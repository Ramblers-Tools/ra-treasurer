<?php

/**
 * @package     com_ra_treasurer
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_treasurer\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class ClaimtypeTable extends Table {

    protected $_supportNullValue = true;

    public function __construct(DatabaseDriver $db) {
        parent::__construct('#__ra_claim_types', 'id', $db);
        $this->setColumnAlias('published', 'state');
    }

    public function check() {
        $this->description = trim((string) $this->description);

        if ($this->description === '') {
            $this->setError('A description is required.');
            return false;
        }

        return parent::check();
    }
}
