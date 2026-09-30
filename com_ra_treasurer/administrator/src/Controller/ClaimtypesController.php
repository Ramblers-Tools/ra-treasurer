<?php

/**
 * @package     com_ra_treasurer
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_treasurer\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

class ClaimtypesController extends AdminController {

    public function getModel($name = 'Claimtype', $prefix = 'Administrator', $config = array()) {
        return parent::getModel($name, $prefix, ['ignore_request' => true]);
    }
}
