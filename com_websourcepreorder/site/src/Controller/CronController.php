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

use Joomla\CMS\MVC\Controller\BaseController;
use Websource\Component\Websourcepreorder\Administrator\Service\DigestService;
use Websource\Component\Websourcepreorder\Administrator\Service\RuleService;
use Websource\Component\Websourcepreorder\Administrator\Service\Settings;

/** URL de cron sécurisée par jeton : index.php?option=com_websourcepreorder&task=cron.run&token=... */
class CronController extends BaseController
{
    public function run()
    {
        $token    = (string) $this->input->getString('token', '');
        $expected = (string) Settings::get('cron_token', '');

        $this->app->setHeader('Content-Type', 'text/plain; charset=utf-8', true);
        $this->app->setHeader('X-Robots-Tag', 'noindex', true);

        if ($expected === '' || !hash_equals($expected, $token)) {
            $this->app->setHeader('status', '403', true);
            $this->app->sendHeaders();
            echo 'forbidden';
            $this->app->close();
        }

        $action = $this->input->getCmd('action', 'all');
        $out    = [];

        if (\in_array($action, ['all', 'sync'], true)) {
            $out[] = 'sync=' . RuleService::syncAll();
        }

        if (\in_array($action, ['all', 'digest'], true)) {
            [$sent, $status] = DigestService::run(false);
            $out[]            = 'digest=' . $sent . ':' . $status;
        }

        $this->app->sendHeaders();
        echo 'OK ' . implode(' ', $out);
        $this->app->close();
    }
}
