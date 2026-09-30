<?php

/**
 * 24/08/26 created by component-creator
 */

namespace Ramblers\Component\Ra_treasurer\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\MVC\Model\AdminModel;
use \Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Versioning\VersionableModelTrait;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\CMS\Event\Model;
use Joomla\CMS\Event\AbstractEvent;
use \Joomla\Database\DatabaseInterface;

/**
 * Payment model.
 *
 * @since  1.0.2
 */
class PaymentModel extends AdminModel {

    use VersionableModelTrait;

    /**
     * @var    string  The prefix to use with controller messages.
     *
     * @since  1.0.2
     */
    protected $text_prefix = 'COM_RA_TREASURER';

    /**
     * @var    string  Alias to manage history control
     *
     * @since  1.0.2
     */
    public $typeAlias = 'com_ra_treasurer.payment';

    /**
     * @var    null  Item data
     *
     * @since  1.0.2
     */
    protected $item = null;

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param   string  $type    The table type to instantiate
     * @param   string  $prefix  A prefix for the table class name. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  Table    A database object
     *
     * @since   1.0.2
     */
    public function getTable($type = 'Payment', $prefix = 'Administrator', $config = array()) {
        return parent::getTable($type, $prefix, $config);
    }

    /**
     * Method to get the record form.
     *
     * @param   array    $data      An optional array of data for the form to interogate.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  \JForm|boolean  A \JForm object on success, false on failure
     *
     * @since   1.0.2
     */
    public function getForm($data = array(), $loadData = true) {
        // Initialise variables.
        $app = Factory::getApplication();

        // Get the form.
        $form = $this->loadForm(
                'com_ra_treasurer.payment',
                'payment',
                array(
                    'control' => 'jform',
                    'load_data' => $loadData
                )
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.0.2
     */
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_ra_treasurer.edit.payment.data', array());

        if (empty($data)) {
            if ($this->item === null) {
                $this->item = $this->getItem();
            }

            $data = $this->item;
        }

        return $data;
    }

    /**
     * Method to get a single record.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  mixed    Object on success, false on failure.
     *
     * @since   1.0.2
     */
    public function getItem($pk = null) {

        if ($item = parent::getItem($pk)) {
            $bookingId = (int) $item->id;

            if ($bookingId > 0) {
                $db = $this->getDatabase();
                $query = $db->getQuery(true)
                        ->select($db->quoteName('e.title', 'event_title'))
                        ->select($db->quoteName('e.event_date'))
                        ->select($db->quoteName('p.preferred_name', 'member_name'))
                        ->from($db->quoteName('#__ra_bookings', 'b'))
                        ->join('INNER', $db->quoteName('#__ra_events', 'e')
                                . ' ON ' . $db->quoteName('e.id') . ' = ' . $db->quoteName('b.event_id'))
                        ->join('LEFT', $db->quoteName('#__ra_profiles', 'p')
                                . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('b.user_id'))
                        ->where($db->quoteName('b.id') . ' = ' . $bookingId);

                $context = $db->setQuery($query)->loadObject();

                if ($context !== null) {
                    $item->event_title = $context->event_title;
                    $item->event_date = $context->event_date;
                    $item->member_name = $context->member_name;
                }
            }
        }

        return $item;
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   Table  $table  Table Object
     *
     * @return  void
     *
     * @since   1.0.2
     */
    protected function prepareTable($table) {
        // Payment fields are held on an existing #__ra_bookings record.
    }

}
