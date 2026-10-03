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
use Joomla\Database\ParameterType;

/**
 * Règles de précommande : états, application/restauration du stock, prix, quotas et limites.
 */
final class RuleService
{
    public const S_PENDING = 0; // programmée / inactive : rien n'est appliqué
    public const S_RUNNING = 1; // en cours : stock et prix appliqués
    public const S_ENDED   = 2; // terminée

    public const PRICE_NONE    = 0;
    public const PRICE_FIXED   = 1; // prix fixe HT
    public const PRICE_PERCENT = 2; // remise en %
    public const PRICE_AMOUNT  = 3; // remise en montant (devise principale)

    /** @var array<int,object>|null règles en cours, par identifiant */
    private static $running = null;

    /** @var array<int,object>|null index produit => règle */
    private static $byProduct = null;

    private static function db(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }

    public static function resetCache(): void
    {
        self::$running   = null;
        self::$byProduct = null;
    }

    /* ------------------------------------------------------------------ états */

    /** Fin effective des ventes (timestamp) ou null = illimitée. */
    public static function effectiveEnd(object $r): ?int
    {
        $ends = [];

        if ($t = Env::ts($r->date_end ?? null)) {
            $ends[] = $t;
        }

        if (!empty($r->end_on_release) && ($t = Env::ts($r->date_release ?? null))) {
            $ends[] = $t;
        }

        return $ends ? min($ends) : null;
    }

    public static function desiredState(object $r, ?int $now = null): int
    {
        $now   = $now ?: time();
        $start = Env::ts($r->date_start ?? null);
        $end   = self::effectiveEnd($r);

        if ($end !== null && $now >= $end) {
            return self::S_ENDED;
        }

        if (empty($r->published)) {
            return self::S_PENDING;
        }

        if ($start !== null && $now < $start) {
            return self::S_PENDING;
        }

        return self::S_RUNNING;
    }

    public static function get(int $id): ?object
    {
        $db    = self::db();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__websourcepreorder_rules'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);

        return $db->loadObject() ?: null;
    }

    /** @return array<int,object> règles actuellement en précommande, triées par date de sortie */
    public static function running(): array
    {
        if (self::$running === null) {
            self::$running = [];

            try {
                $db    = self::db();
                $query = $db->getQuery(true)
                    ->select('*')
                    ->from($db->quoteName('#__websourcepreorder_rules'))
                    ->where($db->quoteName('published') . ' = 1')
                    ->where($db->quoteName('state') . ' < 2');
                $db->setQuery($query);
                $list = [];

                foreach ((array) $db->loadObjectList() as $r) {
                    if (self::desiredState($r) === self::S_RUNNING) {
                        $list[] = $r;
                    }
                }

                usort($list, static function ($a, $b) {
                    $ta = Env::ts($a->date_release) ?: PHP_INT_MAX;
                    $tb = Env::ts($b->date_release) ?: PHP_INT_MAX;

                    return $ta <=> $tb;
                });

                foreach ($list as $r) {
                    self::$running[(int) $r->id] = $r;
                }
            } catch (\Throwable $e) {
                // tables absentes (installation en cours) : aucune règle
                self::$running = [];
            }
        }

        return self::$running;
    }

    /** Règle en cours qui s'applique à un produit HikaShop (le produit lui-même, sinon son parent). */
    public static function findRule(int $productId): ?object
    {
        $running = self::running();

        if (!$running || $productId <= 0) {
            return null;
        }

        if (self::$byProduct === null) {
            self::$byProduct = [];

            foreach ($running as $r) {
                if (!isset(self::$byProduct[(int) $r->product_id])) {
                    self::$byProduct[(int) $r->product_id] = $r;
                }
            }
        }

        if (isset(self::$byProduct[$productId])) {
            return self::$byProduct[$productId];
        }

        $parent = HikashopBridge::parentId($productId);

        return $parent && isset(self::$byProduct[$parent]) ? self::$byProduct[$parent] : null;
    }

    /** Traduction (libellé / message) d'une règle pour une langue donnée. @return array{label:string,message:string} */
    public static function langData(object $rule, ?string $tag = null): array
    {
        $tag    = $tag ?: Env::langTag();
        $label  = (string) ($rule->label ?? '');
        $message = (string) ($rule->message ?? '');
        $trans  = json_decode((string) ($rule->translations ?? ''), true);

        if (\is_array($trans)) {
            foreach ($trans as $t) {
                if (!\is_array($t) || ($t['language'] ?? '') !== $tag) {
                    continue;
                }

                if (trim((string) ($t['label'] ?? '')) !== '') {
                    $label = (string) $t['label'];
                }

                if (trim((string) ($t['message'] ?? '')) !== '') {
                    $message = (string) $t['message'];
                }
            }
        }

        return ['label' => $label, 'message' => $message];
    }

