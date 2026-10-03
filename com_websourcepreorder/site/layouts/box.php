<?php

/**
 * @package     Websource.Websourcepreorder
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 *
 * Layout surchargeable : templates/<template>/html/layouts/com_websourcepreorder/
box.php
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var array $displayData */
$b = $displayData['box'];
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<div class="wspo-box" data-wspo-box data-wspo-product="<?php echo (int) $b['rule']->product_id; ?>" data-wspo-label="<?php echo $e($b['label']); ?>">
	<div class="wspo-box__head">
		<span class="wspo-badge"><?php echo $e($b['badge']); ?></span>
		<?php if ($b['release_date'] !== '') : ?>
			<span class="wspo-box__date"><?php echo Text::_('COM_WEBSOURCEPREORDER_RELEASE_ON'); ?> <strong><?php echo $e($b['release_date']); ?></strong></span>
		<?php else : ?>
			<span class="wspo-box__date"><?php echo Text::_('COM_WEBSOURCEPREORDER_RELEASE_SOON'); ?></span>
		<?php endif; ?>
	</div>
	<?php if ($b['show_countdown']) : ?>
		<div class="wspo-count" data-ts="<?php echo (int) $b['release_ts']; ?>" data-mode="<?php echo $e($b['mode']); ?>" aria-live="off"></div>
	<?php endif; ?>
	<?php if ($b['show_progress']) : ?>
		<div class="wspo-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int) $b['progress']; ?>"><span style="width:<?php echo (int) $b['progress']; ?>%"></span></div>
	<?php endif; ?>
	<?php if ($b['message'] !== '') : ?><div class="wspo-box__msg"><?php echo $b['message']; ?></div><?php endif; ?>
	<ul class="wspo-box__facts">
		<?php if ($b['show_quota'] && $b['remaining'] !== null) : ?>
			<li><?php echo Text::sprintf('COM_WEBSOURCEPREORDER_REMAINING_N', '<strong>' . (int) $b['remaining'] . '</strong>'); ?></li>
		<?php endif; ?>
		<?php if ((int) $b['rule']->max_per_customer > 0) : ?>
			<li><?php echo Text::sprintf('COM_WEBSOURCEPREORDER_LIMIT_CUSTOMER_N', (int) $b['rule']->max_per_customer); ?></li>
		<?php endif; ?>
		<?php if ((int) $b['rule']->max_per_order > 0) : ?>
			<li><?php echo Text::sprintf('COM_WEBSOURCEPREORDER_LIMIT_ORDER_N', (int) $b['rule']->max_per_order); ?></li>
		<?php endif; ?>
		<?php if ($b['saving'] > 0) : ?>
			<li><?php echo Text::sprintf('COM_WEBSOURCEPREORDER_SAVING_N', '<strong>-' . (int) $b['saving'] . ' %</strong>'); ?></li>
		<?php endif; ?>
	</ul>
</div>
