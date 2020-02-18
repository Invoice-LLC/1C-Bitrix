<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
include(GetLangFileName(dirname(__FILE__)."/", "/payment.php"));

$id =  CSalePaySystemAction::GetParamValue("OrderID");

include("InvoiceProcessing.php");

if(isset($_POST["invoice_submit"])) {
    $processing = new InvoiceProcessing();

    try {
        $link = $processing->createPayment($id);
        header("Location: ".$link);
        echo "��������...";
    } catch (Exception $ex) {
        echo "������ ��� �������� �������, ���������� � ��������������";
    }
} else {
    ?>
    <form method="post" target="_blank" action="/personal/order/payment/?ORDER_ID=<?=$id?>&PAYMENT_ID=<?=$id?>/1" accept-charset="utf-8">
        <input type="submit" name="invoice_submit" class="btn btn-default" value="��������">
    </form>
    <?php
}
?>


