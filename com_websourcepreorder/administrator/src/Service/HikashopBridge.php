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
 * Pont vers HikaShop : tout ce qui touche à l'API ou au schéma HikaShop est isolé ici.
 */
final class HikashopBridge
{
    /** @var array<int,object|null> */
    private static $products = [];

    /** @var array<int,int> */
    private static $parents = [];

    private static function db(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }

    /** Charge l'API HikaShop. @return bool true si HikaShop est installé et utilisable. */
    public static function load(): bool
    {
        if (\function_exists('hikashop_config') && \defined('HIKASHOP_COMPONENT')) {
            return true;
        }

        $helper = JPATH_ADMINISTRATOR . '/components/com_hikashop/helpers/helper.php';

        if (!is_file($helper)) {
            return false;
        }

        try {
            include_once $helper;
        } catch (\Throwable $e) {
            return false;
        }

        return \function_exists('hikashop_config');
    }

    public static function isInstalled(): bool
    {
        return is_file(JPATH_ADMINISTRATOR . '/components/com_hikashop/helpers/helper.php');
    }

    public static function reset(): void
    {
        self::$products = [];
        self::$parents  = [];
    }

    /** Ligne produit HikaShop (id, nom, code, alias, parent, publié, quantité). */
    public static function product(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        if (!\array_key_exists($id, self::$products)) {
            $db    = self::db();
            $query = $db->getQuery(true)
                ->select($db->quoteName(['product_id', 'product_parent_id', 'product_name', 'product_code', 'product_alias', 'product_published', 'product_quantity', 'product_type']))
                ->from($db->quoteName('#__hikashop_product'))
                ->where($db->quoteName('product_id') . ' = :id')
                ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);
            $db->setQuery($query);
            self::$products[$id] = $db->loadObject() ?: null;
        }

