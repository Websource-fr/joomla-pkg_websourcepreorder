<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Administrator\View\Bookings;

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

        ToolbarHelper::title(Text::_('COM_WEBSOURCEPREORDER_BOOKINGS_TITLE'), 'list');
        $toolbar = Toolbar::getInstance();

        if ($this->getCurrentUser()->authorise('core.edit', 'com_websourcepreorder')) {
            $toolbar->standardButton('resend', 'COM_WEBSOURCEPREORDER_BTN_RESEND', 'bookings.resend')->listCheck(true)->icon('icon-envelope');
            $toolbar->standardButton('cancel', 'COM_WEBSOURCEPREORDER_BTN_CANCEL_BOOKING', 'bookings.cancel')->listCheck(true)->icon('icon-times');
            $toolbar->standardButton('restore', 'COM_WEBSOURCEPREORDER_BTN_RESTORE', 'bookings.restore')->listCheck(true)->icon('icon-undo');
        }

        $toolbar->standardButton('export', 'COM_WEBSOURCEPREORDER_BTN_EXPORT', 'bookings.export')->listCheck(false)->icon('icon-download');
        $this->getDocument()->getWebAssetManager()->useStyle('com_websourcepreorder.admin');

        parent::display($tpl);
    }
}
