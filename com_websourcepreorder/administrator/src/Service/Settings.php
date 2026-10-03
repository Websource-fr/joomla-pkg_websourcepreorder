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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

/**
 * Accès centralisé aux réglages du composant (paramètres com_websourcepreorder) avec valeurs par défaut.
 */
final class Settings
{
    public const COMPONENT = 'com_websourcepreorder';

    /** Version de l'extension : unique constante (vérifiée contre le manifeste par build.sh). */
    public const VERSION = '1.0.0';

    /** Identifiant utilisé pour l'encart d'accompagnement Websource. */
    public const SLUG = 'pkg_websourcepreorder';

    /** @var Registry|null */
    private static $params = null;

    /** Valeurs par défaut de tous les réglages. */
    public static function defaults(): array
    {
        return [
            // Général
            'admin_email'      => '',
            'mail_order_on'    => 1,
            'mail_admin_on'    => 1,
            'mail_available_on' => 1,
            'mixed_cart'       => 'allow',
            'lazy_sync'        => 1,
            'btn_label'        => '',
            'badge_label'      => '',
            'show_flag'        => 1,
            'show_cart_notice' => 1,
            'keep_data'        => 1,
            'cancel_statuses'  => 'cancelled,refunded',
            'unpaid_release_hours' => 0,
            'site_url'         => '',
            // Compteur
            'show_countdown'   => 1,
            'show_progress'    => 1,
            'show_quota'       => 1,
            'countdown_mode'   => 'auto',
            // Couleurs
            'c_accent'         => '#e8590c',
            'c_on_accent'      => '#ffffff',
            'c_box_bg'         => '#fff7ed',
            'c_border'         => '#fdba74',
            'c_count_bg'       => '#1f2937',
            'c_count_text'     => '#ffffff',
            'c_badge_bg'       => '#e8590c',
            'c_ticker_bg'      => '#1f2937',
            'c_ticker_text'    => '#ffffff',
            // Accueil & bandeau
            'featured_title'   => '',
            'featured_cta'     => '',
            'ticker_prefix'    => '',
            'ticker_speed'     => 40,
            'ticker_max'       => 10,
            // Blocs comptes
            'blk_login'        => 0,
            'blk_register'     => 1,
            'blk_reset'        => 0,
            'blk_products'     => 1,
            'blk_form'         => 1,
            'blk_max'          => 3,
            'blk_title'        => '',
            // Page liste
            'list_title'       => '',
            'list_intro'       => '',
            'list_group_month' => 1,
            'list_show_price'  => 1,
            'list_show_countdown' => 1,
            'list_empty'       => '',
            // Alertes
            'sub_doubleoptin'  => 1,
            'sub_title'        => '',
            'sub_text'         => '',
            'sub_consent'      => '',
            'sub_min_delay'    => 3,
            'sub_rate_max'     => 5,
            'sub_product'      => 1,
            // Cron & récapitulatif
            'cron_token'       => '',
            'digest_on'        => 0,
            'digest_times'     => 1,
            'digest_freq'      => 'week',
            'digest_mode'      => 'all',
            'digest_max'       => 10,
            'digest_batch'     => 300,
        ];
    }

    public static function reset(): void
    {
        self::$params = null;
    }

    private static function registry(): Registry
    {
        if (self::$params === null) {
            self::$params = ComponentHelper::getParams(self::COMPONENT);
        }

        return self::$params;
    }

    /** @return mixed */
    public static function get(string $key, $default = null)
    {
        $defaults = self::defaults();
        $fallback = $default ?? ($defaults[$key] ?? null);
        $value    = self::registry()->get($key, null);

        return ($value === null || $value === '') ? $fallback : $value;
    }

    /** Réglage booléen : une valeur « 0 » enregistrée doit rester 0 (et non retomber sur le défaut). */
    public static function flag(string $key): bool
    {
        $value = self::registry()->get($key, null);
        if ($value === null || $value === '') {
            $value = self::defaults()[$key] ?? 0;
        }

        return (bool) (int) $value;
    }

    public static function int(string $key): int
    {
        $value = self::registry()->get($key, null);
        if ($value === null || $value === '') {
            $value = self::defaults()[$key] ?? 0;
        }

        return (int) $value;
    }

    public static function text(string $key): string
    {
        return trim((string) self::registry()->get($key, ''));
    }

    /** Variables CSS (nom => valeur) issues des réglages de couleurs. */
    public static function cssVars(): array
    {
        $map = [
            'c_accent' => 'accent', 'c_on_accent' => 'on-accent', 'c_box_bg' => 'box-bg', 'c_border' => 'border',
            'c_count_bg' => 'count-bg', 'c_count_text' => 'count-text', 'c_badge_bg' => 'badge-bg',
            'c_ticker_bg' => 'ticker-bg', 'c_ticker_text' => 'ticker-text',
        ];
        $out = [];

        foreach ($map as $key => $css) {
            $col = (string) self::get($key);

            if (preg_match('/^#[0-9a-fA-F]{6}$/', $col)) {
                $out['--wspo-' . $css] = $col;
            }
        }

        return $out;
    }
}
