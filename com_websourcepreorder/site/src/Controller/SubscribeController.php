<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Websource\Component\Websourcepreorder\Administrator\Service\Env;
use Websource\Component\Websourcepreorder\Administrator\Service\Settings;
use Websource\Component\Websourcepreorder\Administrator\Service\SubscriberService;
use Websource\Component\Websourcepreorder\Administrator\Service\Urls;

class SubscribeController extends BaseController
{
    private function reply(bool $ok, string $message): void
    {
        if ($this->input->getCmd('format', '') === 'json' || strpos((string) $this->input->server->getString('HTTP_ACCEPT', ''), 'application/json') !== false) {
            $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
            $this->app->sendHeaders();
            echo new JsonResponse(['message' => $message], $message, !$ok);
            $this->app->close();
        }

        $back = $this->input->server->getString('HTTP_REFERER', '');
        $back = ($back !== '' && Uri::isInternal($back)) ? $back : Urls::root();
        $this->app->enqueueMessage($message, $ok ? 'message' : 'warning');
        $this->app->redirect($back);
    }

    /** Inscription aux alertes (POST, jeton CSRF, honeypot, délai minimum, limite par IP, consentement). */
    public function subscribe()
    {
        Env::loadLanguage();

        if (!Session::checkToken('post')) {
            $this->reply(false, Text::_('COM_WEBSOURCEPREORDER_SUB_ERR_TOKEN'));
        }

        // Pot de miel : les robots remplissent le champ caché ; on fait semblant de réussir.
        if (trim((string) $this->input->post->getString('wspo_hp', '')) !== '') {
            $this->reply(true, Text::_('COM_WEBSOURCEPREORDER_SUB_SENT'));
        }

        $ts = SubscriberService::validTimeToken((string) $this->input->post->getString('wspo_ts', ''));

        if ($ts === null || time() - $ts < Settings::int('sub_min_delay') || time() - $ts > 86400) {
            $this->reply(false, Text::_('COM_WEBSOURCEPREORDER_SUB_ERR_TOO_FAST'));
        }

        if (!$this->input->post->getInt('wspo_consent', 0)) {
            $this->reply(false, Text::_('COM_WEBSOURCEPREORDER_SUB_ERR_CONSENT'));
        }

        $ip = (string) $this->input->server->getString('REMOTE_ADDR', '');

        if (!SubscriberService::rateLimitOk($ip)) {
            $this->reply(false, Text::_('COM_WEBSOURCEPREORDER_SUB_ERR_RATE'));
        }

        $email  = (string) $this->input->post->getString('wspo_email', '');
        $source = preg_replace('/[^a-z0-9_-]/i', '', (string) $this->input->post->getString('wspo_place', 'site'));
        $result = SubscriberService::subscribe($email, $source, Env::langTag(), $ip);
        $map    = [
            'invalid'   => [false, 'COM_WEBSOURCEPREORDER_SUB_ERR_EMAIL'],
            'exists'    => [true, 'COM_WEBSOURCEPREORDER_SUB_EXISTS'],
            'confirmed' => [true, 'COM_WEBSOURCEPREORDER_SUB_CONFIRMED'],
            'sent'      => [true, 'COM_WEBSOURCEPREORDER_SUB_SENT'],
            'error'     => [false, 'COM_WEBSOURCEPREORDER_SUB_ERROR'],
        ];
        [$ok, $key] = $map[$result] ?? $map['error'];
        $this->reply($ok, Text::_($key));
    }

    /** Lien du double opt-in. */
    public function confirm()
    {
        Env::loadLanguage();
        $ok = SubscriberService::confirm((string) $this->input->getString('token', ''));
        $this->app->enqueueMessage(Text::_($ok ? 'COM_WEBSOURCEPREORDER_CONFIRM_OK' : 'COM_WEBSOURCEPREORDER_TOKEN_INVALID'), $ok ? 'message' : 'warning');
        $this->app->redirect(Urls::listPage());
    }

    /** Désinscription en un clic. */
    public function unsubscribe()
    {
        Env::loadLanguage();
        $ok = SubscriberService::unsubscribe((string) $this->input->getString('token', ''));
        $this->app->enqueueMessage(Text::_($ok ? 'COM_WEBSOURCEPREORDER_UNSUB_OK' : 'COM_WEBSOURCEPREORDER_TOKEN_INVALID'), $ok ? 'message' : 'warning');
        $this->app->redirect(Urls::root());
    }
}
