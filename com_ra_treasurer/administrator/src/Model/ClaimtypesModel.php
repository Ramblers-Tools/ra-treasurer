<?php

/**
 * @package     com_ra_treasurer
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_treasurer\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class ClaimtypesModel extends ListModel {

    public function __construct($config = array()) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'state', 'a.state',
                'description', 'a.description',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = null, $direction = null) {
        parent::populateState('a.id', 'ASC');
    }

    protected function getStoreId($id = '') {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.state');

        return parent::getStoreId($id);
    }

    protected function getListQuery() {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
                ->select($db->quoteName('a') . '.*')
                ->from($db->quoteName('#__ra_claim_types', 'a'));

        $state = $this->getState('filter.state');

        if (is_numeric($state)) {
            $query->where($db->quoteName('a.state') . ' = ' . (int) $state);
        } else {
            $query->where($db->quoteName('a.state') . ' IN (0, 1)');
        }

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
            } else {
                $query->where(
                        $db->quoteName('a.description')
                        . ' LIKE ' . $db->quote('%' . $db->escape($search, true) . '%')
                );
            }
        }

        $orderCol = $this->state->get('list.ordering', 'a.id');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        if ($orderCol && $orderDirn) {
            $query->order($db->escape($orderCol . ' ' . $orderDirn));
        }

        return $query;
    }
}
