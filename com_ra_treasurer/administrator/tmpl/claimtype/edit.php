<?php

/**
 * @package     com_ra_treasurer
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$this->document->getWebAssetManager()
        ->useScript('keepalive')
        ->useScript('form.validate');
?>

<form
    action="<?php echo Route::_('index.php?option=com_ra_treasurer&view=claimtype&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post"
    name="adminForm"
    id="claimtype-form"
    class="form-validate form-horizontal">
    <div class="row">
        <div class="col-lg-8">
            <fieldset class="adminform">
                <?php echo $this->form->renderField('id'); ?>
                <?php echo $this->form->renderField('description'); ?>
                <?php echo $this->form->renderField('state'); ?>
                <?php echo $this->form->getInput('ordering'); ?>
            </fieldset>
        </div>
    </div>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
