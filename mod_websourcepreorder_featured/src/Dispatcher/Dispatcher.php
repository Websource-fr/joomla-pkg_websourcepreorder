<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  mod_websourcepreorder_featured
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Module\PreorderFeatured\Site\Dispatcher;

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
        $items = DisplayHelper::items(['featured' => 1], 1);

        if (!$items && (int) $params->get('fallback', 1)) {
            $items = DisplayHelper::items([], 1);
        }

        $data['item']  = $items ? $items[0] : null;
        $data['title'] = trim((string) $params->get('title', '')) ?: (Settings::text('featured_title') ?: \Joomla\CMS\Language\Text::_('MOD_WEBSOURCEPREORDER_FEATURED_DEFAULT_TITLE'));
        $data['cta']   = Settings::text('featured_cta');
        $data['showSubscribe'] = (int) $params->get('show_subscribe', 0) === 1;

        if ($data['item']) {
            Assets::load();
        }

        return $data;
    }
}
