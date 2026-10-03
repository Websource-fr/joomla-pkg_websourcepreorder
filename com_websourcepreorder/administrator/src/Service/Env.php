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
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;

/**
 * Utilitaires d'environnement : langue, dates (stockées en UTC), libellés.
 */
final class Env
{
    public static function language(): Language
    {
        $app = Factory::getApplication();

        return method_exists($app, 'getLanguage') ? $app->getLanguage() : Factory::getLanguage();
    }

    /** Charge les chaînes de langue du composant depuis n'importe quel contexte (plugin, module, cron). */
    public static function loadLanguage(): void
    {
        static $done = false;

        if ($done) {
            return;
        }

        $done = true;
        $lang = self::language();
        $lang->load('com_websourcepreorder', JPATH_SITE);
        $lang->load('com_websourcepreorder', JPATH_ADMINISTRATOR);
        $lang->load('com_websourcepreorder', JPATH_SITE . '/components/com_websourcepreorder');
    }

    public static function langTag(): string
    {
        return self::language()->getTag();
    }

    /** Timestamp UNIX d'une date SQL (UTC), null si vide. */
    public static function ts($date): ?int
    {
        $date = trim((string) $date);

        if ($date === '' || strpos($date, '0000-00-00') === 0) {
            return null;
        }

        $t = strtotime($date . ' UTC');

        return $t ?: null;
    }

    /** Date SQL UTC pour un timestamp. */
    public static function sql(int $ts): string
    {
        return gmdate('Y-m-d H:i:s', $ts);
    }

    public static function nowSql(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    /** Date lisible dans le fuseau de l'utilisateur / du site (heure ajoutée si elle n'est pas minuit). */
    public static function formatDate($date, ?string $lang = null): string
    {
        $t = self::ts($date);

        if (!$t) {
            return '';
        }

        try {
            $d   = Factory::getDate(gmdate('Y-m-d H:i:s', $t), 'UTC');
            $app = Factory::getApplication();
            $tz  = null;

            if (method_exists($app, 'getIdentity') && $app->getIdentity() && method_exists($app->getIdentity(), 'getTimezone')) {
                $tz = $app->getIdentity()->getTimezone();
            }

            $d->setTimezone($tz ?: new \DateTimeZone((string) $app->get('offset', 'UTC')));
            $fmt = Text::_('DATE_FORMAT_LC3');

            if ($d->format('H:i') !== '00:00') {
                $fmt .= ' H:i';
            }

            return $d->format($fmt, true, true);
        } catch (\Throwable $e) {
            return gmdate('d/m/Y H:i', $t);
        }
    }

    /** Clé « AAAA-MM » dans le fuseau du site, pour regrouper par mois. */
    public static function monthKey($date): string
    {
        $t = self::ts($date);

        return $t ? gmdate('Y-m', $t) : '';
    }

    /** Nom de mois localisé pour une clé AAAA-MM. */
    public static function monthLabel(string $key): string
    {
        if ($key === '') {
            return Text::_('COM_WEBSOURCEPREORDER_DATE_TBA');
        }

        $d = Factory::getDate($key . '-01 12:00:00', 'UTC');

        return $d->format(Text::_('COM_WEBSOURCEPREORDER_MONTH_FORMAT'), false, true);
    }

    /** Langues du site disponibles (tag => titre) : langues installées du site, complétées des langues de contenu publiées. */
    public static function languages(): array
    {
        $out = [];

        foreach (\Joomla\CMS\Language\LanguageHelper::getInstalledLanguages(0, true) as $l) {
            $out[$l->element] = $l->name;
        }

        foreach (\Joomla\CMS\Language\LanguageHelper::getContentLanguages([1]) as $l) {
            $out[$l->lang_code] = $out[$l->lang_code] ?? $l->title;
        }

        return $out ?: ['fr-FR' => 'Français (fr-FR)', 'en-GB' => 'English (en-GB)'];
    }

    public static function shopName(): string
    {
        return (string) Factory::getApplication()->get('sitename', '');
    }
}
