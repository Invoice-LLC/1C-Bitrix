<h1>Invoice</h1>
<?php
use Bitrix\Sale;

include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/invoice.payment.utf8/lib/RestClient.php');

if (isset($_POST["invoice_settings_submit"]) && check_bitrix_sessid()) {
    COption::SetOptionString('invoice.payment.utf8', 'invoice_api_key', $_POST['invoice_api_key']);
    COption::SetOptionString('invoice.payment.utf8', 'invoice_login', $_POST['invoice_login']);

    $apiKey = COption::GetOptionString('invoice.payment.utf8', 'invoice_api_key', '');
    $merchantId = COption::GetOptionString('invoice.payment.utf8', 'invoice_login', '');

    $client = new RestClient($merchantId, $apiKey);

    $status = $client->AuthCheck()->status == "successful" ? "Auntification successful" : "Auntification failed";

    COption::SetOptionString('invoice.payment.utf8', 'error_api_merchant', $status);
}
?>
<form method="post">
    <table cellspacing="0" cellpadding="0" border="0" class="internal" style="width: 85%;">
        <tr>
            <td>Ключ API Invoice</td>
            <td width="25%" style="vertical-align: middle;">
                <input width="100%" type="text" name="invoice_api_key" placeholder="Ключ API Invoice" value="<?= COption::GetOptionString("invoice.payment.utf8", "invoice_api_key", "");?>">
            </td>
        </tr>
        <tr>
            <td>ID компании Invoice</td>
            <td width="25%" style="vertical-align: middle;">
                <input width="100%" type="text" name="invoice_login" placeholder="ID компании" value="<?= COption::GetOptionString("invoice.payment.utf8", "invoice_login", "");?>">
            </td>
        </tr>
        <tr>
            <td>
                Все данные можно получить в <a href="https://lk.invoice.su/">личном кабинете Invoice</a>
            </td>
            <td name="error_api_merchant"> <?= COption::GetOptionString('invoice.payment.utf8', 'error_api_merchant', ''); ?> 
            </td>

        </tr>
        <tr>
            <td width="25%" style="vertical-align: middle;">
                <input type="submit" name="invoice_settings_submit" value="Сохранить">
            </td>
            <td></td>
        </tr>
    </table>
    <?=bitrix_sessid_post();?>
</form>

