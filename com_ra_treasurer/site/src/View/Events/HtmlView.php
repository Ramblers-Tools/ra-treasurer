<?php

/**
 * @version    CVS: 1.0.2
 * @package    Com_Ra_treasurer
 * @author     Charlie Bigley <charlie@ramblers.tools>
 * @copyright  Ramblers Tools
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 27/08/26 CB added ToolsHelper
 */

namespace Ramblers\Component\Ra_treasurer\Site\View\Events;
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
/**
 * View class for a list of Ra_treasurer.
 *
 * @since  1.0.2
 */
class HtmlView extends BaseHtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $params;
	protected $toolsHelper;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$app = Factory::getApplication();

		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->params = $app->getParams('com_ra_treasurer');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->toolsHelper = new ToolsHelper;

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode("\n", $errors));
		}

		$this->_prepareDocument();
		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function _prepareDocument()
	{
		$app   = Factory::getApplication();
		$menus = $app->getMenu();
		$title = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_RA_TREASURER_DEFAULT_PAGE_TITLE'));
		}

		$title = $this->params->get('page_title', '');

		if (empty($title))
		{
			$title = $app->get('sitename');
		}
		elseif ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
		}

		$this->document->setTitle($title);

		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
		
	}

	/**
	 * Check if state is set
	 *
	 * @param   mixed  $state  State
	 *
	 * @return bool
	 */
	public function getState($state)
	{
		return isset($this->state->{$state}) ? $this->state->{$state} : false;
	}

	public function numConfirmed($event_id) {
		$sql = 'SELECT COUNT(*) FROM #__ra_bookings AS b ';
		$sql .= 'INNER JOIN #__ra_events AS a ON a.id = b.event_id ';
		$sql .= 'WHERE b.state = 1 AND a.id = ' . (int) $event_id;
		return $this->toolsHelper->getValue($sql);
	}

	public function totPaid($event_id) {
		$sql = 'SELECT SUM(b.amount_paid) FROM #__ra_bookings AS b ';
		$sql .= 'INNER JOIN #__ra_events AS a ON a.id = b.event_id ';
		$sql .= 'WHERE b.state = 1 AND a.id = ' . (int) $event_id;
		return $this->toolsHelper->getValue($sql);
	}
}