        return self::$products[$id];
    }

    public static function productName(int $id): string
    {
        $p = self::product($id);

        if (!$p) {
            return '#' . $id;
        }

        $name = (string) $p->product_name;

        if ((int) $p->product_parent_id > 0) {
            $parent = self::product((int) $p->product_parent_id);
            $name   = ($parent ? $parent->product_name : '') . ' - ' . $name;
        }

        return $name;
    }

    /** Identifiant du produit parent (0 si le produit n'est pas une variante). */
    public static function parentId(int $id): int
    {
        if (!isset(self::$parents[$id])) {
            $p                  = self::product($id);
            self::$parents[$id] = $p ? (int) $p->product_parent_id : 0;
        }

        return self::$parents[$id];
    }

    /**
     * Précharge les parents de plusieurs produits en une seule requête.
     *
     * @param int[] $ids
     */
    public static function preloadParents(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function ($i) {
            return $i > 0 && !isset(self::$parents[$i]);
        })));

        if (!$ids) {
            return;
        }

        $db    = self::db();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['product_id', 'product_parent_id']))
            ->from($db->quoteName('#__hikashop_product'))
            ->whereIn($db->quoteName('product_id'), $ids);
        $db->setQuery($query);

        foreach ((array) $db->loadObjectList() as $row) {
            self::$parents[(int) $row->product_id] = (int) $row->product_parent_id;
        }

        foreach ($ids as $id) {
            self::$parents[$id] = self::$parents[$id] ?? 0;
        }
    }

    /** @return int[] identifiants des variantes d'un produit */
    public static function variantIds(int $id): array
    {
        $db    = self::db();
        $query = $db->getQuery(true)
            ->select($db->quoteName('product_id'))
            ->from($db->quoteName('#__hikashop_product'))
            ->where($db->quoteName('product_parent_id') . ' = :id')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);

        return array_map('intval', (array) $db->loadColumn());
    }

    /** Recherche de produits (sélecteur d'une règle). @return array<int,array{id:int,name:string}> */
    public static function search(string $term, int $limit = 15): array
    {
        $db   = self::db();
        $like = '%' . $db->escape($term, true) . '%';
        $id   = (int) $term;
        $query = $db->getQuery(true)
            ->select($db->quoteName(['product_id', 'product_parent_id', 'product_name', 'product_code']))
            ->from($db->quoteName('#__hikashop_product'))
            ->where('(' . $db->quoteName('product_name') . ' LIKE :like OR ' . $db->quoteName('product_code') . ' LIKE :like2 OR ' . $db->quoteName('product_id') . ' = :id)')
            ->bind(':like', $like)
            ->bind(':like2', $like)
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER)
            ->where($db->quoteName('product_type') . ' IN (' . $db->quote('main') . ',' . $db->quote('variant') . ')')
            ->order($db->quoteName('product_name') . ' ASC');
        $db->setQuery($query, 0, $limit);
        $out = [];

        foreach ((array) $db->loadObjectList() as $row) {
            $name = (string) $row->product_name;

            if ((int) $row->product_parent_id > 0) {
                $parent = self::product((int) $row->product_parent_id);
                $name   = ($parent ? $parent->product_name : '') . ' - ' . $name;
            }

            $out[] = ['id' => (int) $row->product_id, 'name' => trim($name . ($row->product_code !== '' ? ' (' . $row->product_code . ')' : ''))];
        }

        return $out;
    }

    /** URL absolue de la fiche produit. */
    public static function productUrl(int $id): string
    {
        $p = self::product($id);

        if (!$p) {
            return Urls::root();
        }

        $alias = $p->product_alias !== '' ? $p->product_alias : \Joomla\CMS\Filter\OutputFilter::stringURLSafe($p->product_name);

        return Urls::site('index.php?option=com_hikashop&ctrl=product&task=show&cid=' . (int) $p->product_id . '&name=' . $alias);
    }

    /** URL absolue de la première image du produit (ou de son parent), chaîne vide sinon. */
    public static function productImage(int $id): string
    {
        $ids = [$id];

        if ($parent = self::parentId($id)) {
            $ids[] = $parent;
        }

        $db = self::db();

        foreach ($ids as $pid) {
            $query = $db->getQuery(true)
                ->select($db->quoteName('file_path'))
                ->from($db->quoteName('#__hikashop_file'))
                ->where($db->quoteName('file_type') . ' = ' . $db->quote('product'))
                ->where($db->quoteName('file_ref_id') . ' = :pid')
                ->order($db->quoteName('file_ordering') . ' ASC')
                ->bind(':pid', $pid, \Joomla\Database\ParameterType::INTEGER);
            $db->setQuery($query, 0, 1);
            $path = (string) $db->loadResult();

            if ($path !== '') {
                $folder = 'media/com_hikashop/upload/';

                if (self::load()) {
                    $folder = trim((string) hikashop_config()->get('uploadfolder', 'media/com_hikashop/upload/'), '/') . '/';
                }

                return Urls::absolute($folder . str_replace('%2F', '/', rawurlencode($path)));
            }
        }

        return '';
    }

    /**
     * Prix affiché (TTC si la boutique affiche TTC) formaté, avec la devise courante.
     * Les prix de précommande sont déjà appliqués par l'événement onAfterLoadProductPrice.
     */
    public static function priceHtml(int $id): string
    {
        if (!self::load()) {
            return '';
        }

        try {
            $productClass = hikashop_get('class.product');
            $currency     = hikashop_get('class.currency');
            $product      = $productClass->get($id);

            if (!$product) {
                return '';
            }

            $rows = [$product];
            $cur  = hikashop_getCurrency();
            $currency->getListingPrices($rows, hikashop_getZone(), $cur, 'cheapest');
            $row = $rows[0];

            if (empty($row->prices)) {
                return '';
            }

            $price   = reset($row->prices);
            $withTax = (int) hikashop_config()->get('price_with_tax', 1);
            $value   = $withTax && isset($price->price_value_with_tax) ? $price->price_value_with_tax : $price->price_value;

            return $currency->format($value, $price->price_currency_id);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Montant HT brut du premier prix (hors règles de précommande), pour calculer un pourcentage d'économie. */
    public static function rawPrice(int $id): float
    {
        $db    = self::db();
        $query = $db->getQuery(true)
            ->select('MIN(' . $db->quoteName('price_value') . ')')
            ->from($db->quoteName('#__hikashop_price'))
            ->where($db->quoteName('price_product_id') . ' = :id')
            ->where($db->quoteName('price_min_quantity') . ' <= 1')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);

        return (float) $db->loadResult();
    }

    /** Utilisateur HikaShop du visiteur courant (0 si invité non identifié). */
    public static function currentHikaUserId(): int
    {
        if (!self::load()) {
            return 0;
        }

        try {
            return (int) hikashop_loadUser(false);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Statuts de commande HikaShop considérés comme annulés/remboursés. */
    public static function cancelStatuses(): array
    {
        return ['cancelled', 'refunded'];
    }
}
