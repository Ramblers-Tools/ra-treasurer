<?php

/**
 * @package     com_ra_treasurer
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_treasurer\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;

class ClaimtypeModel extends AdminModel {

    public $typeAlias = 'com_ra_treasurer.claimtype';

    public function getTable($type = 'Claimtype', $prefix = 'Administrator', $config = array()) {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = array(), $loadData = true) {
        return $this->loadForm(
                'com_ra_treasurer.claimtype',
                'claimtype',
                ['control' => 'jform', 'load_data' => $loadData]
        );
    }

    protected function loadFormData() {
        $data = Factory::getApplication()->getUserState('com_ra_treasurer.edit.claimtype.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }
}
