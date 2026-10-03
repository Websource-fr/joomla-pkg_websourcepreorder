<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  mod_websourcepreorder_featured
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Websource\Component\Websourcepreorder\Administrator\Service\DisplayHelper;

/** @var array $displayData */
if (empty($item)) {
    return;
}

$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<section class="wspo-featured<?php echo $e($params->get('moduleclass_sfx', '')); ?>">
	<h2 class="wspo-featured__title"><?php echo $e($title); ?></h2>
	<div class="wspo-featured__card">
		<?php if ($item['image'] !== '') : ?>
			<a class="wspo-featured__img" href="<?php echo $e($item['url']); ?>"><img src="<?php echo $e($item['image']); ?>" alt="<?php echo $e($item['name']); ?>" loading="lazy"></a>
		<?php endif; ?>
		<div class="wspo-featured__body">
			<span class="wspo-badge"><?php echo $e($item['badge']); ?></span>
			<h3><a href="<?php echo $e($item['url']); ?>"><?php echo $e($item['name']); ?></a></h3>
			<p class="wspo-box__date"><?php echo $item['release_date'] !== '' ? Text::_('COM_WEBSOURCEPREORDER_RELEASE_ON') . ' <strong>' . $e($item['release_date']) . '</strong>' : Text::_('COM_WEBSOURCEPREORDER_RELEASE_SOON'); ?></p>
			<?php if ($item['show_countdown']) : ?>
				<div class="wspo-count" data-ts="<?php echo (int) $item['release_ts']; ?>" data-mode="<?php echo $e($item['mode']); ?>"></div>
			<?php endif; ?>
			<?php if ($item['show_progress']) : ?>
				<div class="wspo-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int) $item['progress']; ?>"><span style="width:<?php echo (int) $item['progress']; ?>%"></span></div>
			<?php endif; ?>
			<?php if ($item['price'] !== '') : ?><p class="wspo-featured__price"><?php echo $item['price']; ?></p><?php endif; ?>
			<?php if ($item['show_quota'] && $item['remaining'] !== null) : ?>
				<p><?php echo Text::sprintf('COM_WEBSOURCEPREORDER_REMAINING_N', '<strong>' . (int) $item['remaining'] . '</strong>'); ?></p>
			<?php endif; ?>
			<a class="wspo-btn" href="<?php echo $e($item['url']); ?>"><?php echo $e($cta !== '' ? $cta : $item['label']); ?></a>
		</div>
	</div>
	<?php if ($showSubscribe) : ?><?php echo DisplayHelper::layout('subscribe', ['place' => 'home']); ?><?php endif; ?>
</section>
