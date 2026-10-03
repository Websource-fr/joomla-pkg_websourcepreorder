<?php

/**
 * @package     Websource.Websourcepreorder
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Websource\Component\Websourcepreorder\Administrator\Service\DisplayHelper;
use Websource\Component\Websourcepreorder\Administrator\Service\Settings;

$showPrice = Settings::flag('list_show_price');
$showCount = Settings::flag('list_show_countdown');
?>
<div class="wspo-list">
	<h1><?php echo htmlspecialchars($this->title); ?></h1>
	<?php if ($this->intro !== '') : ?><div class="wspo-list__intro"><?php echo \Joomla\CMS\HTML\HTMLHelper::_('content.prepare', $this->intro); ?></div><?php endif; ?>
	<?php if (!$this->groups) : ?>
		<p class="wspo-list__empty"><?php echo htmlspecialchars($this->empty); ?></p>
	<?php endif; ?>
	<?php foreach ($this->groups as $group) : ?>
		<?php if ($group['label'] !== '') : ?><h2 class="wspo-list__month"><?php echo htmlspecialchars($group['label']); ?></h2><?php endif; ?>
		<div class="wspo-list__grid">
			<?php foreach ($group['items'] as $it) : ?>
				<?php echo DisplayHelper::layout('card', ['item' => $it, 'showPrice' => $showPrice, 'showCountdown' => $showCount]); ?>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>
	<?php echo DisplayHelper::layout('subscribe', ['place' => 'list']); ?>
</div>
