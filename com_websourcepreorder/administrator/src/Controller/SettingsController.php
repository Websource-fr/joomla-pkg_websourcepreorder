<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Websource\Component\Websourcepreorder\Administrator\Model\SettingsModel;
use Websource\Component\Websourcepreorder\Administrator\Service\Env;
use Websource\Component\Websourcepreorder\Administrator\Service\MailService;

class SettingsController extends BaseController
{
    private function guard(): bool
    {
        $this->checkToken();

        if (!$this->app->getIdentity()->authorise('core.admin', 'com_websourcepreorder')) {
            $this->setRedirect('index.php?option=com_websourcepreorder&view=settings', Text::_('JERROR_ALERTNOAUTHOR'), 'error');

            return false;
        }

        return true;
    }

    private function back(): string
    {
        $tab = preg_replace('/[^a-z0-9_-]/i', '', (string) $this->input->getString('tab', ''));

        return 'index.php?option=com_websourcepreorder&view=settings' . ($tab !== '' ? '&tab=' . $tab : '');
    }

    /** Enregistre réglages et e-mails (bouton Enregistrer). */
    public function save()
    {
        if (!$this->guard()) {
            return false;
        }

        /** @var SettingsModel $model */
        $model = $this->getModel('Settings', 'Administrator', ['ignore_request' => true]);
        $form  = $model->getForm($this->input->post->get('jform', [], 'array'), false);
        $data  = $model->validate($form, $this->input->post->get('jform', [], 'array'));

        if ($data === false) {
            foreach ($model->getErrors() as $e) {
                $this->app->enqueueMessage($e instanceof \Exception ? $e->getMessage() : $e, 'warning');
            }

            $this->app->setUserState('com_websourcepreorder.edit.settings.data', $this->input->post->get('jform', [], 'array'));
            $this->setRedirect($this->back());

            return false;
        }

        $this->app->setUserState('com_websourcepreorder.edit.settings.data', null);
        $model->save($data);
        $mail = $this->input->post->get('mail', [], 'array');

        if ($mail) {
            $model->saveMails($mail);
        }

        $this->setRedirect($this->back(), Text::_('COM_WEBSOURCEPREORDER_SETTINGS_SAVED'));

        return true;
    }

    /** Envoie un e-mail de test d'un type donné à l'adresse saisie. */
    public function testMail()
    {
        if (!$this->guard()) {
            return false;
        }

        $key  = $this->input->getCmd('key', 'order');
        $tag  = $this->input->getString('lang', Env::langTag());
        $to   = trim($this->input->getString('test_to', ''));

        if (!\in_array($key, MailService::KEYS, true) || !array_key_exists($tag, Env::languages())) {
            $this->setRedirect($this->back(), Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

            return false;
        }

        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $to = (string) $this->app->getIdentity()->email;
        }

        // Enregistre d'abord les gabarits saisis pour que le test reflète l'écran.
        $mail = $this->input->post->get('mail', [], 'array');

        if ($mail) {
            $this->getModel('Settings', 'Administrator', ['ignore_request' => true])->saveMails($mail);
        }

        $ok = MailService::send($key, $to, $tag, MailService::sampleVars($key), 'Test');
        $this->setRedirect($this->back(), $ok ? Text::sprintf('COM_WEBSOURCEPREORDER_TEST_SENT', $to) : Text::_('COM_WEBSOURCEPREORDER_TEST_FAILED'), $ok ? 'message' : 'error');

        return true;
    }

    public function cancel()
    {
        $this->setRedirect('index.php?option=com_websourcepreorder');

        return true;
    }

    public function regenerateToken()
    {
        if (!$this->guard()) {
            return false;
        }

        SettingsModel::ensureToken(true);
        $this->setRedirect($this->back(), Text::_('COM_WEBSOURCEPREORDER_TOKEN_REGENERATED'));

        return true;
    }
}
