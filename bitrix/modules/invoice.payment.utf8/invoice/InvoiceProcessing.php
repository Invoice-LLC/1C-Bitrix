<?php

include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/invoice.payment.utf8/lib/RestClient.php');
include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/invoice.payment.utf8/lib/TerminalInfo.php');
include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/invoice.payment.utf8/lib/PaymentInfo.php');
include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/invoice.payment.utf8/lib/CREATE_TERMINAL.php');
include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/invoice.payment.utf8/lib/CREATE_PAYMENT.php');
include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/invoice.payment.utf8/lib/common/ORDER.php');
include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/invoice.payment.utf8/lib/common/SETTINGS.php');
include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/invoice.payment.utf8/lib/common/ITEM.php');

/**
 * Class InvoiceProcessing
 * @version 1.0.0
 * @author Kirill A Kuznetsov
 * @email dev@invoice.su
 */
class InvoiceProcessing
{
    /**
     * @var $key string - API Key
     * @var $login string - Invoice login
     */
    private $key;
    private $login;

    private $terminal_id;

    /**
     * @var $client RestClient
     */
    private $client;

    /**
     * @var $payment_link string - Reference to the payment order
     */
    private $payment_link;

    public function __construct()
    {
        CModule::IncludeModule('sale');
        $this->key = COption::GetOptionString('invoice.payment.utf8', 'invoice_api_key', '');
        $this->login = COption::GetOptionString('invoice.payment.utf8', 'invoice_login', '');

        if($this->login == null or $this->key == null)
        {
            $this->log("Login or key is null");
            throw new Exception("Ошибка Invoice, попробуйте изменить настройки");
            return;
        } else {
            $this->client = new RestClient($this->login, $this->key);
        }

        $this->terminal_id = COption::GetOptionString("invoice.payment.utf8", 'invoice_terminal', '');
        $this->initTerminal();
    }

    private function initTerminal() {
        if(!isset($this->terminal_id) or $this->terminal_id == null) {
            $rsSites = CSite::GetByID("s1");
            $arSite = $rsSites->Fetch();

            $name = $arSite["NAME"];
            $description = $arSite["SITE_NAME"];

            $this->createTerminal($name, $description);
        }
    }

    /**
     * Creating Invoice payment terminal
     * @param $name string
     * @param $description string
     * @throws Exception
     */
    public function createTerminal($name, $description) {
        $create_terminal = new CREATE_TERMINAL();
        $create_terminal->description = $description;
        $create_terminal->name = $name;
        $create_terminal->type = "dynamical";
        $create_terminal->defaultPrice = 1;
        
        if($create_terminal->name == null or empty($create_terminal->name)) {
            $create_terminal->name = "Invoice Bitrix";   
        }

        $this->log("Creating new terminal \n");
        $terminal = $this->client->CreateTerminal($create_terminal);

        if($terminal == null or $terminal->error != null) {
            $terminal = $this->client->CreateTerminal($create_terminal);
            if($terminal == null or $terminal->error != null) {
                $this->log("ERROR: ".json_encode($terminal) ."\n");
                throw new Exception("Ошибка при создании терминала");
                return;
            }
        }
        $this->log("Terminal is created \n");
        $this->terminal_id = $terminal->id;
        $this->setOptionParameter("invoice_terminal", $terminal->id);
    }

    /**
     * Creating Invoice payment
     * @param $order_id integer
     * @return string
     * @throws Exception
     */
    public function createPayment($order_id) {
        $create_payment = new CREATE_PAYMENT();
        $bOrder = \Bitrix\Sale\Order::load($order_id);

        if($bOrder == null) {
            $this->log("Order in null \n");
            throw new Exception("Order not found!");
            return;
        }

        $payment_id = $this->getTransactionId($order_id);
        if(isset($payment_id) and $payment_id != null) {
            return "https://pay.invoice.su/P$payment_id";
        }

        $order = new ORDER();
        $order->amount = $bOrder->getPrice();
        $order->id = $order_id;
        $order->currency = $bOrder->getCurrency();
        $create_payment->order = $order;

        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $settings = new SETTINGS();
        $settings->terminal_id = $this->terminal_id;
        $settings->fail_url = $url;
        $settings->success_url = $url;
        $create_payment->settings = $settings;

        $receipt = array();
        $basket = $bOrder->getBasket();
        foreach ($basket as $basketItem) {
            $item = new ITEM();
            $item->name = $basketItem->getField('NAME');
            $item->resultPrice = $basketItem->getFinalPrice();
            $item->quantity = $basketItem->getQuantity();
            $item->price = $basketItem->getPrice();

            array_push($receipt, $item);
        }
        $create_payment->receipt = $receipt;

        $payment = $this->client->CreatePayment($create_payment);
        if($payment == null or $payment->error != null) {
            $this->log("ERROR: ". json_encode($payment) . "\n");
            throw new Exception("Ошибка при создании заказа");
        } else {
            $this->addTransactionId($order_id, $payment->id);
            return $payment->payment_url;
        }
    }

    /**
     * @param $result string - Payment status(successful/failed/check/refund)
     * @param $order_id - Bitrix order ID
     * @param $type - Notification type
     * @throws Exception
     */
    public function setPaymentResult($result, $order_id, $type)
    {
        $order = \Bitrix\Sale\Order::load($order_id);
        $paymentCollection = $order->getPaymentCollection();

        foreach ($paymentCollection as $payment) {
            switch ($result) {
                case "successful" :
                    if($type == "pay")
                        $payment->setPaid("Y");

                    if($type == "refund")
                        $payment->setReturn("Y");

                    break;
                case "error":
                    $payment->setPaid("N");
                    break;
            }
        }
        $order->save();
    }

    /**
     * @param $key string - API Key
     * @param $status string - Payment status
     * @param $order_id string - Transaction ID
     *
     * @return string - Signature
     */
    public function getSignature($key, $status, $order_id) {
        return md5($order_id.$status.$key);
    }

    /**
     * @param $key string
     * @param $value string
     */
    private function setOptionParameter($key, $value) {
        COption::SetOptionString('invoice.payment', $key, $value);
    }

    /**
     * @param $order_id integer - Bitrix order ID
     * @return string|null - Invoice payment ID
     */
    private function getTransactionId($order_id) {
        return COption::GetOptionString('invoice.payment.utf8', 'invoice_tran_'.$order_id, '');
    }

    /**
     * @param $order_id integer - Bitrix order ID
     * @param $tranId string - Invoice payment ID
     */
    private function addTransactionId($order_id, $tranId) {
        $this->setOptionParameter("invoice_tran_".$order_id, $tranId);
    }

    private function log($log) {
        CEventLog::Add(array(
            "SEVERITY" => "INFO",
            "AUDIT_TYPE_ID" => "INVOICE_PAYMENT",
            "MODULE_ID" => "main",
            "ITEM_ID" => $_POST["user"],
            "DESCRIPTION" => $log,
        ));
    }
}
