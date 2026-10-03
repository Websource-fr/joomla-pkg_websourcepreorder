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

use Joomla\CMS\Layout\FileLayout;

/**
 * Préparation des données d'affichage et rendu des layouts (surchargeables dans html/layouts/com_websourcepreorder).
 */
final class DisplayHelper
{
    public static function layout(string $id, array $data): string
    {
        Env::loadLanguage();
        Assets::load();
        $layout = new FileLayout($id, null, ['component' => 'com_websourcepreorder', 'client' => 0]);

        return $layout->render($data);
    }

    /** Pourcentage de progression entre le début et la sortie. */
    public static function progress(object $rule): ?int
    {
        $rel   = Env::ts($rule->date_release);
        $start = Env::ts($rule->date_start) ?: Env::ts($rule->created);

        if (!$rel || !$start || $rel <= $start) {
            return null;
        }

        return (int) max(0, min(100, round((time() - $start) / ($rel - $start) * 100)));
    }

    /** Données du bloc d'une règle (fiche produit). */
    public static function box(object $rule): array
    {
        Env::loadLanguage();
        $lang     = RuleService::langData($rule);
        $rel      = Env::ts($rule->date_release);
        $progress = self::progress($rule);
        $raw      = HikashopBridge::rawPrice((int) $rule->product_id);
        $saving   = 0;

        if ((int) $rule->price_mode === RuleService::PRICE_PERCENT) {
            $saving = (int) round((float) $rule->price_value);
        } elseif ($raw > 0 && (int) $rule->price_mode === RuleService::PRICE_FIXED) {
            $saving = (int) round((1 - (float) $rule->price_value / $raw) * 100);
        } elseif ($raw > 0 && (int) $rule->price_mode === RuleService::PRICE_AMOUNT) {
            $saving = (int) round((float) $rule->price_value / $raw * 100);
        }

        return [
            'rule'           => $rule,
            'label'          => $lang['label'] !== '' ? $lang['label'] : Assets::buttonLabel(),
            'badge'          => Assets::badgeLabel(),
            'message'        => $lang['message'],
            'release_ts'     => $rel,
            'release_date'   => $rel ? Env::formatDate($rule->date_release) : '',
            'progress'       => $progress,
            'remaining'      => RuleService::remaining($rule),
            'saving'         => max(0, min(100, $saving)),
            'show_countdown' => Settings::flag('show_countdown') && $rel && $rel > time(),
            'show_progress'  => Settings::flag('show_progress') && $progress !== null,
            'show_quota'     => Settings::flag('show_quota'),
            'mode'           => (string) Settings::get('countdown_mode'),
        ];
    }

    /**
     * Produits en précommande présentés (liste, bandeau, à la une, blocs comptes, digest).
     *
     * @param array $filter ex. ['featured' => 1] ou ['in_ticker' => 1]
     *
     * @return array<int,array>
     */
    public static function items(array $filter = [], int $limit = 0): array
    {
        Env::loadLanguage();
        $items = [];
        $seen  = [];

        foreach (RuleService::running() as $r) {
            foreach ($filter as $k => $v) {
                if ((int) $r->$k !== (int) $v) {
                    continue 2;
                }
            }

            $pid = (int) $r->product_id;
            $p   = HikashopBridge::product($pid);

            if (isset($seen[$pid]) || !$p || !(int) $p->product_published) {
                continue;
            }

            $seen[$pid] = true;
            $box        = self::box($r);
            $items[]    = $box + [
                'product_id'  => $pid,
                'name'        => HikashopBridge::productName($pid),
                'url'         => HikashopBridge::productUrl($pid),
                'image'       => HikashopBridge::productImage($pid),
                'price'       => HikashopBridge::priceHtml($pid),
                'month'       => Env::monthKey($r->date_release),
            ];

            if ($limit && \count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    /** Variables de couleurs en attribut style (pour les modules). */
    public static function hasPreorders(): bool
    {
        return (bool) RuleService::running();
    }
}
