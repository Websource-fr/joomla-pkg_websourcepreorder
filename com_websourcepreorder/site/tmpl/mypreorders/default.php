<?php

/**
 * @package     Websource.Websourcepreorder
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Websource\Component\Websourcepreorder\Administrator\Service\Env;
use Websource\Component\Websourcepreorder\Administrator\Service\HikashopBridge;
?>
<div class="wspo-mine">
	<h1><?php echo Text::_('COM_WEBSOURCEPREORDER_MYPREORDERS_TITLE'); ?></h1>
	<?php if (!$this->items) : ?>
		<p><?php echo Text::_('COM_WEBSOURCEPREORDER_MYPREORDERS_EMPTY'); ?></p>
	<?php else : ?>
		<table class="table table-striped">
			<thead><tr>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_ORDER'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_PRODUCT'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_QTY'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_RELEASE'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_STATE'); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ($this->items as $b) : ?>
				<tr>
					<td><?php echo htmlspecialchars((string) $b->order_number); ?></td>
					<td><a href="<?php echo htmlspecialchars(HikashopBridge::productUrl((int) $b->product_id)); ?>"><?php echo htmlspecialchars($b->product_name); ?></a></td>
					<td><?php echo (int) $b->quantity; ?></td>
					<td><?php echo htmlspecialchars(Env::formatDate($b->release_date) ?: '—'); ?></td>
					<td><?php echo Text::_('COM_WEBSOURCEPREORDER_BSTATUS_FRONT_' . (int) $b->status); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
