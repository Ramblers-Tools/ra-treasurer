<?php
/**
 * 20/08/26 created by component-creator
 * 25/08/26 CB changed columns
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Session\Session;
use \Joomla\CMS\User\UserFactoryInterface;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

$user = Factory::getApplication()->getIdentity();
$userId = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
$canEdit = $user->authorise('core.edit', 'com_ra_treasurer') && file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'bookingform.xml');
$canCheckin = $user->authorise('core.manage', 'com_ra_treasurer');
$canChange = $user->authorise('core.edit.state', 'com_ra_treasurer');
$canDelete = $user->authorise('core.delete', 'com_ra_treasurer');
$canRecordPayment = !$user->guest && $user->authorise('core.create', 'com_ra_treasurer');
$today = Factory::getDate('now', Factory::getConfig()->get('offset'))->format('Y-m-d');
$itemId = Factory::getApplication()->input->getInt('Itemid');

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_ra_tools');
$wa->useStyle('com_ra_tools.list');
?>

<?php if ($this->params->get('show_page_heading')) : ?>
    <div class="page-header">
        <h1> <?php echo $this->escape($this->params->get('page_heading')); ?> </h1>
    </div>
<?php endif; ?>
<form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post"
      name="adminForm" id="adminForm">
          <?php
          if (!empty($this->filterForm)) {
              echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
          }
          ?>
    <div class="table-responsive">
        <table class="table table-striped" id="bookingList">
            <thead>
                <tr>
                    <th class=''>
                        <?php echo HTMLHelper::_('grid.sort', 'COM_RA_TREASURER_BOOKINGS_EVENT_DATE', 'e.event_date', $listDirn, $listOrder); ?>
                    </th>
                    <th class=''>
                        <?php echo HTMLHelper::_('grid.sort', 'Event title', 'e.title', $listDirn, $listOrder); ?>
                    </th>

                    <th class=''>
                        <?php echo HTMLHelper::_('grid.sort', 'Booking date', 'a.created', $listDirn, $listOrder); ?>
                    </th>
                    <th class=''>
                        <?php echo HTMLHelper::_('grid.sort', 'Member', 'p.preferred_name', $listDirn, $listOrder); ?>
                    </th>
                    <th class=''>
                        <?php echo HTMLHelper::_('grid.sort', 'Num places', 'a.num_places', $listDirn, $listOrder); ?>
                    </th>
                    <?php if ($canRecordPayment || $canEdit || $canDelete): ?>
                        <th class="center">
                            <?php echo Text::_('Action'); ?>
                        </th>
                    <?php endif; ?>

                </tr>
            </thead>
            <tfoot>
                <tr>
                    <td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
                        <div class="pagination">
                            <?php echo $this->pagination->getPagesLinks(); ?>
                        </div>
                    </td>
                </tr>
            </tfoot>
            <tbody>
                <?php foreach ($this->items as $i => $item) : ?>
                    <tr class="row<?php echo $i % 2; ?>">
                        <td>
                            <?php
                            $date = $item->event_date;
                            echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC4')) : '-';
                            ?>
                        </td>
                        <td>
                            <?php echo $item->title; ?>
                        </td>
                        <td>
                            <?php
                            $date = $item->created;
                            echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC4')) : '-';
                            ?>
                        </td>
                        <td>
                            <?php echo $item->preferred_name; ?>
                        </td>
                        <td>
                            <?php echo $item->num_places; ?>
                        </td>
                        <?php if ($canRecordPayment || $canEdit || $canDelete): ?>
                            <td class="center">
                                <?php if ($canRecordPayment) : ?>
                                    <button type="button"
                                            class="btn btn-success btn-sm record-payment-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#recordPaymentModal"
                                            data-booking-id="<?php echo (int) $item->id; ?>"
                                            data-booking-label="<?php echo $this->escape($item->preferred_name . ' — ' . $item->title); ?>"
                                            data-amount-due="<?php echo $this->escape((string) ($item->amount_due ?? '')); ?>">
                                                <?php echo Text::_('Record Payment'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>


    <input type="hidden" name="task" value=""/>
    <input type="hidden" name="boxchecked" value="0"/>
    <input type="hidden" name="filter_order" value=""/>
    <input type="hidden" name="filter_order_Dir" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<?php if ($canRecordPayment) : ?>
    <?php
    $modalBody = '<p id="record-payment-booking" class="fw-semibold"></p>'
            . '<div class="mb-3">'
            . '<label for="record-payment-date" class="form-label">'
            . Text::_('Date paid')
            . '</label>'
            . '<input type="date" id="record-payment-date" name="date_paid" '
            . 'class="form-control" value="' . $today . '" max="' . $today . '" required>'
            . '</div>'
            . '<div class="mb-3">'
            . '<label for="record-payment-amount" class="form-label">'
            . Text::_('Amount paid')
            . '</label>'
            . '<input type="number" id="record-payment-amount" name="amount_paid" '
            . 'class="form-control" min="0.01" max="99999.99" step="0.01" '
            . 'inputmode="decimal" required>'
            . '</div>';

    $modalFooter = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'
            . Text::_('JCANCEL')
            . '</button>'
            . '<button type="submit" form="record-payment-form" class="btn btn-success">'
            . Text::_('Confirm')
            . '</button>';
    ?>
    <form action="<?php echo Route::_('index.php?option=com_ra_treasurer&task=bookings.recordPayment'); ?>"
          method="post" id="record-payment-form" class="form-validate">
              <?php
              echo HTMLHelper::_(
                      'bootstrap.renderModal',
                      'recordPaymentModal',
                      array(
                          'title' => Text::_('Record Payment'),
                          'footer' => $modalFooter,
                      ),
                      $modalBody
              );
              ?>
        <input type="hidden" name="task" value="bookings.recordPayment">
        <input type="hidden" name="booking_id" id="record-payment-booking-id" value="">
        <?php if ($itemId > 0) : ?>
            <input type="hidden" name="Itemid" value="<?php echo $itemId; ?>">
        <?php endif; ?>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <?php
    $wa->addInlineScript(
            "document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('recordPaymentModal');
                if (!modal) {
                    return;
                }

                modal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (!button) {
                        return;
                    }

                    document.getElementById('record-payment-booking-id').value = button.dataset.bookingId || '';
                    document.getElementById('record-payment-booking').textContent = button.dataset.bookingLabel || '';

                    const amount = button.dataset.amountDue || '';
                    document.getElementById('record-payment-amount').value = /^\\d+(?:\\.\\d{1,2})?$/.test(amount) ? amount : '';
                    document.getElementById('record-payment-date').value = '" . $today . "';
                });
            });"
    );
    ?>
<?php endif; ?>

<?php
if ($canDelete) {
    $wa->addInlineScript("
			jQuery(document).ready(function () {
				jQuery('.delete-button').click(deleteItem);
			});

			function deleteItem() {

				if (!confirm(\"" . Text::_('COM_RA_TREASURER_DELETE_MESSAGE') . "\")) {
					return false;
				}
			}
		", [], [], ["jquery"]);
}
?>
