<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  pkg_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

/**
 * Script du package : vérifie les prérequis, active les plugins installés et signale l'absence de HikaShop.
 */
class Pkg_WebsourcepreorderInstallerScript
{
    public function preflight($type, $parent): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        $lang = Factory::getApplication()->getLanguage();
        $lang->load('pkg_websourcepreorder.sys', dirname(__FILE__)) || $lang->load('pkg_websourcepreorder.sys', JPATH_ADMINISTRATOR);

        if (version_compare(PHP_VERSION, '8.1', '<')) {
            Factory::getApplication()->enqueueMessage(Text::_('PKG_WEBSOURCEPREORDER_PHP_TOO_OLD'), 'error');

            return false;
        }

        if (version_compare(JVERSION, '4.4', '<')) {
            Factory::getApplication()->enqueueMessage(Text::_('PKG_WEBSOURCEPREORDER_JOOMLA_TOO_OLD'), 'error');

            return false;
        }

        return true;
    }

    public function postflight($type, $parent): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        $app = Factory::getApplication();
        $db  = Factory::getContainer()->get(DatabaseInterface::class);

        // Les plugins du package sont activés d'office.
        foreach ([['system', 'websourcepreorder'], ['task', 'websourcepreorder']] as [$folder, $element]) {
            $db->setQuery(
                $db->getQuery(true)->update($db->quoteName('#__extensions'))->set($db->quoteName('enabled') . ' = 1')
                    ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                    ->where($db->quoteName('folder') . ' = :f')->where($db->quoteName('element') . ' = :e')
                    ->bind(':f', $folder)->bind(':e', $element)
            )->execute();
        }

        $app->getLanguage()->load('pkg_websourcepreorder.sys', JPATH_ADMINISTRATOR);

        if (!is_file(JPATH_ADMINISTRATOR . '/components/com_hikashop/helpers/helper.php')) {
            $app->enqueueMessage(Text::_('PKG_WEBSOURCEPREORDER_NEEDS_HIKASHOP'), 'warning');
        } else {
            $app->enqueueMessage(Text::_('PKG_WEBSOURCEPREORDER_INSTALLED'), 'message');
        }

        return true;
    }
}
