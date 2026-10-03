<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Site\View\Mypreorders;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Websource\Component\Websourcepreorder\Administrator\Service\Assets;
use Websource\Component\Websourcepreorder\Administrator\Service\Env;

class HtmlView extends BaseHtmlView
{
    protected $items = [];

    public function display($tpl = null)
    {
        Env::loadLanguage();
        Assets::load();
        $app  = Factory::getApplication();
        $user = $app->getIdentity();

        if ($user->guest) {
            $app->enqueueMessage(Text::_('COM_WEBSOURCEPREORDER_LOGIN_REQUIRED'), 'notice');
            $app->redirect(Route::_('index.php?option=com_users&view=login&return=' . base64_encode(Uri::getInstance()->toString()), false));
        }

        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $uid = (int) $user->id;
        $q   = $db->getQuery(true)
            ->select('b.*, o.order_number AS order_number')
            ->from($db->quoteName('#__websourcepreorder_bookings', 'b'))
            ->join('LEFT', $db->quoteName('#__hikashop_order', 'o') . ' ON o.order_id = b.order_id')
            ->where('(b.user_id = :u1 OR b.hika_user_id IN (SELECT hu.user_id FROM ' . $db->quoteName('#__hikashop_user', 'hu') . ' WHERE hu.user_cms_id = :u2))')
            ->bind(':u1', $uid, \Joomla\Database\ParameterType::INTEGER)
            ->bind(':u2', $uid, \Joomla\Database\ParameterType::INTEGER)
            ->order('b.id DESC');
        $db->setQuery($q);
        $this->items = (array) $db->loadObjectList();
        $this->getDocument()->setTitle(Text::_('COM_WEBSOURCEPREORDER_MYPREORDERS_TITLE'));

        parent::display($tpl);
    }
}
