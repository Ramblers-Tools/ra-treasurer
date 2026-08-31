<?php

$root = dirname(__DIR__);
$controller = file_get_contents($root . '/com_ra_treasurer/site/src/Controller/BookingsController.php');
$model = file_get_contents($root . '/com_ra_treasurer/site/src/Model/BookingsModel.php');
$paymentsModel = file_get_contents($root . '/com_ra_treasurer/site/src/Model/PaymentsModel.php');
$template = file_get_contents($root . '/com_ra_treasurer/site/tmpl/bookings/default.php');
$installSql = file_get_contents($root . '/com_ra_treasurer/administrator/sql/install.mysql.utf8.sql');

$loginCheck = strpos($controller, '$user->guest');
$permissionCheck = strpos($controller, "authorise('core.create', 'com_ra_treasurer')");
$tokenCheck = strpos($controller, '$this->checkToken()');

if ($loginCheck === false || $permissionCheck === false || $tokenCheck === false
    || !($loginCheck < $permissionCheck && $permissionCheck < $tokenCheck)) {
    throw new RuntimeException('Payment authorization checks are missing or in the wrong order.');
}

foreach (['date_paid', 'amount_paid', 'payment_created_by', 'payment_created'] as $field) {
    if (strpos($model, "quoteName('" . $field . "')") === false) {
        throw new RuntimeException('Payment persistence is missing ' . $field . '.');
    }
}

if (strpos($model, "quoteName('amount_paid') . ' IS NULL'") === false) {
    throw new RuntimeException('Payment update must be restricted to outstanding bookings.');
}

if (strpos($model, "a.amount_paid IS NULL") === false
    || strpos($paymentsModel, "a.amount_paid IS NOT NULL") === false) {
    throw new RuntimeException('Outstanding/payment list filters are incorrect.');
}

foreach (['recordPaymentModal', 'date_paid', 'amount_paid', 'bookings.recordPayment'] as $fragment) {
    if (strpos($template, $fragment) === false) {
        throw new RuntimeException('Payment modal is missing ' . $fragment . '.');
    }
}

if (!preg_match('/`payment_created`\s+DATETIME/i', $installSql)) {
    throw new RuntimeException('payment_created must be a DATETIME column.');
}

echo "Payment feature contract tests passed\n";
