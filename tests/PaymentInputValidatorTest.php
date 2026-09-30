<?php

define('_JEXEC', 1);

require_once dirname(__DIR__) . '/com_ra_treasurer/site/src/Service/PaymentInputValidator.php';

use Ramblers\Component\Ra_treasurer\Site\Service\PaymentInputValidator;

$today = '2026-08-26';

foreach ([
    ['2026-08-26', '12', '12.00'],
    ['2026-08-25', '12.5', '12.50'],
    ['2020-02-29', '0.01', '0.01'],
    ['2026-08-01', '99999.99', '99999.99'],
] as [$date, $amount, $normalisedAmount]) {
    $result = PaymentInputValidator::validate($date, $amount, $today);

    if ($result['date_paid'] !== $date || $result['amount_paid'] !== $normalisedAmount) {
        throw new RuntimeException('Valid payment input was not normalised correctly.');
    }
}

foreach ([
    ['2026-08-27', '12.00', 'COM_RA_TREASURER_PAYMENT_FUTURE_DATE'],
    ['2026-02-30', '12.00', 'COM_RA_TREASURER_PAYMENT_INVALID_DATE'],
    ['', '12.00', 'COM_RA_TREASURER_PAYMENT_INVALID_DATE'],
    ['2026-08-26', '0', 'COM_RA_TREASURER_PAYMENT_INVALID_AMOUNT'],
    ['2026-08-26', '-1.00', 'COM_RA_TREASURER_PAYMENT_INVALID_AMOUNT'],
    ['2026-08-26', '12.345', 'COM_RA_TREASURER_PAYMENT_INVALID_AMOUNT'],
    ['2026-08-26', '100000.00', 'COM_RA_TREASURER_PAYMENT_INVALID_AMOUNT'],
    ['2026-08-26', 'abc', 'COM_RA_TREASURER_PAYMENT_INVALID_AMOUNT'],
] as [$date, $amount, $message]) {
    try {
        PaymentInputValidator::validate($date, $amount, $today);
        throw new RuntimeException('Invalid payment input was accepted.');
    } catch (InvalidArgumentException $exception) {
        if ($exception->getMessage() !== $message) {
            throw $exception;
        }
    }
}

echo "Payment input validator tests passed\n";
