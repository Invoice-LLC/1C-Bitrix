<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
include(GetLangFileName(dirname(__FILE__)."/", "/payment.php"));

$id =  $orderNumber = CSalePaySystemAction::GetParamValue("ORDER_ID");

if($id == null) {
    $id = @$_GET['ORDER_ID'];
}

include("InvoiceProcessing.php");

$processing = new InvoiceProcessing();

try {
    $link = $processing->createPayment($id);
    echo "<script>window.location.href='".$link."';</script>";
    echo "Редирект...";
} catch (Exception $ex) {
    echo "Ошибка при создании платежа, обратитесь к администратору";
}

?>


