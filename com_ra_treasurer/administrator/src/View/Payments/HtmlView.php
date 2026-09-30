<?php

/**
 * 24/08/26 created by component-creator
 * 31/08/26 CB Updated buttons
 */

namespace Ramblers\Component\Ra_treasurer\Administrator\View\Payments;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use \Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * View class for a list of Payments.
 *
 * @since  1.0.2
 */
class HtmlView extends BaseHtmlView {

    protected $items;
    protected $pagination;
    protected $state;

    /**
     * Display the view
     *
     * @param   string  $tpl  Template name
     *
     * @return void
     *
     * @throws Exception
     */
    public function display($tpl = null) {
        $this->state = $this->get('State');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        $this->addToolbar();

        $this->sidebar = Sidebar::render();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   1.0.2
     */
    protected function addToolbar() {
        $state = $this->get('State');
        $canDo = ToolsHelper::getActions();

        ToolbarHelper::title(Text::_('Payments'), "generic");

        $toolbar = Toolbar::getInstance('toolbar');

        $toolbar->standardButton('nrecords')
                ->icon('fa fa-info-circle')
                ->text(number_format($this->pagination->total) . ' Records')
                ->task('')
                ->onclick('return false')
                ->listCheck(false);

        ToolbarHelper::cancel('payments.cancel', 'Return to Dashboard');

        // Set sidebar action
        Sidebar::setAction('index.php?option=com_ra_treasurer&view=payments');
    }

    /**
     * Method to order fields
     *
     * @return void
     */
    protected function getSortFields() {
        return array(
            'a.`id`' => Text::_('JGRID_HEADING_ID'),
            'a.`member_name`' => Text::_('COM_RA_TREASURER_PAYMENTS_MEMBER_NAME'),
            'a.`event_name`' => Text::_('COM_RA_TREASURER_PAYMENTS_EVENT_NAME'),
            'a.`amount_paid`' => Text::_('COM_RA_TREASURER_PAYMENTS_AMOUNT_PAID'),
            'a.`event_date`' => Text::_('COM_RA_TREASURER_PAYMENTS_EVENT_DATE'),
            'a.`created`' => Text::_('COM_RA_TREASURER_PAYMENTS_CREATED'),
            'a.`date_paid`' => Text::_('COM_RA_TREASURER_PAYMENTS_DATE_PAID'),
        );
    }

    /**
     * Check if state is set
     *
     * @param   mixed  $state  State
     *
     * @return bool
     */
    public function getState($state) {
        return isset($this->state->{$state}) ? $this->state->{$state} : false;
    }

}
