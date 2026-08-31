<?php

/**
 * 20/08/26 created by component-creator
 */

namespace Ramblers\Component\Ra_treasurer\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Ramblers\Component\Ra_treasurer\Site\Service\PaymentInputValidator;

/**
 * Bookings class.
 *
 * @since  1.0.0
 */
class BookingsController extends FormController {

    private function paymentText(string $key, string $fallback): string {
        $text = Text::_($key);

        return $text === $key ? $fallback : $text;
    }

    /**
     * Proxy for getModel.
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional
     * @param   array   $config  Configuration array for model. Optional
     *
     * @return  object	The model
     *
     * @since   1.0.0
     */
    public function getModel($name = 'Bookings', $prefix = 'Site', $config = array()) {
        return parent::getModel($name, $prefix, array('ignore_request' => true));
    }

    public function recordPayment(): void {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $redirect = 'index.php?option=com_ra_treasurer&view=bookings';
        $itemId = $this->input->getInt('Itemid');

        if ($itemId > 0) {
            $redirect .= '&Itemid=' . $itemId;
        }

        if ($user->guest || (int) $user->id < 1) {
            $app->enqueueMessage($this->paymentText(
                            'COM_RA_TREASURER_PAYMENT_LOGIN_REQUIRED',
                            'You must be logged in to record a payment.'
                    ), 'warning');
            $this->setRedirect(Route::_($redirect, false));
            return;
        }

        if (!$user->authorise('core.create', 'com_ra_treasurer')) {
            $app->enqueueMessage($this->paymentText(
                            'COM_RA_TREASURER_PAYMENT_CREATE_DENIED',
                            'You do not have permission to add payments.'
                    ), 'warning');
            $this->setRedirect(Route::_($redirect, false));
            return;
        }

        $this->checkToken();

        $bookingId = $this->input->getInt('booking_id');

        if ($bookingId < 1) {
            $app->enqueueMessage($this->paymentText(
                            'COM_RA_TREASURER_PAYMENT_INVALID_BOOKING',
                            'The selected booking is invalid.'
                    ), 'warning');
            $this->setRedirect(Route::_($redirect, false));
            return;
        }

        $today = Factory::getDate('now', Factory::getConfig()->get('offset'))->format('Y-m-d');

        try {
            $payment = PaymentInputValidator::validate(
                            $this->input->post->getString('date_paid'),
                            $this->input->post->getString('amount_paid'),
                            $today
            );
        } catch (\InvalidArgumentException $exception) {
            $fallbacks = [
                'COM_RA_TREASURER_PAYMENT_INVALID_DATE' => 'Enter a valid payment date.',
                'COM_RA_TREASURER_PAYMENT_FUTURE_DATE' => 'The payment date cannot be in the future.',
                'COM_RA_TREASURER_PAYMENT_INVALID_AMOUNT' => 'Enter a positive currency amount with no more than two decimal places.',
            ];
            $key = $exception->getMessage();
            $app->enqueueMessage($this->paymentText($key, $fallbacks[$key] ?? 'Invalid payment details.'), 'warning');
            $this->setRedirect(Route::_($redirect, false));
            return;
        }

        $created = Factory::getDate()->toSql();
        $model = $this->getModel();

        try {
            $saved = $model->recordPayment(
                    $bookingId,
                    $payment['date_paid'],
                    $payment['amount_paid'],
                    (int) $user->id,
                    $created
            );
        } catch (\Throwable $exception) {
            Log::add(
                    'Payment save failed for booking ' . $bookingId . ': ' . $exception->getMessage(),
                    Log::ERROR,
                    'com_ra_treasurer'
            );
            $message = $this->paymentText(
                    'COM_RA_TREASURER_PAYMENT_SAVE_FAILED',
                    'The payment could not be saved.'
            );

            if (JDEBUG) {
                $message .= ' ' . $exception->getMessage();
            }

            $app->enqueueMessage($message, 'error');
            $this->setRedirect(Route::_($redirect, false));
            return;
        }

        if (!$saved) {
            $app->enqueueMessage($this->paymentText(
                            'COM_RA_TREASURER_PAYMENT_NOT_OUTSTANDING',
                            'The booking was not found or has already been paid.'
                    ), 'warning');
            $this->setRedirect(Route::_($redirect, false));
            return;
        }

        $app->enqueueMessage($this->paymentText(
                        'COM_RA_TREASURER_PAYMENT_SAVED',
                        'Payment recorded successfully.'
                ), 'success');
        $this->setRedirect(Route::_($redirect, false));
    }

}
