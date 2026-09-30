<?php

/**
 * @package     com_ra_treasurer
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_treasurer\Administrator\View\Claimtypes;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView {

    protected $items;
    protected $pagination;
    protected $state;
    protected $filterForm;
    protected $activeFilters;

    public function display($tpl = null) {
        $this->state = $this->get('State');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar(): void {
        $user = Factory::getApplication()->getIdentity();
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title('Claim types', 'generic');

        if ($user->authorise('core.create', 'com_ra_treasurer')) {
            $toolbar->addNew('claimtype.add', 'New');
        }

        $toolbar->standardButton('nrecords')
                ->icon('fa fa-info-circle')
                ->text(number_format($this->pagination->total) . ' Records')
                ->task('')
                ->onclick('return false')
                ->listCheck(false);

        $toolbar->standardButton('dashboard')
                ->icon('icon-arrow-left')
                ->text('Return to Dashboard')
                ->task('')
                ->onclick("window.location.href='index.php?option=com_ra_tools&view=dashboard'; return false")
                ->listCheck(false);
    }
}
