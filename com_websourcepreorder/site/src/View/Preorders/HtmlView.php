<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Site\View\Preorders;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Websource\Component\Websourcepreorder\Administrator\Service\Assets;
use Websource\Component\Websourcepreorder\Administrator\Service\DisplayHelper;
use Websource\Component\Websourcepreorder\Administrator\Service\Env;
use Websource\Component\Websourcepreorder\Administrator\Service\Settings;

class HtmlView extends BaseHtmlView
{
    protected $groups = [];
    protected $title;
    protected $intro;
    protected $empty;
    protected $params;

    public function display($tpl = null)
    {
        Env::loadLanguage();
        Assets::load();
        $app          = \Joomla\CMS\Factory::getApplication();
        $this->params = $app->getParams();
        $items        = DisplayHelper::items(['in_list' => 1]);
        $group        = Settings::flag('list_group_month');

        foreach ($items as $it) {
            $key = $group ? $it['month'] : 'all';
            $this->groups[$key]['items'][] = $it;
        }

        if ($group) {
            uksort($this->groups, static function ($a, $b) {
                if ($a === '') {
                    return 1;
                }

                return $b === '' ? -1 : strcmp($a, $b);
            });
        }

        foreach ($this->groups as $k => &$g) {
            $g['label'] = $group ? Env::monthLabel((string) $k) : '';
        }

        unset($g);
        $this->title = $this->params->get('page_heading') ?: (Settings::text('list_title') ?: Text::_('COM_WEBSOURCEPREORDER_LIST_TITLE'));
        $this->intro = Settings::text('list_intro');
        $this->empty = Settings::text('list_empty') ?: Text::_('COM_WEBSOURCEPREORDER_LIST_EMPTY');
        $this->getDocument()->setTitle($this->title);

        parent::display($tpl);
    }
}
