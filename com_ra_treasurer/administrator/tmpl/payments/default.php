<?php
/**
 * @version    CVS: 1.0.2
 * @package    Com_Ra_treasurer
 * @author     Charlie Bigley <charlie@ramblers.tools>
 * @copyright  Ramblers Tools
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_ra_tools');
$wa->useStyle('com_ra_tools.admin')
        ->useScript('com_ra_tools.admin');

$user = Factory::getApplication()->getIdentity();
$userId = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
?>

<form action="<?php echo Route::_('index.php?option=com_ra_treasurer&view=payments'); ?>" method="post"
      name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

                <div class="clearfix"></div>
                <table class="table table-striped" id="paymentList">
                    <thead>
                        <tr>
                            <th class=''>
                                <?php echo HTMLHelper::_('grid.sort', 'Date paid', 'a.date_paid', $listDirn, $listOrder); ?>
                            </th>
                            <th class=''>
                                <?php echo HTMLHelper::_('grid.sort', 'Payment created', 'a.payment_created', $listDirn, $listOrder); ?>
                            </th>

                            <th class=''>
                                <?php echo HTMLHelper::_('grid.sort', 'Amount paid', 'a.amount_paid', $listDirn, $listOrder); ?>
                            </th>
                            <th class=''>
                                <?php echo HTMLHelper::_('grid.sort', 'Event date', 'e.event_date', $listDirn, $listOrder); ?>
                            </th>

                            <th class=''>
                                <?php echo HTMLHelper::_('grid.sort', 'Event title', 'e.title', $listDirn, $listOrder); ?>
                            </th>

                            <th class=''>
                                <?php echo HTMLHelper::_('grid.sort', 'Member', 'p.preferred_name', $listDirn, $listOrder); ?>
                            </th>

                            <th class=''>
                                <?php echo HTMLHelper::_('grid.sort', 'Ref ID', 'a.id', $listDirn, $listOrder); ?>
                            </th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
                                <?php echo $this->pagination->getListFooter(); ?>
                            </td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php
                        foreach ($this->items as $i => $item) :
                            $canCreate = $user->authorise('core.create', 'com_ra_treasurer');
                            $canEdit = $user->authorise('core.edit', 'com_ra_treasurer');
                            $canCheckin = $user->authorise('core.manage', 'com_ra_treasurer');
                            $canChange = $user->authorise('core.edit.state', 'com_ra_treasurer');
                            ?>
                            <tr class="row<?php echo $i % 2; ?>" data-draggable-group='1' data-transition>
                                <?php
                                echo '<td>' . $item->date_paid . '</td>';
                                echo '<td>';
                                $date = $item->payment_created;
                                echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC2')) : '-';
                                echo '</td>';
                                echo '<td>';
                                if ($canEdit) {
                                    $editUrl = Route::_('index.php?option=com_ra_treasurer&task=payment.edit&id=' . (int) $item->id);
                                    echo '<a href="' . $editUrl . '">' . $this->escape($item->amount_paid) . '</a>';
                                } else {
                                    echo $this->escape($item->amount_paid);
                                }
                                echo '</td>';
                                echo '<td>';
                                $date = $item->event_date;
                                echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC1')) : '-';
                                echo '</td>';
                                echo '<td>' . $item->title . '</td>';
                                echo '<td>' . $item->preferred_name . '</td>';
                                echo '<td>' . $item->id . '</td>';
                                ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <input type="hidden" name="task" value=""/>
                <input type="hidden" name="boxchecked" value="0"/>
                <input type="hidden" name="list[fullorder]" value="<?php echo $listOrder; ?> <?php echo $listDirn; ?>"/>
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
