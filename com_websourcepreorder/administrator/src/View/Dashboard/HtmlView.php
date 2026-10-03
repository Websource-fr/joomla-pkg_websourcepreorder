<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Administrator\View\Dashboard;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Websource\Component\Websourcepreorder\Administrator\Model\SettingsModel;
use Websource\Component\Websourcepreorder\Administrator\Service\HikashopBridge;
use Websource\Component\Websourcepreorder\Administrator\Service\Urls;

class HtmlView extends BaseHtmlView
{
    protected $stats;
    protected $hikashop;
    protected $cronUrl;

    public function display($tpl = null)
    {
        $this->stats    = $this->getModel()->getStats();
        $this->hikashop = HikashopBridge::isInstalled();
        $this->cronUrl  = Urls::task('cron.run', ['token' => SettingsModel::ensureToken()]);

        ToolbarHelper::title(Text::_('COM_WEBSOURCEPREORDER'), 'clock');

        $user = $this->getCurrentUser();

        if ($user->authorise('core.admin', 'com_websourcepreorder') || $user->authorise('core.options', 'com_websourcepreorder')) {
            ToolbarHelper::preferences('com_websourcepreorder');
        }

        $this->getDocument()->getWebAssetManager()->useStyle('com_websourcepreorder.admin');

        parent::display($tpl);
    }
}
