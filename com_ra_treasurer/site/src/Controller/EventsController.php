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
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

/**
 * Events class.
 *
 * @since  1.0.2
 */
class EventsController extends FormController {

    protected $app;
    protected $db;
    protected $toolsHelper;

    public function __construct() {
        parent::__construct();
        $this->db = Factory::getContainer()->get('DatabaseDriver');
        $this->toolsHelper = new ToolsHelper;
        $this->app = Factory::getApplication();
// Import CSS
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
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
     * @since   1.0.2
     */
    public function getModel($name = 'Events', $prefix = 'Site', $config = array()) {
        return parent::getModel($name, $prefix, array('ignore_request' => true));
    }

    public function showPayments() {
        $id = $this->app->input->getInt('id', '0');
        $sql = 'SELECT e.event_date, e.title, e.num_bookings, p.preferred_name ';
        $sql .= 'FROM #__ra_events AS e ';
        $sql .= 'INNER JOIN #__contact_details AS c ON c.id=e.contact_id ';
        $sql .= 'INNER JOIN #__ra_profiles AS p ON p.id=c.user_id ';
        $sql .= 'WHERE e.id= ' . $id;

        $item = $this->toolsHelper->getItem($sql);

        echo 'Event date: <b>' . $item->event_date . '</b><br>';
        echo 'Event name: <b>' . $item->title . '</b><br>';
        echo 'Organiser: <b>' . $item->preferred_name . '</b><br>';

        $sql = 'SELECT SUM(b.amount_paid) AS tot_payments ';
        $sql .= 'FROM #__ra_bookings AS b ';
        $sql .= 'INNER JOIN #__ra_events AS e ON e.id=b.event_id ';
        $sql .= 'WHERE e.id= ' . $id;
        $tot_payments = $this->toolsHelper->getValue($sql);

        echo 'Total paid: <b>' . $tot_payments . '</b><br>';

        // Get summary of payment amounts
        $sql = 'SELECT b.amount_paid, COUNT(*), SUM(b.amount_paid) ';
        $sql .= 'FROM #__ra_bookings AS b ';
        $sql .= 'INNER JOIN #__ra_events AS e ON e.id=b.event_id ';
        $sql .= 'WHERE b.amount_paid IS NOT NULL ';
        $sql .= 'AND e.id= ' . $id;
        $sql .= ' GROUP BY b.amount_paid ';
// echo $sql;
        echo '<h4>Summary of payments received</h4>';
        $rows = $this->toolsHelper->getRows($sql);
        $table = new ToolsTable;
        $table->add_header('Count,Amount,Total');

        foreach ($rows as $row) {
            $table->add_item($row->{'COUNT(*)'});
            $table->add_item($row->amount_paid);

            $table->add_item($row->{'SUM(b.amount_paid)'});
            $table->generate_line();
        }
        $table->generate_table();

        // get individual payments
        $sql = 'SELECT b.date_paid, b.payment_created, p.preferred_name,b.amount_paid ';
        $sql .= 'FROM #__ra_bookings AS b ';
        $sql .= 'INNER JOIN #__ra_events AS e ON e.id=b.event_id ';
        $sql .= 'LEFT JOIN #__ra_profiles AS p ON p.id=b.user_id ';
        $sql .= 'WHERE b.amount_paid IS NOT NULL ';
        $sql .= 'AND e.id= ' . $id;
        $sql .= ' ORDER BY b.payment_created';
        echo '<h4>Details of payments received</h4>';
        $rows = $this->toolsHelper->getRows($sql);
        $table = new ToolsTable;
        $table->add_header('Date Paid,Payment Created,Member,Amount');

        foreach ($rows as $row) {
            $table->add_item($row->date_paid);
            $table->add_item($row->payment_created);
            $table->add_item($row->preferred_name);
            $table->add_item($row->amount_paid);
            $table->generate_line();
        }
        $table->generate_table();

        $back = 'index.php?option=com_ra_treasurer&view=events';
        echo $this->toolsHelper->backButton($back);
    }

}
