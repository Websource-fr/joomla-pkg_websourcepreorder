<?php

/**
 * @package     Websource.Websourcepreorder
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 *
 * Layout surchargeable : templates/<template>/html/layouts/com_websourcepreorder/
subscribe.php
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Websource\Component\Websourcepreorder\Administrator\Service\Settings;
use Websource\Component\Websourcepreorder\Administrator\Service\SubscriberService;

/** @var array $displayData */
$place   = preg_replace('/[^a-z0-9_-]/i', '', (string) ($displayData['place'] ?? 'site'));
$title   = (string) ($displayData['title'] ?? '') !== '' ? $displayData['title'] : (Settings::text('sub_title') ?: Text::_('COM_WEBSOURCEPREORDER_SUB_TITLE'));
$text    = (string) ($displayData['text'] ?? '') !== '' ? $displayData['text'] : (Settings::text('sub_text') ?: Text::_('COM_WEBSOURCEPREORDER_SUB_TEXT'));
$consent = Settings::text('sub_consent') ?: Text::_('COM_WEBSOURCEPREORDER_SUB_CONSENT');
$uid     = 'wspo-sub-' . $place . '-' . substr(md5(uniqid('', true)), 0, 6);
$e       = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<div class="wspo-sub wspo-sub--<?php echo $e($place); ?>">
	<p class="wspo-sub__title"><?php echo $e($title); ?></p>
	<p class="wspo-sub__text"><?php echo $e($text); ?></p>
	<form class="wspo-sub__form" method="post" action="<?php echo $e(Route::_('index.php?option=com_websourcepreorder&task=subscribe.subscribe')); ?>" data-wspo-sub novalidate>
		<div class="wspo-sub__row">
			<label class="visually-hidden" for="<?php echo $uid; ?>-email"><?php echo Text::_('COM_WEBSOURCEPREORDER_SUB_EMAIL'); ?></label>
			<input type="email" id="<?php echo $uid; ?>-email" name="wspo_email" required placeholder="<?php echo $e(Text::_('COM_WEBSOURCEPREORDER_SUB_EMAIL')); ?>" autocomplete="email">
			<button type="submit" class="wspo-btn"><?php echo Text::_('COM_WEBSOURCEPREORDER_SUB_BUTTON'); ?></button>
		</div>
		<label class="wspo-sub__consent" for="<?php echo $uid; ?>-consent"><input type="checkbox" id="<?php echo $uid; ?>-consent" name="wspo_consent" value="1" required> <span><?php echo $e($consent); ?></span></label>
		<div class="wspo-hp" aria-hidden="true"><label>Website<input type="text" name="wspo_hp" tabindex="-1" autocomplete="off" value=""></label></div>
		<input type="hidden" name="wspo_ts" value="<?php echo $e(SubscriberService::timeToken(time())); ?>">
		<input type="hidden" name="wspo_place" value="<?php echo $e($place); ?>">
		<?php echo HTMLHelper::_('form.token'); ?>
		<p class="wspo-sub__msg" role="status" aria-live="polite"></p>
	</form>
</div>
