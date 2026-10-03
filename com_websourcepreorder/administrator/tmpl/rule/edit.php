<?php

/**
 * @package     Websource.Websourcepreorder
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
?>
<form action="<?php echo Route::_('index.php?option=com_websourcepreorder&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="rule-form" class="form-validate">
	<?php echo HTMLHelper::_('uitab.startTabSet', 'wspoRule', ['active' => 'general', 'recall' => true, 'breakpoint' => 768]); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'wspoRule', 'general', Text::_('COM_WEBSOURCEPREORDER_TAB_RULE_GENERAL')); ?>
	<div class="row"><div class="col-lg-8"><?php echo $this->form->renderFieldset('general'); ?></div></div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'wspoRule', 'pricing', Text::_('COM_WEBSOURCEPREORDER_TAB_RULE_PRICING')); ?>
	<div class="row"><div class="col-lg-8"><?php echo $this->form->renderFieldset('pricing'); ?></div></div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'wspoRule', 'display', Text::_('COM_WEBSOURCEPREORDER_TAB_RULE_DISPLAY')); ?>
	<div class="row"><div class="col-lg-8"><?php echo $this->form->renderFieldset('display'); ?></div></div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
