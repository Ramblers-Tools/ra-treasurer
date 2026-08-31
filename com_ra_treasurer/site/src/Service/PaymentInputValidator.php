<?php

namespace Ramblers\Component\Ra_treasurer\Site\Service;

defined('_JEXEC') or die;

/**
 * Validates and normalises payment values submitted from the bookings list.
 */
final class PaymentInputValidator
{
    public static function validate(string $datePaid, string $amountPaid, string $today): array
    {
        $datePaid = trim($datePaid);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $datePaid);
        $dateErrors = \DateTimeImmutable::getLastErrors();

        if ($date === false
            || ($dateErrors !== false
                && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $date->format('Y-m-d') !== $datePaid) {
            throw new \InvalidArgumentException('COM_RA_TREASURER_PAYMENT_INVALID_DATE');
        }

        if ($datePaid > $today) {
            throw new \InvalidArgumentException('COM_RA_TREASURER_PAYMENT_FUTURE_DATE');
        }

        $amountPaid = trim($amountPaid);

        if (!preg_match('/^(?:0|[1-9]\d{0,4})(?:\.\d{1,2})?$/', $amountPaid)
            || (float) $amountPaid <= 0) {
            throw new \InvalidArgumentException('COM_RA_TREASURER_PAYMENT_INVALID_AMOUNT');
        }

        return [
            'date_paid' => $datePaid,
            'amount_paid' => number_format((float) $amountPaid, 2, '.', ''),
        ];
    }
}
