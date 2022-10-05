<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

include("InvoiceProcessing.php");

$postData = file_get_contents('php://input');
$notification = json_decode($postData, true);

if(!isset($notification) or !isset($notification["id"])) {
    die("the notification must not be null");
}

$signature = null;
if(!isset($notification["signature"])) {
    die("the signature must not be null");
} else {
    $signature = $notification["signature"];
}

$status = null;

if(!isset($notification["status"])) {
    die("status must not be null");
} else {
    $status = $notification["status"];
}

$key = COption::GetOptionString('invoice.payment.utf8', 'invoice_api_key', '');

if($key == null) {
    die("api key is invalid");
}

$order_id = null;

if(!isset($notification["order"]["id"])) {
    die("Order not found");
} else {
    $order_id = $notification["order"]["id"];
}

$processing = new InvoiceProcessing();

if($processing->getSignature($key, $notification["status"], $notification["id"]) != $signature) {
    die("Wrong signature");
}

$processing->setPaymentResult($status,$notification["order"]["id"], $notification["notification_type"]);

echo "OK";