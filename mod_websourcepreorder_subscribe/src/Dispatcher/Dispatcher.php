<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  mod_websourcepreorder_subscribe
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Module\PreorderSubscribe\Site\Dispatcher;

\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Websource\Component\Websourcepreorder\Administrator\Service\Assets;
use Websource\Component\Websourcepreorder\Administrator\Service\DisplayHelper;
use Websource\Component\Websourcepreorder\Administrator\Service\Env;
use Websource\Component\Websourcepreorder\Administrator\Service\HikashopBridge;
use Websource\Component\Websourcepreorder\Administrator\Service\Settings;

class Dispatcher extends AbstractModuleDispatcher
{
    public function dispatch(): void
    {
        // Le composant (services, CSS et JS partagés) doit être présent.
        if (!is_dir(JPATH_ADMINISTRATOR . '/components/com_websourcepreorder/src') || !HikashopBridge::isInstalled()) {
            return;
        }

        try {
            parent::dispatch();
        } catch (\Throwable $e) {
            // Un module ne doit jamais casser la page.
        }
    }

    protected function getLayoutData(): array
    {
        $data   = parent::getLayoutData();
        $params = $data['params'];
        Env::loadLanguage();
        $data['show'] = !((int) $params->get('only_with_preorders', 0) && !DisplayHelper::hasPreorders());
        $data['place'] = preg_replace('/[^a-z-]/', '', (string) $params->get('place', 'footer')) ?: 'footer';
        $data['title'] = trim((string) $params->get('title', ''));
        $data['text']  = trim((string) $params->get('text', ''));

        if ($data['show']) {
            Assets::load();
        }

        return $data;
    }
}