    /* ------------------------------------------------------------------ synchronisation */

    /** Synchronise toutes les règles (cron, tâche planifiée, visite du site). @return int nombre de règles dont l'état a changé */
    public static function syncAll(): int
    {
        $n   = 0;
        $db  = self::db();
        $db->setQuery($db->getQuery(true)->select($db->quoteName('id'))->from($db->quoteName('#__websourcepreorder_rules')));

        foreach ((array) $db->loadColumn() as $id) {
            $rule = self::get((int) $id);

            if ($rule && self::syncRule($rule)) {
                ++$n;
            }
        }

        self::resetCache();
        State::set('last_sync', (string) time());
        BookingService::releaseUnpaid();

        return $n;
    }

    /** @return bool true si l'état a changé */
    public static function syncRule(object $rule): bool
    {
        $desired = self::desiredState($rule);
        $old     = (int) $rule->state;

        if ($desired === $old) {
            return false;
        }

        if ($old === self::S_RUNNING) {
            self::revert($rule);
        }

        if ($desired === self::S_RUNNING) {
            self::apply($rule);
        }

        $rule->state = $desired;
        self::store($rule);
        self::resetCache();

        if ($old === self::S_RUNNING && $desired === self::S_ENDED) {
            $rel = Env::ts($rule->date_release);

            if ($rel !== null && $rel <= time() + 60) {
                BookingService::notifyAvailable($rule);
            }
        }

        return true;
    }

    /** Enregistre uniquement les champs d'état d'une règle. */
    private static function store(object $rule): void
    {
        $db    = self::db();
        $state = (int) $rule->state;
        $prev  = (string) ($rule->prev_state ?? '');
        $date  = (string) ($rule->date_release ?? '');
        $end   = (int) ($rule->end_on_release ?? 0);
        $id    = (int) $rule->id;
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__websourcepreorder_rules'))
            ->set($db->quoteName('state') . ' = :s')
            ->set($db->quoteName('prev_state') . ' = :p')
            ->set($db->quoteName('end_on_release') . ' = :e')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':s', $state, ParameterType::INTEGER)
            ->bind(':p', $prev)
            ->bind(':e', $end, ParameterType::INTEGER)
            ->bind(':id', $id, ParameterType::INTEGER);

        if ($date !== '') {
            $query->set($db->quoteName('date_release') . ' = :d')->bind(':d', $date);
        }

