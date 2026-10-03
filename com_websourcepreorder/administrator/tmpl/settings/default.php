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
use Websource\Component\Websourcepreorder\Administrator\Service\MailService;

HTMLHelper::_('behavior.formvalidator');
$tabs = ['general', 'counter', 'colors', 'home', 'accounts', 'list', 'alerts', 'cron'];
$tabs[] = 'emails';
$active = in_array($this->tab, $tabs, true) ? $this->tab : 'general';
?>
<form action="<?php echo Route::_('index.php?option=com_websourcepreorder&view=settings'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate wspo-settings">
	<input type="hidden" name="tab" id="wspo-tab" value="<?php echo htmlspecialchars($active); ?>">
	<?php echo HTMLHelper::_('uitab.startTabSet', 'wspoSettings', ['active' => $active, 'recall' => false, 'breakpoint' => 768]); ?>

	<?php foreach (['general', 'counter', 'colors', 'home', 'accounts', 'list', 'alerts', 'cron'] as $name) : ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'wspoSettings', $name, Text::_('COM_WEBSOURCEPREORDER_TAB_' . strtoupper($name))); ?>
		<div class="row"><div class="col-lg-8">
			<p class="text-muted"><?php echo Text::_('COM_WEBSOURCEPREORDER_TAB_' . strtoupper($name) . '_INTRO'); ?></p>
			<?php echo $this->form->renderFieldset($name); ?>
			<?php if ($name === 'cron') : ?>
				<div class="control-group">
					<div class="control-label"><label><?php echo Text::_('COM_WEBSOURCEPREORDER_SET_CRON_URL'); ?></label></div>
					<div class="controls">
						<input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($this->cronUrl); ?>" onclick="this.select()">
						<div class="form-text"><?php echo Text::_('COM_WEBSOURCEPREORDER_SET_CRON_URL_DESC'); ?></div>
						<p class="mt-2"><button type="submit" class="btn btn-warning" formnovalidate onclick="document.querySelector('[name=task]').value='settings.regenerateToken';"><?php echo Text::_('COM_WEBSOURCEPREORDER_BTN_REGENERATE'); ?></button></p>
						<pre class="wspo-pre">*/15 * * * * curl -fsS "<?php echo htmlspecialchars($this->cronUrl); ?>" &gt;/dev/null</pre>
					</div>
				</div>
			<?php endif; ?>
		</div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php endforeach; ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'wspoSettings', 'emails', Text::_('COM_WEBSOURCEPREORDER_TAB_EMAILS')); ?>
	<p class="text-muted"><?php echo Text::_('COM_WEBSOURCEPREORDER_TAB_EMAILS_INTRO'); ?></p>
	<div class="wspo-mails">
		<div class="row mb-3">
			<div class="col-md-4">
				<label for="wspo-mail-key" class="form-label"><?php echo Text::_('COM_WEBSOURCEPREORDER_MAIL_TYPE'); ?></label>
				<select id="wspo-mail-key" class="form-select">
					<?php foreach (MailService::KEYS as $key) : ?><option value="<?php echo $key; ?>"><?php echo Text::_('COM_WEBSOURCEPREORDER_MAIL_' . strtoupper($key)); ?></option><?php endforeach; ?>
				</select>
			</div>
			<div class="col-md-4">
				<label for="wspo-mail-lang" class="form-label"><?php echo Text::_('JFIELD_LANGUAGE_LABEL'); ?></label>
				<select id="wspo-mail-lang" class="form-select">
					<?php foreach ($this->languages as $tag => $title) : ?><option value="<?php echo htmlspecialchars($tag); ?>"><?php echo htmlspecialchars(strpos($title, $tag) !== false ? $title : $title . ' (' . $tag . ')'); ?></option><?php endforeach; ?>
				</select>
			</div>
		</div>
		<?php foreach ($this->mails as $key => $byLang) : ?>
			<?php foreach ($byLang as $tag => $tpl) : ?>
				<div class="wspo-mail" data-key="<?php echo $key; ?>" data-lang="<?php echo htmlspecialchars($tag); ?>" hidden>
					<div class="mb-3">
						<label class="form-label" for="mail_<?php echo $key . '_' . $tag; ?>_subject"><?php echo Text::_('COM_WEBSOURCEPREORDER_MAIL_SUBJECT'); ?></label>
						<input type="text" class="form-control" id="mail_<?php echo $key . '_' . $tag; ?>_subject" name="mail[<?php echo $key; ?>][<?php echo htmlspecialchars($tag); ?>][subject]" value="<?php echo htmlspecialchars($tpl['subject']); ?>">
					</div>
					<div class="mb-3">
						<label class="form-label" for="mail_<?php echo $key . '_' . $tag; ?>_body"><?php echo Text::_('COM_WEBSOURCEPREORDER_MAIL_BODY'); ?></label>
						<textarea class="form-control font-monospace" rows="12" id="mail_<?php echo $key . '_' . $tag; ?>_body" name="mail[<?php echo $key; ?>][<?php echo htmlspecialchars($tag); ?>][body]"><?php echo htmlspecialchars($tpl['body']); ?></textarea>
					</div>
					<p class="small"><strong><?php echo Text::_('COM_WEBSOURCEPREORDER_MAIL_VARS'); ?></strong>
						<?php foreach (MailService::variables($key) as $v) : ?><code>{<?php echo $v; ?>}</code> <?php endforeach; ?></p>
				</div>
			<?php endforeach; ?>
		<?php endforeach; ?>
		<div class="row g-2 align-items-end mt-2">
			<div class="col-md-5">
				<label class="form-label" for="wspo-test-to"><?php echo Text::_('COM_WEBSOURCEPREORDER_MAIL_TEST_TO'); ?></label>
				<input type="email" class="form-control" id="wspo-test-to" name="test_to" value="">
			</div>
			<div class="col-md-4">
				<input type="hidden" name="key" id="wspo-test-key" value="order">
				<input type="hidden" name="lang" id="wspo-test-lang" value="">
				<button type="submit" class="btn btn-outline-primary" formnovalidate id="wspo-test-btn" onclick="document.querySelector('[name=task]').value='settings.testMail';"><?php echo Text::_('COM_WEBSOURCEPREORDER_MAIL_TEST_BTN'); ?></button>
			</div>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
