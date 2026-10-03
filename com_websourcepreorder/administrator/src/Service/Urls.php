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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Génération d'URL publiques absolues, utilisable depuis le site, l'administration, le cron et la CLI.
 */
final class Urls
{
    /** Racine publique du site (avec slash final). */
    public static function root(): string
    {
        $custom = Settings::text('site_url');

        if ($custom !== '' && preg_match('#^https?://#i', $custom)) {
            return rtrim($custom, '/') . '/';
        }

        // Configuration globale de Joomla (live_site) : seule source fiable en ligne de commande.
        $live = trim((string) Factory::getApplication()->get('live_site', ''));

        if ($live !== '' && preg_match('#^https?://#i', $live)) {
            return rtrim($live, '/') . '/';
        }

        return Uri::root();
    }

    /** Transforme une URL relative en URL absolue. */
    public static function absolute(string $url): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        $root  = self::root();
        $parts = parse_url($root);
        $host  = ($parts['scheme'] ?? 'http') . '://' . ($parts['host'] ?? 'localhost') . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $base  = rtrim($parts['path'] ?? '/', '/');

        if (strpos($url, '/') === 0) {
            // Chemin déjà absolu par rapport au domaine : on évite de dupliquer le sous-dossier.
            if ($base !== '' && strpos($url, $base . '/') === 0) {
                return $host . $url;
            }

            return $host . $base . $url;
        }

        return $host . $base . '/' . $url;
    }

    /** URL absolue (SEF si possible) d'un lien non-SEF du site public. */
    public static function site(string $nonSef): string
    {
        try {
            $app = Factory::getApplication();

            if ($app->isClient('site')) {
                $url = Route::_($nonSef, false);
            } else {
                $url = Route::link('site', $nonSef, false);
                // Depuis l'administration, le sous-dossier /administrator ne doit pas se retrouver dans l'URL.
                $url = preg_replace('#/administrator(?=/|$)#', '', (string) $url, 1);
            }

            if ($url !== '' && $url !== null) {
                return self::absolute($url);
            }
        } catch (\Throwable $e) {
            // repli ci-dessous
        }

        return self::absolute($nonSef);
    }

    /** URL d'une tâche publique du composant (confirmation, désinscription, cron…). */
    public static function task(string $task, array $query = []): string
    {
        $query = array_merge(['option' => 'com_websourcepreorder', 'task' => $task], $query);

        // Ces liens sont de simples actions : on les garde non-SEF pour qu'ils restent stables.
        // Forme « racine/?option=… » : évite la redirection 301 de Joomla quand la réécriture d'URL est active.
        return self::root() . '?' . http_build_query($query, '', '&');
    }

    /** URL de la page « Toutes les précommandes ». */
    public static function listPage(): string
    {
        return self::site('index.php?option=com_websourcepreorder&view=preorders');
    }

    /** URL de la page « Mes précommandes ». */
    public static function accountPage(): string
    {
        return self::site('index.php?option=com_websourcepreorder&view=mypreorders');
    }
}
