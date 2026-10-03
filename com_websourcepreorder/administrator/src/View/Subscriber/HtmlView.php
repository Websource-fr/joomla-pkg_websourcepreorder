<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Administrator\View\Subscriber;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $item;

    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        Factory::getApplication()->getInput()->set('hidemainmenu', true);
        ToolbarHelper::title(Text::_($this->item->id ? 'COM_WEBSOURCEPREORDER_SUBSCRIBER_EDIT' : 'COM_WEBSOURCEPREORDER_SUBSCRIBER_NEW'), 'users');
        ToolbarHelper::apply('subscriber.apply');
        ToolbarHelper::save('subscriber.save');
        ToolbarHelper::cancel('subscriber.cancel', $this->item->id ? 'JTOOLBAR_CLOSE' : 'JTOOLBAR_CANCEL');
        $this->getDocument()->getWebAssetManager()->useStyle('com_websourcepreorder.admin');

        parent::display($tpl);
    }
}
