<?php

$root = dirname(__DIR__);
$controller = file_get_contents($root . '/com_ra_treasurer/site/src/Controller/BookingsController.php');
$model = file_get_contents($root . '/com_ra_treasurer/site/src/Model/BookingsModel.php');
$paymentsModel = file_get_contents($root . '/com_ra_treasurer/site/src/Model/PaymentsModel.php');
$template = file_get_contents($root . '/com_ra_treasurer/site/tmpl/bookings/default.php');
$installSql = file_get_contents($root . '/com_ra_treasurer/administrator/sql/install.mysql.utf8.sql');
$adminModel = file_get_contents($root . '/com_ra_treasurer/administrator/src/Model/PaymentModel.php');
$adminTable = file_get_contents($root . '/com_ra_treasurer/administrator/src/Table/PaymentTable.php');
$adminView = file_get_contents($root . '/com_ra_treasurer/administrator/src/View/Payment/HtmlView.php');
$adminTemplate = file_get_contents($root . '/com_ra_treasurer/administrator/tmpl/payment/edit.php');
$adminForm = file_get_contents($root . '/com_ra_treasurer/administrator/forms/payment.xml');

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

foreach (['payment_modified', 'payment_modified_by'] as $field) {
    if (strpos($installSql, '`' . $field . '`') === false
        || strpos($adminTable, '$this->' . $field . ' = ') === false) {
        throw new RuntimeException('Administrator payment saves must maintain ' . $field . '.');
    }
}

if (strpos($adminModel, 'getTable($type = \'Payment\'') === false
    || strpos($adminTable, "parent::__construct('#__ra_bookings', 'id'") === false) {
    throw new RuntimeException('The administrator Payment model must use its payment table for booking records.');
}

foreach (['date_paid', 'amount_paid', 'payment_created', 'payment_created_by', 'payment_modified', 'payment_modified_by'] as $field) {
    if (strpos($adminForm, 'name="' . $field . '"') === false) {
        throw new RuntimeException('Administrator payment form is missing ' . $field . '.');
    }
}

if (strpos($adminForm, 'name="date_paid"') === false
    || !preg_match('/name="date_paid".*?filter="string"/s', $adminForm)) {
    throw new RuntimeException('Administrator payment dates must not be altered by a timezone filter.');
}

foreach (['payment_created', 'payment_modified'] as $field) {
    if (!preg_match('/name="' . $field . '"(?:(?!<field).)*filter="user_utc"/s', $adminForm)) {
        throw new RuntimeException($field . ' must be displayed in the current user timezone.');
    }
}

if (strpos($adminTable, "load('com_ra_treasurer', JPATH_ADMINISTRATOR") === false
    || strpos($adminTable, '$text === $key ? $fallback : $text') === false) {
    throw new RuntimeException('Administrator payment errors require translated text with a safe fallback.');
}

foreach (['member_name', 'event_title', 'event_date'] as $field) {
    if (strpos($adminTemplate, $field) === false) {
        throw new RuntimeException('Administrator payment context is missing ' . $field . '.');
    }
}

if (strpos($adminTemplate, "uitab.endTabSet") !== false
    || strpos($adminView, "ToolbarHelper::cancel('payment.cancel'") === false) {
    throw new RuntimeException('Administrator payment view navigation is invalid.');
}

echo "Payment feature contract tests passed\n";
