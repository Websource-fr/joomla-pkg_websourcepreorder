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
use Websource\Component\Websourcepreorder\Administrator\Service\RuleService;

HTMLHelper::_('behavior.multiselect');
$badge = [0 => 'bg-secondary', 1 => 'bg-success', 2 => 'bg-dark'];
?>
<form action="<?php echo Route::_('index.php?option=com_websourcepreorder&view=rules'); ?>" method="post" name="adminForm" id="adminForm">
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	<?php if (empty($this->items)) : ?>
		<div class="alert alert-info"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
	<?php else : ?>
		<table class="table table-striped" id="rulesList">
			<thead><tr>
				<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_PRODUCT'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_STATE'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_RELEASE'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_PRICE'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_QUOTA'); ?></th>
				<th class="w-1"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ($this->items as $i => $item) : ?>
				<tr>
					<td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
					<td><a href="<?php echo Route::_('index.php?option=com_websourcepreorder&task=rule.edit&id=' . (int) $item->id); ?>"><?php echo htmlspecialchars($item->product_label); ?></a>
						<?php if (!$item->published) : ?><span class="badge bg-warning text-dark"><?php echo Text::_('JDISABLED'); ?></span><?php endif; ?></td>
					<td><span class="badge <?php echo $badge[$item->effective_state]; ?>"><?php echo Text::_('COM_WEBSOURCEPREORDER_STATE_' . $item->effective_state); ?></span></td>
					<td><?php echo htmlspecialchars(Env::formatDate($item->date_release) ?: '—'); ?></td>
					<td><?php echo (int) $item->price_mode ? Text::_('COM_WEBSOURCEPREORDER_PRICE_MODE_' . (int) $item->price_mode) . ' : ' . rtrim(rtrim(number_format((float) $item->price_value, 2, '.', ''), '0'), '.') : '—'; ?></td>
					<td><?php echo (int) $item->quota ? (int) $item->booked . ' / ' . (int) $item->quota : (int) $item->booked . ' / ∞'; ?></td>
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
