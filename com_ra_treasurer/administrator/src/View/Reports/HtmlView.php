<?php

/**
 * 20/08/26 created by component-creator
 */

namespace Ramblers\Component\Ra_treasurer\Administrator\View\Reports;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * View class for a list of Reports.
 *
 * @since  1.0.0
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

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        $this->sidebar = Sidebar::render();
        parent::display($tpl);
    }


    /**
     * Method to order fields
     *
     * @return void
     */
    protected function getSortFields() {
        return array(
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
