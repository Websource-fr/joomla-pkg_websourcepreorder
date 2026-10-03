<?php

/**
 * @package     Websource.Websourcepreorder
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Websource\Component\Websourcepreorder\Administrator\Service\Env;

HTMLHelper::_('behavior.multiselect');
$badge = [0 => 'bg-info', 1 => 'bg-success', 2 => 'bg-secondary'];
?>
<form action="<?php echo Route::_('index.php?option=com_websourcepreorder&view=bookings'); ?>" method="post" name="adminForm" id="adminForm">
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	<?php if (empty($this->items)) : ?>
		<div class="alert alert-info"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
	<?php else : ?>
		<table class="table table-striped">
			<thead><tr>
				<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_ORDER'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_CUSTOMER'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_PRODUCT'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_QTY'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_RELEASE'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_STATE'); ?></th>
				<th class="w-1"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ($this->items as $i => $item) : ?>
				<tr>
					<td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
					<td><?php echo htmlspecialchars((string) $item->order_number); ?><br><small class="text-muted"><?php echo htmlspecialchars((string) $item->order_status); ?></small></td>
					<td><?php echo htmlspecialchars($item->customer_name !== '' ? $item->customer_name : '—'); ?><br><small><?php echo htmlspecialchars($item->email); ?></small></td>
					<td><?php echo htmlspecialchars($item->product_name); ?></td>
					<td><?php echo (int) $item->quantity; ?></td>
					<td><?php echo htmlspecialchars(Env::formatDate($item->release_date) ?: '—'); ?></td>
					<td><span class="badge <?php echo $badge[(int) $item->status] ?? 'bg-secondary'; ?>"><?php echo Text::_('COM_WEBSOURCEPREORDER_BSTATUS_' . (int) $item->status); ?></span></td>
					<td><?php echo (int) $item->id; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php echo $this->pagination->getListFooter(); ?>
	<?php endif; ?>
	<input type="hidden" name="task" value="">
	<input type="hidden" name="boxchecked" value="0">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
