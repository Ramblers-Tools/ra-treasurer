<?php

/**
 * @package     com_ra_treasurer
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>

<dl class="row">
    <dt class="col-sm-3">Description</dt>
    <dd class="col-sm-9"><?php echo $this->escape((string) $this->item->description); ?></dd>
    <dt class="col-sm-3">Status</dt>
    <dd class="col-sm-9"><?php echo (int) $this->item->state === 1 ? 'Published' : 'Unpublished'; ?></dd>
    <dt class="col-sm-3">ID</dt>
    <dd class="col-sm-9"><?php echo (int) $this->item->id; ?></dd>
</dl>
