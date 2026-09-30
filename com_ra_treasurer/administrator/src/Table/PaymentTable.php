<?php

/**
 * @package     com_ra_treasurer
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_treasurer\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * Persists the payment fields held on a booking record.
 */
class PaymentTable extends Table {

    protected $_supportNullValue = true;

    public function __construct(DatabaseDriver $db) {
        parent::__construct('#__ra_bookings', 'id', $db);
        $this->setColumnAlias('published', 'state');
    }

    public function check() {
        if (!is_numeric($this->amount_paid) || (float) $this->amount_paid > 99999.99) {
            $this->setError($this->paymentText(
                            'COM_RA_TREASURER_PAYMENT_INVALID_AMOUNT',
                            'Enter a positive payment amount less than 99999.99'
            ));
            return false;
        }

        $submittedDate = trim((string) $this->date_paid);

        if (preg_match('/^(\d{4}-\d{2}-\d{2})(?:[ T].*)?$/', $submittedDate, $matches)) {
            $submittedDate = $matches[1];
        }

        $datePaid = \DateTimeImmutable::createFromFormat('!Y-m-d', $submittedDate);
        $dateErrors = \DateTimeImmutable::getLastErrors();

        if ($datePaid === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) || $datePaid->format('Y-m-d') !== $submittedDate) {
            $this->setError($this->paymentText(
                            'COM_RA_TREASURER_PAYMENT_INVALID_DATE',
                            'Enter a valid payment date.'
            ));
            return false;
        }

        $timezone = Factory::getApplication()->get('offset');
        $today = new \DateTimeImmutable(Factory::getDate('now', $timezone)->format('Y-m-d'));

        if ($datePaid > $today) {
            $this->setError($this->paymentText(
                            'COM_RA_TREASURER_PAYMENT_FUTURE_DATE',
                            'The payment date cannot be in the future.'
            ));
            return false;
        }

        $this->date_paid = $datePaid->format('Y-m-d');
        $this->amount_paid = number_format((float) $this->amount_paid, 2, '.', '');

        return parent::check();
    }

    public function store($updateNulls = true) {
        $now = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        if (empty($this->payment_created)) {
            $this->payment_created = $now;
            $this->payment_created_by = $userId;
        }

        $this->payment_modified = $now;
        $this->payment_modified_by = $userId;

        return parent::store($updateNulls);
    }

    private function paymentText(string $key, string $fallback): string {
        $language = Factory::getApplication()->getLanguage();
        $language->load('com_ra_treasurer', JPATH_ADMINISTRATOR, null, true);
        $text = Text::_($key);

        return $text === $key ? $fallback : $text;
    }

}
