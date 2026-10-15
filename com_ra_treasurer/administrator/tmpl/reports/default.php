<?php
/**
 * 12/10/26 CB created from com ra_events
 */
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

$toolsHelper = new ToolsHelper;
ToolBarHelper::title('Treasurer Reports');

// Import CSS
$this->wa = $this->document->getWebAssetManager();
$this->wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

$back = 'administrator/index.php?option=com_ra_tools&view=dashboard';
$breadcrumbs = $toolsHelper->buildLink('administrator/index.php', 'Home Dashboard');
$breadcrumbs .= '>' . $toolsHelper->buildLink($back, 'RA Dashboard');
echo $breadcrumbs;

$reports = [
    'Show Guests' => 'administrator/index.php?option=com_ra_treasurer&task=reports.showGuests',

];
?>

<form action="<?php echo Route::_('index.php?option=com_ra_treasurer&view=reports'); ?>" method="post" name="reportsForm" id="reportsForm">
    <div id="j-main-container" class="span10">
        <div class="clearfix"> </div>
        <?php
        echo '<div class="dashboard-grid">';
        echo $toolsHelper->buildDashboardReportBlock('Treasurers reports', $reports);
        echo '</div>';

        echo $toolsHelper->backButton($back);
        ?>
        <input type="hidden" name="task" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</div>
</form>
<?php
