<?php

/**
 * 24/08/26 created by component-creator
 */

namespace Ramblers\Component\Ra_treasurer\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Versioning\VersionableControllerTrait;

/**
 * Payment controller class.
 *
 * @since  1.0.2
 */
class PaymentController extends FormController {

    use VersionableControllerTrait;

    protected $view_list = 'payments';

}
