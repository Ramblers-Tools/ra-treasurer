<?php

/**
 * @version    CVS: 1.0.2
 * @package    Com_Ra_treasurer
 * @author     Charlie Bigley <charlie@ramblers.tools>
 * @copyright  Ramblers Tools
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_treasurer\Site\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\Model\ListModel;
use \Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use \Joomla\CMS\Helper\TagsHelper;
use \Joomla\CMS\Layout\FileLayout;
use \Joomla\Database\ParameterType;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\Database\DatabaseInterface;

/**
 * Methods supporting a list of Ra_treasurer records.
 *
 * @since  1.0.2
 */
class EventsModel extends ListModel {

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @see    JController
     * @since  1.0.2
     */
    public function __construct($config = array()) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'organiser', 'p.preferred_name',
                'title', 'a.title',
                'event_date', 'a.event_date',
                'max_bookings', 'a.max_bookings',
                'num_confirmed',
                'total_paid',
            );
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   Elements order
     * @param   string  $direction  Order direction
     *
     * @return  void
     *
     * @throws  Exception
     *
     * @since   1.0.2
     */
    protected function populateState($ordering = null, $direction = null) {
        // List state information.
        parent::populateState('a.event_date', 'DESC');

        $app = Factory::getApplication();
        $list = $app->getUserState($this->context . '.list');

        $value = $app->getUserState($this->context . '.list.limit', $app->get('list_limit', 25));
        $list['limit'] = $value;

        $this->setState('list.limit', $value);

        $value = $app->input->get('limitstart', 0, 'uint');
        $this->setState('list.start', $value);

        $ordering = $this->getUserStateFromRequest($this->context . '.filter_order', 'filter_order', 'a.event_date');
        $direction = strtoupper($this->getUserStateFromRequest($this->context . '.filter_order_Dir', 'filter_order_Dir', 'DESC'));

        if (!empty($ordering) || !empty($direction)) {
            $list['fullordering'] = $ordering . ' ' . $direction;
        }

        $app->setUserState($this->context . '.list', $list);

        $context = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $context);

        // Split context into component and optional section
        if (!empty($context)) {
            $parts = FieldsHelper::extract($context);

            if ($parts) {
                $this->setState('filter.component', $parts[0]);
                $this->setState('filter.section', $parts[1]);
            }
        }
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  DatabaseQuery
     *
     * @since   1.0.2
     */
    protected function getListQuery() {
        // Create a new query object.
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
                $this->getState(
                        'list.select', 'DISTINCT a.*'
                )
        );

        $query->from('`#__ra_events` AS a');
        $query->where('bookable="1"');
        $query->where('api_site_id IS NULL');
        // Join over the users for the checked out user.
        $query->select('p.preferred_name ');
        $query->select('(SELECT COUNT(*) FROM #__ra_bookings AS b'
                . ' WHERE b.state = 1 AND b.event_id = a.id) AS num_confirmed');
        $query->select('(SELECT SUM(b.amount_paid) FROM #__ra_bookings AS b'
                . ' WHERE b.state = 1 AND b.event_id = a.id) AS total_paid');
        $query->join('LEFT', '#__contact_details AS c ON c.id=a.contact_id');
        $query->join('LEFT', '#__ra_profiles AS p ON p.id=c.user_id');

        if (!Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_ra_treasurer')) {
            $query->where('a.state = 1');
        } else {
            $query->where('(a.state IN (0, 1))');
        }

        // Filter by search in title
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr($search, 3));
            } else {
                $search = $db->Quote('%' . $db->escape($search, true) . '%');
                $query->where('(p.preferred_name LIKE ' . $search . ' OR a.title LIKE ' . $search . ')');
            }
        }


        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'a.event_date');
        $orderDirn = $this->state->get('list.direction', 'DESC');

        if ($orderCol && $orderDirn) {
            $query->order($db->escape($orderCol . ' ' . $orderDirn));
        }
        if (JDEBUG) {
            Factory::getApplication()->enqueueMessage($this->_db->replacePrefix($query), 'message');
        }
        return $query;
    }

    /**
     * Method to get an array of data items
     *
     * @return  mixed An array of data on success, false on failure.
     */
    public function getItems() {
        $items = parent::getItems();

        return $items;
    }

    /**
     * Overrides the default function to check Date fields format, identified by
     * "_dateformat" suffix, and erases the field if it's not correct.
     *
     * @return void
     */
    protected function loadFormData() {
        $app = Factory::getApplication();
        $filters = $app->getUserState($this->context . '.filter', array());
        $error_dateformat = false;

        foreach ($filters as $key => $value) {
            if (strpos($key, '_dateformat') && !empty($value) && $this->isValidDate($value) == null) {
                $filters[$key] = '';
                $error_dateformat = true;
            }
        }

        if ($error_dateformat) {
            $app->enqueueMessage(Text::_("COM_RA_TREASURER_SEARCH_FILTER_DATE_FORMAT"), "warning");
            $app->setUserState($this->context . '.filter', $filters);
        }

        return parent::loadFormData();
    }

    /**
     * Checks if a given date is valid and in a specified format (YYYY-MM-DD)
     *
     * @param   string  $date  Date to be checked
     *
     * @return bool
     */
    private function isValidDate($date) {
        $date = str_replace('/', '-', $date);
        return (date_create($date)) ? Factory::getDate($date)->format("Y-m-d") : null;
    }

}
