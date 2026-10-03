<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Petit stockage clé/valeur d'état interne (dernière synchro, état du récapitulatif…),
 * séparé des paramètres pour éviter toute collision avec l'enregistrement des réglages.
 */
final class State
{
    private static function db(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }

    public static function get(string $name, string $default = ''): string
    {
        $db    = self::db();
        $query = $db->getQuery(true)
            ->select($db->quoteName('value'))
            ->from($db->quoteName('#__websourcepreorder_state'))
            ->where($db->quoteName('name') . ' = :n')
            ->bind(':n', $name);
        $db->setQuery($query);
        $value = $db->loadResult();

        return $value === null ? $default : (string) $value;
    }

    public static function set(string $name, $value): void
    {
        $db     = self::db();
        $value  = (string) $value;
        $exists = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__websourcepreorder_state'))
            ->where($db->quoteName('name') . ' = :n')
            ->bind(':n', $name);
        $db->setQuery($exists);

        if ((int) $db->loadResult() > 0) {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__websourcepreorder_state'))
                ->set($db->quoteName('value') . ' = :v')
                ->where($db->quoteName('name') . ' = :n')
                ->bind(':v', $value)
                ->bind(':n', $name);
        } else {
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__websourcepreorder_state'))
                ->columns($db->quoteName(['name', 'value']))
                ->values(':n, :v')
                ->bind(':n', $name)
                ->bind(':v', $value);
        }

        $db->setQuery($query)->execute();
    }
}
