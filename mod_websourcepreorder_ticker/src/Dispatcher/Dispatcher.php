<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  mod_websourcepreorder_ticker
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Module\PreorderTicker\Site\Dispatcher;

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
        $max = (int) $params->get('max', 0) ?: max(1, Settings::int('ticker_max'));
        $data['items'] = DisplayHelper::items(['in_ticker' => 1], $max);
        $data['speed'] = (int) $params->get('speed', 0) ?: max(5, Settings::int('ticker_speed'));
        $data['prefix'] = trim((string) $params->get('prefix', '')) ?: (Settings::text('ticker_prefix') ?: \Joomla\CMS\Language\Text::_('MOD_WEBSOURCEPREORDER_TICKER_DEFAULT_PREFIX'));
        $data['showPrice'] = (int) $params->get('show_price', 1) === 1;

        if ((int) $params->get('home_only', 0)) {
            $app  = \Joomla\CMS\Factory::getApplication();
            $menu = $app->getMenu();
            $data['items'] = ($menu->getActive() && $menu->getActive() === $menu->getDefault($app->getLanguage()->getTag())) ? $data['items'] : [];
        }

        if ($data['items']) {
            Assets::load();
        }

        return $data;
    }
}
