<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  mod_websourcepreorder_ticker
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var array $displayData */
if (empty($items)) {
    return;
}

$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<div class="wspo-ticker<?php echo $e($params->get('moduleclass_sfx', '')); ?>" style="--wspo-speed:<?php echo (int) $speed; ?>s" role="region" aria-label="<?php echo $e(Text::_('MOD_WEBSOURCEPREORDER_TICKER_ARIA')); ?>">
	<div class="wspo-ticker__track">
		<?php for ($loop = 1; $loop <= 2; $loop++) : ?>
			<ul class="wspo-ticker__list"<?php echo $loop === 2 ? ' aria-hidden="true"' : ''; ?>>
				<?php foreach ($items as $it) : ?>
					<li class="wspo-ticker__item">
						<a href="<?php echo $e($it['url']); ?>"<?php echo $loop === 2 ? ' tabindex="-1"' : ''; ?>>
							<strong><?php echo $e($prefix); ?></strong> <?php echo $e($it['name']); ?>
							<?php if ($it['release_ts']) : ?><span class="wspo-ticker__left" data-ts="<?php echo (int) $it['release_ts']; ?>"></span><?php endif; ?>
							<?php if ($showPrice && $it['price'] !== '') : ?><span class="wspo-ticker__price"><?php echo strip_tags($it['price']); ?></span><?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endfor; ?>
	</div>
</div>
