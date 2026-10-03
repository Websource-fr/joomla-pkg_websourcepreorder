<?php

/**
 * @package     Websource.Websourcepreorder
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 *
 * Layout surchargeable : templates/<template>/html/layouts/com_websourcepreorder/
card.php
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var array $displayData */
$it = $displayData['item'];
$e  = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<article class="wspo-card">
	<a class="wspo-card__img" href="<?php echo $e($it['url']); ?>">
		<?php if ($it['image'] !== '') : ?><img src="<?php echo $e($it['image']); ?>" alt="<?php echo $e($it['name']); ?>" loading="lazy"><?php endif; ?>
		<span class="wspo-badge"><?php echo $e($it['badge']); ?></span>
	</a>
	<div class="wspo-card__body">
		<h3><a href="<?php echo $e($it['url']); ?>"><?php echo $e($it['name']); ?></a></h3>
		<?php if (!empty($displayData['showPrice']) && $it['price'] !== '') : ?>
			<p class="wspo-card__price"><?php echo $it['price']; ?><?php if ($it['saving'] > 0) : ?> <span class="wspo-save">-<?php echo (int) $it['saving']; ?>%</span><?php endif; ?></p>
		<?php endif; ?>
		<p class="wspo-card__date"><?php echo $it['release_date'] !== '' ? Text::_('COM_WEBSOURCEPREORDER_RELEASE_ON') . ' <strong>' . $e($it['release_date']) . '</strong>' : Text::_('COM_WEBSOURCEPREORDER_RELEASE_SOON'); ?></p>
		<?php if (!empty($displayData['showCountdown']) && $it['show_countdown']) : ?>
			<div class="wspo-count wspo-count--small" data-ts="<?php echo (int) $it['release_ts']; ?>" data-mode="<?php echo $e($it['mode']); ?>"></div>
		<?php endif; ?>
		<?php if ($it['show_quota'] && $it['remaining'] !== null) : ?>
			<p class="wspo-card__stock"><?php echo Text::sprintf('COM_WEBSOURCEPREORDER_REMAINING_N', '<strong>' . (int) $it['remaining'] . '</strong>'); ?></p>
		<?php endif; ?>
		<a class="wspo-btn" href="<?php echo $e($it['url']); ?>"><?php echo $e($it['label']); ?></a>
	</div>
</article>
