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
use Joomla\CMS\Session\Session;
use Websource\Component\Websourcepreorder\Administrator\Service\Env;
use Websource\Component\Websourcepreorder\Administrator\Service\HikashopBridge;

$s     = $this->stats;
$token = Session::getFormToken();
$fmt   = static function ($ts) {
    return $ts ? HTMLHelper::date(gmdate('Y-m-d H:i:s', $ts), Text::_('DATE_FORMAT_LC5'), 'UTC') . ' UTC' : Text::_('COM_WEBSOURCEPREORDER_NEVER');
};
?>
<div class="wspo-admin">
	<?php if (!$this->hikashop) : ?>
		<div class="alert alert-danger"><?php echo Text::_('COM_WEBSOURCEPREORDER_NO_HIKASHOP'); ?></div>
	<?php endif; ?>

	<div class="wspo-cards">
		<a class="wspo-card" href="<?php echo Route::_('index.php?option=com_websourcepreorder&view=rules'); ?>"><b><?php echo (int) $s['rules_running']; ?></b><span><?php echo Text::_('COM_WEBSOURCEPREORDER_STATE_1'); ?></span></a>
		<a class="wspo-card" href="<?php echo Route::_('index.php?option=com_websourcepreorder&view=rules&filter[state]=0'); ?>"><b><?php echo (int) $s['rules_pending']; ?></b><span><?php echo Text::_('COM_WEBSOURCEPREORDER_STATE_0'); ?></span></a>
		<a class="wspo-card" href="<?php echo Route::_('index.php?option=com_websourcepreorder&view=rules&filter[state]=2'); ?>"><b><?php echo (int) $s['rules_ended']; ?></b><span><?php echo Text::_('COM_WEBSOURCEPREORDER_STATE_2'); ?></span></a>
		<a class="wspo-card" href="<?php echo Route::_('index.php?option=com_websourcepreorder&view=bookings'); ?>"><b><?php echo (int) $s['units']; ?></b><span><?php echo Text::_('COM_WEBSOURCEPREORDER_DASH_UNITS'); ?> (<?php echo (int) $s['bookings']; ?>)</span></a>
		<a class="wspo-card" href="<?php echo Route::_('index.php?option=com_websourcepreorder&view=subscribers'); ?>"><b><?php echo (int) $s['subs_confirmed']; ?></b><span><?php echo Text::_('COM_WEBSOURCEPREORDER_DASH_SUBS'); ?> (+<?php echo (int) $s['subs_pending']; ?>)</span></a>
	</div>

	<div class="row">
		<div class="col-lg-7">
			<h2><?php echo Text::_('COM_WEBSOURCEPREORDER_DASH_NEXT'); ?></h2>
			<?php if (!$s['next']) : ?>
				<p class="text-muted"><?php echo Text::_('COM_WEBSOURCEPREORDER_DASH_NONE'); ?> <a href="<?php echo Route::_('index.php?option=com_websourcepreorder&task=rule.add'); ?>"><?php echo Text::_('COM_WEBSOURCEPREORDER_RULE_NEW'); ?></a></p>
			<?php else : ?>
				<table class="table table-striped">
					<thead><tr><th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_PRODUCT'); ?></th><th><?php echo Text::_('COM_WEBSOURCEPREORDER_COL_RELEASE'); ?></th></tr></thead>
					<tbody>
					<?php foreach ($s['next'] as $r) : ?>
						<tr>
							<td><a href="<?php echo Route::_('index.php?option=com_websourcepreorder&task=rule.edit&id=' . (int) $r->id); ?>"><?php echo htmlspecialchars(HikashopBridge::productName((int) $r->product_id)); ?></a></td>
							<td><?php echo htmlspecialchars(Env::formatDate($r->date_release) ?: '—'); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<div class="col-lg-5">
			<h2><?php echo Text::_('COM_WEBSOURCEPREORDER_DASH_TOOLS'); ?></h2>
			<p>
				<a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_websourcepreorder&task=rule.add'); ?>"><?php echo Text::_('COM_WEBSOURCEPREORDER_RULE_NEW'); ?></a>
				<a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_websourcepreorder&view=settings'); ?>"><?php echo Text::_('COM_WEBSOURCEPREORDER_SETTINGS_TITLE'); ?></a>
			</p>
			<p>
				<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_websourcepreorder&task=tools.sync&' . $token . '=1'); ?>"><?php echo Text::_('COM_WEBSOURCEPREORDER_DASH_SYNC'); ?></a>
				<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_websourcepreorder&task=tools.digest&' . $token . '=1'); ?>" onclick="return confirm('<?php echo Text::_('COM_WEBSOURCEPREORDER_DASH_DIGEST_CONFIRM', true); ?>');"><?php echo Text::_('COM_WEBSOURCEPREORDER_DASH_DIGEST'); ?></a>
			</p>
			<dl class="wspo-dl">
				<dt><?php echo Text::_('COM_WEBSOURCEPREORDER_DASH_LAST_SYNC'); ?></dt><dd><?php echo $fmt($s['last_sync']); ?></dd>
				<dt><?php echo Text::_('COM_WEBSOURCEPREORDER_DASH_LAST_DIGEST'); ?></dt><dd><?php echo $fmt($s['digest_last']); ?><?php echo $s['digest_run'] ? ' — ' . Text::_('COM_WEBSOURCEPREORDER_DASH_DIGEST_RUNNING') : ''; ?></dd>
				<dt><?php echo Text::_('COM_WEBSOURCEPREORDER_DASH_CRON_URL'); ?></dt><dd><input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($this->cronUrl); ?>" onclick="this.select()"></dd>
			</dl>
		</div>
	</div>
	<?php
	$user = \Joomla\CMS\Factory::getApplication()->getIdentity();
	// Encart accompagnement : administration uniquement, pour les utilisateurs ayant core.admin ou core.manage.
	if (\Joomla\CMS\Factory::getApplication()->isClient('administrator') && ($user->authorise('core.admin', 'com_websourcepreorder') || $user->authorise('core.manage', 'com_websourcepreorder'))) {
		echo (new \Joomla\CMS\Layout\FileLayout('websource_support', JPATH_ADMINISTRATOR . '/components/com_websourcepreorder/layouts'))->render([
			'slug'    => \Websource\Component\Websourcepreorder\Administrator\Service\Settings::SLUG,
			'version' => \Websource\Component\Websourcepreorder\Administrator\Service\Settings::VERSION,
		]);
	}
	?>
</div>
