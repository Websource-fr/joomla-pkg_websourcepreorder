<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Administrator\View\Settings;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Websource\Component\Websourcepreorder\Administrator\Model\SettingsModel;
use Websource\Component\Websourcepreorder\Administrator\Service\Env;
use Websource\Component\Websourcepreorder\Administrator\Service\MailService;
use Websource\Component\Websourcepreorder\Administrator\Service\Urls;

class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $mails;
    protected $languages;
    protected $cronUrl;
    protected $tab;

    public function display($tpl = null)
    {
        $this->form      = $this->get('Form');
        $this->mails     = $this->getModel()->getMails();
        $this->languages = Env::languages();
        $this->cronUrl   = Urls::task('cron.run', ['token' => SettingsModel::ensureToken()]);
        $this->tab       = preg_replace('/[^a-z0-9_-]/i', '', (string) \Joomla\CMS\Factory::getApplication()->getInput()->getString('tab', 'general'));

        ToolbarHelper::title(Text::_('COM_WEBSOURCEPREORDER_SETTINGS_TITLE'), 'cog');
        ToolbarHelper::apply('settings.save');
        ToolbarHelper::cancel('settings.cancel', 'JTOOLBAR_CLOSE');
        $this->getDocument()->getWebAssetManager()->useStyle('com_websourcepreorder.admin')->useScript('com_websourcepreorder.admin');

        parent::display($tpl);
    }
}
