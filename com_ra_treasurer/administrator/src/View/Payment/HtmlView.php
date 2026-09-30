<?php

/**
 * 24/08/26 created by component-creator
 */

namespace Ramblers\Component\Ra_treasurer\Administrator\View\Payment;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * View class for a single Payment.
 *
 * @since  1.0.2
 */
class HtmlView extends BaseHtmlView {

    protected $state;
    protected $item;
    protected $form;

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
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }
        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return void
     *
     * @throws Exception
     */
    protected function addToolbar() {
        Factory::getApplication()->input->set('hidemainmenu', true);

        $user = Factory::getApplication()->getIdentity();
        $isNew = ($this->item->id == 0);

        if (isset($this->item->checked_out)) {
            $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
        } else {
            $checkedOut = false;
        }

        $canDo = ToolsHelper::getActions();

        ToolbarHelper::title(Text::_('Payment for booking'), "generic");

        // Payments are edits to existing booking records; they are not created here.
        if (!$isNew && !$checkedOut && $canDo->get('core.edit')) {
            ToolbarHelper::apply('payment.apply', 'JTOOLBAR_APPLY');
            ToolbarHelper::save('payment.save', 'JTOOLBAR_SAVE');
        }

        if (empty($this->item->id)) {
            ToolbarHelper::cancel('payment.cancel', 'JTOOLBAR_CANCEL');
        } else {
            ToolbarHelper::cancel('payment.cancel', 'JTOOLBAR_CLOSE');
        }
    }

}
