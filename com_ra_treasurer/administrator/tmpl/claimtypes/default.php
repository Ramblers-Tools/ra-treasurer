<?php

/**
 * @package     com_ra_treasurer
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

$wa = $this->document->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_ra_tools');
$wa->useStyle('com_ra_tools.admin')
        ->useScript('com_ra_tools.admin');

$user = Factory::getApplication()->getIdentity();
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
$canEdit = $user->authorise('core.edit', 'com_ra_treasurer');
$canChange = $user->authorise('core.edit.state', 'com_ra_treasurer');
?>

<form action="<?php echo Route::_('index.php?option=com_ra_treasurer&view=claimtypes'); ?>" method="post"
      name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php // echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
                <div class="clearfix"></div>
                <table class="table table-striped" id="claimtypeList">
                    <thead>
                        <tr>
                            <th class="w-1 text-center">
                                <input type="checkbox" autocomplete="off" class="form-check-input"
                                       name="checkall-toggle" value="" title="Check all"
                                       onclick="Joomla.checkAll(this)">
                            </th>
                            <th scope="col" class="w-1 text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'Status', 'a.state', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col">
                                <?php echo HTMLHelper::_('searchtools.sort', 'Description', 'a.description', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-3 d-none d-lg-table-cell">
                                <?php echo HTMLHelper::_('searchtools.sort', 'ID', 'a.id', $listDirn, $listOrder); ?>
                            </th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <td colspan="4"><?php echo $this->pagination->getListFooter(); ?></td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php foreach ($this->items as $i => $item) : ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'claimtypes.', $canChange, 'cb'); ?>
                                </td>
                                <td>
                                    <?php if ($canEdit) : ?>
                                        <a href="<?php echo Route::_('index.php?option=com_ra_treasurer&task=claimtype.edit&id=' . (int) $item->id); ?>">
                                            <?php echo $this->escape($item->description); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo $this->escape($item->description); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-lg-table-cell"><?php echo (int) $item->id; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <input type="hidden" name="list[fullorder]" value="<?php echo $this->escape($listOrder . ' ' . $listDirn); ?>">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
