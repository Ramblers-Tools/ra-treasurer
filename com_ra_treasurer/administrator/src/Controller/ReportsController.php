<?php

/**
 * 20/08/26 created by component-creator
 */

namespace Ramblers\Component\Ra_treasurer\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;

use Ramblers\Component\Ra_events\Site\Helpers\BookingHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\SchemaHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
/**
 * Reports list controller class.
 *
 * @since  1.0.0
 */
class ReportsController extends AdminController {

    protected $breadcrumbs;
    protected $back;
    protected $db;
    protected $app;
    protected $toolsHelper;

    public function __construct() {
        parent::__construct();
        $this->db = Factory::getDbo();
        $this->toolsHelper = new ToolsHelper;
        $this->app = Factory::getApplication();
        $this->back = 'administrator/index.php?option=com_ra_treasurer&view=reports';
        $this->breadcrumbs = $this->toolsHelper->buildLink('administrator/index.php', 'Home Dashboard');
        $this->breadcrumbs .= '>' . $this->toolsHelper->buildLink('administrator/index.php?option=com_ra_tools&view=dashboard', 'RA Dashboard');
        $this->breadcrumbs .= '>' . $this->toolsHelper->buildLink('administrator/index.php?option=com_ra_treasurer&view=reports', 'Treasurer Reports');

        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
    }
/*
Temporary function to corrrect database updates omitted from the normal update process.
Can be invoked frot the browser administrator/index.php?option=com_ra_treasurer&task=system.checkSchema
*/
    public function checkSchema() { 
        if (!$this->toolsHelper->isSuperuser()) {
            return;
        }
        $helper = New SchemaHelper;

        // table ra_guest_bookings
        $details = '(
          `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `booking_id` INT  NOT NULL,
          `name` VARCHAR(100) NOT NULL,
          `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;';
        $helper->checkTable('ra_guest_bookings', $details);

        $helper->checkColumn('ra_events', 'max_bookings', 'A', 'INT NULL AFTER num_bookings; ');
        $helper->checkColumn('ra_events', 'requires_payment', 'A', 'INT DEFAULT "0" AFTER bookable; ');
        $helper->checkColumn('ra_events', 'waiting_list_enabled', 'A', 'INT DEFAULT "0" AFTER max_bookings; ');
        $helper->checkColumn('ra_events', 'multi_guest_enabled', 'A', 'INT DEFAULT "0" AFTER waiting_list_enabled; ');
        $helper->checkColumn('ra_events', 'max_guests', 'A', 'INT DEFAULT "0" AFTER multi_guest_enabled; ');

        echo $this->toolsHelper->backButton($this->back);
    }


    /**
     * Method to clone existing Reports
     *
     * @return  void
     *
     * @throws  Exception
     */
    public function duplicate() {
        // Check for request forgeries
        $this->checkToken();

        // Get id(s)
        $pks = $this->input->post->get('cid', array(), 'array');

        try {
            if (empty($pks)) {
                throw new \Exception(Text::_('COM_RA_TREASURER_NO_ELEMENT_SELECTED'));
            }

            ArrayHelper::toInteger($pks);
            $model = $this->getModel();
            $model->duplicate($pks);
            $this->setMessage(Text::_('COM_RA_TREASURER_ITEMS_SUCCESS_DUPLICATED'));
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
        }

        $this->setRedirect('index.php?option=com_ra_treasurer&view=reports');
    }

    /**
     * Proxy for getModel.
     *
     * @param   string  $name    Optional. Model name
     * @param   string  $prefix  Optional. Class prefix
     * @param   array   $config  Optional. Configuration array for model
     *
     * @return  object	The Model
     *
     * @since   1.0.0
     */
    public function getModel($name = 'Report', $prefix = 'Administrator', $config = array()) {
        return parent::getModel($name, $prefix, array('ignore_request' => true));
    }


    public function showGuests() {
        ToolBarHelper::title('Bookings with Guests');
        echo $this->breadcrumbs . '<br>';        
        $bookingHelper = new BookingHelper();
        $toolsTable = new ToolsTable();        
        $sql = 'SELECT b.id, e.event_date, e.title, p.preferred_name, b.partner, COUNT(g.id) AS cnt ';
        $sql .= 'FROM `#__ra_bookings` AS b ';
        $sql .= 'INNER JOIN #__ra_events AS e ON e.id = b.event_id ';
        $sql .= 'LEFT JOIN #__ra_profiles AS p ON p.id = b.user_id ';
        $sql .= 'LEFT JOIN #__ra_guest_bookings AS g ON g.booking_id = b.id ';
        $sql .= 'WHERE b.partner > "" ';
        $sql .= 'GROUP BY b.id, e.event_date, e.title, p.preferred_name, b.partner ';
        $sql .= 'ORDER BY e.event_date DESC, e.title, p.preferred_name';
        $rows = $this->toolsHelper->getRows($sql);

        if (!$rows) {
            echo 'No bookings have Guests<br>';
            return;
        }
        $header = 'Event Date,Event Title,User,Guest,Count';
        $toolsTable->add_header($header);
        foreach ($rows as $row) {
            $toolsTable->add_item($row->event_date);
            $toolsTable->add_item($row->title);
            $toolsTable->add_item($row->preferred_name);
            $toolsTable->add_item($row->partner);
            $toolsTable->add_item($row->cnt);
            $toolsTable->generate_line();
            // convert the name of the partner to an array with just one item
            if ($row->cnt == 0) {
                $names = explode('@', $row->partner);
                // Save to ra_booking_guests
 //               $bookingHelper->saveGuests($row->id, $names);
            }
        } 
        $toolsTable->generate_table('Bookings');
        echo $this->toolsHelper->backButton($this->back);
    }

}
