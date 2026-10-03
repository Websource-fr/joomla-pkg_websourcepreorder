<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Administrator\View\Rules;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    public $filterForm;
    public $activeFilters;

    public function display($tpl = null)
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        ToolbarHelper::title(Text::_('COM_WEBSOURCEPREORDER_RULES_TITLE'), 'clock');
        $user    = $this->getCurrentUser();
        $toolbar = Toolbar::getInstance();

        if ($user->authorise('core.create', 'com_websourcepreorder')) {
            ToolbarHelper::addNew('rule.add');
        }

        if ($user->authorise('core.edit.state', 'com_websourcepreorder')) {
            $toolbar->standardButton('release', 'COM_WEBSOURCEPREORDER_BTN_RELEASE', 'rules.release')->listCheck(true)->icon('icon-play');
            ToolbarHelper::publish('rules.publish', 'JTOOLBAR_ENABLE', true);
            ToolbarHelper::unpublish('rules.unpublish', 'JTOOLBAR_DISABLE', true);
        }

        if ($user->authorise('core.delete', 'com_websourcepreorder')) {
            ToolbarHelper::deleteList('COM_WEBSOURCEPREORDER_CONFIRM_DELETE', 'rules.delete');
        }

        $this->getDocument()->getWebAssetManager()->useStyle('com_websourcepreorder.admin');

        parent::display($tpl);
    }
}
