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
?>
<form action="<?php echo Route::_('index.php?option=com_websourcepreorder&view=subscribers'); ?>" method="post" name="adminForm" id="adminForm">
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	<?php if (empty($this->items)) : ?>
		<div class="alert alert-info"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
	<?php else : ?>
		<table class="table table-striped">
			<thead><tr>
				<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_FIELD_EMAIL'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_CONFIRMED'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_SOURCE'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_LANG'); ?></th>
				<th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_DATE'); ?></th>
				<th class="w-1"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ($this->items as $i => $item) : ?>
				<tr>
					<td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
					<td><a href="<?php echo Route::_('index.php?option=com_websourcepreorder&task=subscriber.edit&id=' . (int) $item->id); ?>"><?php echo htmlspecialchars($item->email); ?></a></td>
					<td><span class="badge <?php echo $item->confirmed ? 'bg-success' : 'bg-warning text-dark'; ?>"><?php echo Text::_('COM_WEBSOURCEPREORDER_CONFIRMED_' . (int) $item->confirmed); ?></span></td>
					<td><?php echo htmlspecialchars($item->source); ?></td>
					<td><?php echo htmlspecialchars($item->lang); ?></td>
					<td><?php echo htmlspecialchars(Env::formatDate($item->created)); ?></td>
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
