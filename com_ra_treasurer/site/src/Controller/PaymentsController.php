<?php

/**
 * @version    CVS: 1.0.2
 * @package    Com_Ra_treasurer
 * @author     Charlie Bigley <charlie@ramblers.tools>
 * @copyright  Ramblers Tools
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_treasurer\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;

/**
 * Payments class.
 *
 * @since  1.0.2
 */
class PaymentsController extends FormController {

    /**
     * Proxy for getModel.
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional
     * @param   array   $config  Configuration array for model. Optional
     *
     * @return  object	The model
     *
     * @since   1.0.2
     */
    public function getModel($name = 'Payments', $prefix = 'Site', $config = array()) {
        return parent::getModel($name, $prefix, array('ignore_request' => true));
    }

}
