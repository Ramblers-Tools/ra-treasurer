<?php
/**
 * @package     com_ra_treasurer
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
        ->useScript('form.validate');

$showDate = static function ($value, string $format = 'DATE_FORMAT_LC4'): string {
    if ($value === null || $value === '' || str_starts_with((string) $value, '0000-00-00')) {
        return Text::_('COM_RA_TREASURER_NOT_SUPPLIED');
    }

    return HTMLHelper::_('date', $value, Text::_($format));
};
?>

<form
    action="<?php echo Route::_('index.php?option=com_ra_treasurer&view=payment&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post"
    name="adminForm"
    id="payment-form"
    class="form-validate form-horizontal">

    <div class="row">
        <div class="col-lg-8">
            <fieldset class="adminform">

                <dl class="row">
                    <dt class="col-sm-3"><?php echo Text::_('Booking Ref'); ?></dt>
                    <dd class="col-sm-9"><?php echo (int) $this->item->id; ?></dd>

                    <dt class="col-sm-3"><?php echo Text::_('Event date'); ?></dt>
                    <dd class="col-sm-9"><?php echo $this->escape($showDate($this->item->event_date ?? null)); ?></dd>

                    <dt class="col-sm-3"><?php echo Text::_('Event title'); ?></dt>
                    <dd class="col-sm-9"><?php echo $this->escape((string) ($this->item->event_title ?? '')); ?></dd>

                    <dt class="col-sm-3"><?php echo Text::_('Member name'); ?></dt>
                    <dd class="col-sm-9"><?php echo $this->escape((string) ($this->item->member_name ?? '')); ?></dd>

                </dl>

<?php
echo $this->form->renderField('date_paid');
echo $this->form->renderField('amount_paid');
echo $this->form->renderField('payment_created');
echo $this->form->renderField('payment_created_by');
echo $this->form->renderField('payment_modified');
echo $this->form->renderField('payment_modified_by');
echo '<div style="background-color:powderblue;">This function is intended to correct data entry errors, '
 . 'but if you have issued a refund, you may set the amount paid to zero.</div>';
?>
            </fieldset>
        </div>
    </div>

<?php echo $this->form->getInput('id'); ?>
    <?php echo $this->form->getInput('state'); ?>
    <?php echo $this->form->getInput('checked_out'); ?>
    <?php echo $this->form->getInput('checked_out_time'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