        $db->setQuery($query)->execute();
    }

    /** Identifiants des produits touchés par la règle : le produit et ses variantes. */
    private static function targetIds(object $rule): array
    {
        $pid = (int) $rule->product_id;

        return array_merge([$pid], HikashopBridge::variantIds($pid));
    }

    /** Autorise la commande hors stock (quantité illimitée) en mémorisant l'état d'origine. */
    public static function apply(object $rule): void
    {
        $ids = self::targetIds($rule);
        $db  = self::db();
        $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['product_id', 'product_quantity']))
                ->from($db->quoteName('#__hikashop_product'))
                ->whereIn($db->quoteName('product_id'), $ids)
        );
        $prev = [];

        foreach ((array) $db->loadObjectList() as $row) {
            $prev[(int) $row->product_id] = (int) $row->product_quantity;
        }

        $rule->prev_state = json_encode($prev);

        if ($prev) {
            $db->setQuery(
                $db->getQuery(true)
                    ->update($db->quoteName('#__hikashop_product'))
                    ->set($db->quoteName('product_quantity') . ' = -1')
                    ->whereIn($db->quoteName('product_id'), array_keys($prev))
            )->execute();
        }

        HikashopBridge::reset();
    }

    /** Restaure exactement l'état d'origine du stock. */
    public static function revert(object $rule): void
    {
        $prev = json_decode((string) ($rule->prev_state ?? ''), true);
        $db   = self::db();

        if (\is_array($prev)) {
            foreach ($prev as $pid => $qty) {
                $pid   = (int) $pid;
                $qty   = (int) $qty;
                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__hikashop_product'))
                    ->set($db->quoteName('product_quantity') . ' = :q')
                    ->where($db->quoteName('product_id') . ' = :id')
                    ->bind(':q', $qty, ParameterType::INTEGER)
                    ->bind(':id', $pid, ParameterType::INTEGER);
                $db->setQuery($query)->execute();
            }
        }

        $rule->prev_state = '';
        HikashopBridge::reset();
    }

    /** Recalcule une règle après modification : restaure puis ré-applique si elle était en cours. */
    public static function refresh(int $id): void
    {
        $rule = self::get($id);

        if (!$rule) {
            return;
        }

        if ((int) $rule->state === self::S_RUNNING) {
            self::revert($rule);
            $rule->state = self::S_PENDING;
            self::store($rule);
        } elseif ((int) $rule->state === self::S_ENDED) {
            // Une règle terminée dont on repousse la date de sortie peut redevenir en cours.
            $rule->state = self::S_PENDING;
            self::store($rule);
        }

        self::resetCache();
        self::syncRule(self::get($id));
    }

    /** Supprime les effets d'une règle (avant suppression). */
    public static function disable(int $id): void
    {
        $rule = self::get($id);

        if ($rule && (int) $rule->state === self::S_RUNNING) {
            self::revert($rule);
            $rule->state = self::S_ENDED;
            self::store($rule);
        }

        self::resetCache();
    }

    /** Marque comme disponible maintenant : termine la règle et prévient les acheteurs. */
    public static function releaseNow(int $id): bool
    {
        $rule = self::get($id);

        if (!$rule) {
            return false;
        }

        $db   = self::db();
        $now  = Env::nowSql();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__websourcepreorder_rules'))
            ->set($db->quoteName('date_release') . ' = :d')
            ->set($db->quoteName('end_on_release') . ' = 1')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':d', $now)
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
        self::resetCache();

        return self::syncRule(self::get($id));
    }

    /** Restaure toutes les règles en cours (désinstallation). */
    public static function revertAll(): void
    {
        $db = self::db();
        $db->setQuery($db->getQuery(true)->select($db->quoteName('id'))->from($db->quoteName('#__websourcepreorder_rules'))->where($db->quoteName('state') . ' = 1'));

        foreach ((array) $db->loadColumn() as $id) {
            self::disable((int) $id);
        }
    }

    /* ------------------------------------------------------------------ prix */

    /**
     * Applique le prix de précommande aux lignes de prix HikaShop (événement onAfterLoadProductPrice).
     * Rien n'est enregistré : le prix d'origine du produit n'est jamais modifié.
     *
     * @param object[] $prices
     */
    public static function applyPrices(array $prices): void
    {
        if (!$prices || !self::running()) {
            return;
        }

        HikashopBridge::preloadParents(array_map(static function ($p) {
            return (int) $p->price_product_id;
        }, $prices));
        $main = 1;

        if (HikashopBridge::load()) {
            $main = (int) hikashop_config()->get('main_currency', 1);
        }

        foreach ($prices as $price) {
            if (!\is_object($price) || !empty($price->wspo_applied)) {
                continue;
            }

            $rule = self::findRule((int) $price->price_product_id);

            if (!$rule || (int) $rule->price_mode === self::PRICE_NONE || (float) $rule->price_value <= 0) {
                continue;
            }

            $value = (float) $rule->price_value;
            $old   = (float) $price->price_value;

            // Les remises en montant et les prix fixes sont exprimés dans la devise principale de la boutique.
            $isMain = (int) $price->price_currency_id === $main;

            switch ((int) $rule->price_mode) {
                case self::PRICE_FIXED:
                    if ($isMain) {
                        $price->price_value = $value;
                    } else {
                        $price->price_value = $old; // devises secondaires : conversion HikaShop sur le prix principal
                    }
                    break;
                case self::PRICE_PERCENT:
                    $price->price_value = round($old * (1 - min(100.0, $value) / 100), 5);
                    break;
                case self::PRICE_AMOUNT:
                    if ($isMain) {
                        $price->price_value = max(0.0, round($old - $value, 5));
                    }
                    break;
            }

            $price->wspo_applied = 1;
        }
    }

    /* ------------------------------------------------------------------ quotas */

    /** Unités réservées (hors annulées) pour une règle, éventuellement pour un client HikaShop. */
    public static function booked(int $ruleId, ?int $hikaUserId = null): int
    {
        $db    = self::db();
        $query = $db->getQuery(true)
            ->select('COALESCE(SUM(' . $db->quoteName('quantity') . '), 0)')
            ->from($db->quoteName('#__websourcepreorder_bookings'))
            ->where($db->quoteName('rule_id') . ' = :r')
            ->where($db->quoteName('status') . ' != ' . BookingService::CANCELLED)
            ->bind(':r', $ruleId, ParameterType::INTEGER);

        if ($hikaUserId !== null) {
            $query->where($db->quoteName('hika_user_id') . ' = :u')->bind(':u', $hikaUserId, ParameterType::INTEGER);
        }

        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    /** @return int|null null = illimité */
    public static function remaining(object $rule): ?int
    {
        if (!(int) $rule->quota) {
            return null;
        }

        return max(0, (int) $rule->quota - self::booked((int) $rule->id));
    }

    /**
     * Contrôle d'une ligne de panier.
     *
     * @param object   $product       produit HikaShop (product_id…)
     * @param int      $quantity      quantité souhaitée pour la ligne
     * @param object[] $cartProducts  lignes du panier (cart_product_id => ligne)
     * @param mixed    $cartLineId    identifiant de la ligne contrôlée
     *
     * @return array{0:int,1:?string} quantité autorisée et message éventuel
     */
    public static function checkLine(object $product, int $quantity, array $cartProducts, $cartLineId): array
    {
        $pid  = (int) ($product->product_id ?? 0);
        $rule = self::findRule($pid);

        // Préchargement des parents pour les autres lignes.
        $lineIds = [];

        foreach ($cartProducts as $line) {
            $lineIds[] = (int) ($line->product_id ?? 0);
        }

        HikashopBridge::preloadParents($lineIds);

        if (Settings::get('mixed_cart') === 'forbid') {
            $mine = $rule !== null;

            foreach ($cartProducts as $key => $line) {
                if ((string) $key === (string) $cartLineId || empty($line->product_id)) {
                    continue;
                }

                $other = self::findRule((int) $line->product_id) !== null;

                // La ligne la plus récente est refusée quand les deux types se mélangent.
                if ($other !== $mine && (!is_numeric($key) || !is_numeric($cartLineId) || (int) $key < (int) $cartLineId || (int) $cartLineId === 0)) {
                    return [0, $mine
                        ? \Joomla\CMS\Language\Text::_('COM_WEBSOURCEPREORDER_ERR_MIXED_PRE')
                        : \Joomla\CMS\Language\Text::_('COM_WEBSOURCEPREORDER_ERR_MIXED_STD')];
                }
            }
        }

        if (!$rule) {
            return [$quantity, null];
        }

        // Quantité déjà présente dans les autres lignes couvertes par la même règle.
        $others = 0;

        foreach ($cartProducts as $key => $line) {
            if ((string) $key === (string) $cartLineId || empty($line->product_id)) {
                continue;
            }

            $r = self::findRule((int) $line->product_id);

            if ($r && (int) $r->id === (int) $rule->id) {
                $others += (int) ($line->cart_product_quantity ?? 0);
            }
        }

        $allowed = $quantity;
        $msg     = null;
        $Text    = \Joomla\CMS\Language\Text::class;

        if ((int) $rule->max_per_order > 0) {
            $cap = (int) $rule->max_per_order - $others;

            if ($quantity > $cap) {
                $allowed = max(0, $cap);
                $msg     = $Text::sprintf('COM_WEBSOURCEPREORDER_ERR_MAX_ORDER', (int) $rule->max_per_order);
            }
        }

        $remaining = self::remaining($rule);

        if ($remaining !== null && $allowed > $remaining - $others) {
            $allowed = max(0, $remaining - $others);
            $msg     = $remaining > 0
                ? $Text::sprintf('COM_WEBSOURCEPREORDER_ERR_REMAINING', $remaining)
                : $Text::_('COM_WEBSOURCEPREORDER_ERR_FULL');
        }

        $hikaUser = HikashopBridge::currentHikaUserId();

        if ((int) $rule->max_per_customer > 0 && $hikaUser) {
            $cap = (int) $rule->max_per_customer - self::booked((int) $rule->id, $hikaUser) - $others;

            if ($allowed > $cap) {
                $allowed = max(0, $cap);
                $msg     = $Text::sprintf('COM_WEBSOURCEPREORDER_ERR_MAX_CUSTOMER', (int) $rule->max_per_customer);
            }
        }

        return [$allowed, $allowed < $quantity ? $msg : null];
    }
}
