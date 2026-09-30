<?php

/**
 * @package     com_ra_treasurer
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_treasurer\Administrator\View\Claimtype;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView {

    protected $state;
    protected $item;
    protected $form;

    public function display($tpl = null) {
        $this->state = $this->get('State');
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');

        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar(): void {
        Factory::getApplication()->input->set('hidemainmenu', true);

        $user = Factory::getApplication()->getIdentity();
        $isNew = empty($this->item->id);
        $canSave = $isNew
                ? $user->authorise('core.create', 'com_ra_treasurer')
                : $user->authorise('core.edit', 'com_ra_treasurer');

        ToolbarHelper::title('Claim type', 'generic');

        if ($canSave) {
            ToolbarHelper::apply('claimtype.apply', 'Apply');
            ToolbarHelper::save('claimtype.save', 'Save');
        }

        ToolbarHelper::cancel('claimtype.cancel', $isNew ? 'Cancel' : 'Close');
    }
}
