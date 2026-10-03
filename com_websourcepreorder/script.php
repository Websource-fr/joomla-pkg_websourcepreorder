<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * Script d'installation du composant : jeton de cron à l'installation ; à la désinstallation,
 * restauration du stock des précommandes en cours puis suppression des tables (sauf « conserver les données »).
 */
class Com_WebsourcepreorderInstallerScript
{
    private const TABLES = ['rules', 'bookings', 'subscribers', 'mails', 'state', 'ratelimit'];

    public function postflight($type, $parent): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        $table = Table::getInstance('extension');

        if ($table->load(['element' => 'com_websourcepreorder', 'type' => 'component'])) {
            $params = new Registry($table->params);

            if ((string) $params->get('cron_token', '') === '') {
                $params->set('cron_token', UserHelper::genRandomPassword(40));
                $table->params = $params->toString();
                $table->store();
            }
        }

        return true;
    }

    public function uninstall($parent): bool
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $table = Table::getInstance('extension');
            $keep  = false;

            if ($table->load(['element' => 'com_websourcepreorder', 'type' => 'component'])) {
                $keep = (int) (new Registry($table->params))->get('keep_data', 1) === 1;
            }

            // Restaure le stock des précommandes en cours (toujours, que les données soient conservées ou non).
            $db->setQuery('SHOW TABLES LIKE ' . $db->quote($db->getPrefix() . 'websourcepreorder_rules'));

            if ($db->loadResult()) {
                $db->setQuery(
                    $db->getQuery(true)->select($db->quoteName(['id', 'prev_state']))->from($db->quoteName('#__websourcepreorder_rules'))->where($db->quoteName('state') . ' = 1')
                );

                foreach ((array) $db->loadObjectList() as $rule) {
                    $prev = json_decode((string) $rule->prev_state, true);

                    if (\is_array($prev)) {
                        foreach ($prev as $pid => $qty) {
                            $pid = (int) $pid;
                            $qty = (int) $qty;
                            $db->setQuery(
                                $db->getQuery(true)->update($db->quoteName('#__hikashop_product'))
                                    ->set($db->quoteName('product_quantity') . ' = :q')->where($db->quoteName('product_id') . ' = :p')
                                    ->bind(':q', $qty, \Joomla\Database\ParameterType::INTEGER)->bind(':p', $pid, \Joomla\Database\ParameterType::INTEGER)
                            )->execute();
                        }
                    }

                    $id = (int) $rule->id;
                    $db->setQuery(
                        $db->getQuery(true)->update($db->quoteName('#__websourcepreorder_rules'))
                            ->set($db->quoteName('state') . ' = 2')->set($db->quoteName('prev_state') . ' = ' . $db->quote(''))
                            ->where($db->quoteName('id') . ' = :i')->bind(':i', $id, \Joomla\Database\ParameterType::INTEGER)
                    )->execute();
                }
            }

            if (!$keep) {
                foreach (self::TABLES as $t) {
                    $db->setQuery('DROP TABLE IF EXISTS ' . $db->quoteName('#__websourcepreorder_' . $t))->execute();
                }
            }
        } catch (\Throwable $e) {
            Factory::getApplication()->enqueueMessage('Websource Précommandes : ' . $e->getMessage(), 'warning');
        }

        return true;
    }
}
