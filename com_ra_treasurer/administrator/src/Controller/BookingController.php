<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_treasurer
 * @author     Charlie Bigley <charlie@ramblers.tools>
 * @copyright  Ramblers Tools
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_treasurer\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Versioning\VersionableControllerTrait;

/**
 * Booking controller class.
 *
 * @since  1.0.0
 */
class BookingController extends FormController
{
	use VersionableControllerTrait;

	protected $view_list = 'bookings';
}
