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
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Réservations créées à la commande HikaShop, annulation/remboursement, e-mails de confirmation et de disponibilité.
 */
final class BookingService
{
    public const RESERVED  = 0;
    public const NOTIFIED  = 1;
    public const CANCELLED = 2;

    private static function db(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }

    /** Statuts de commande considérés comme annulés. */
    public static function cancelStatuses(): array
    {
        $raw = Settings::text('cancel_statuses');
        $list = array_filter(array_map('trim', explode(',', $raw !== '' ? $raw : 'cancelled,refunded')));

        return $list ?: ['cancelled', 'refunded'];
    }

    /** Crée les réservations d'une commande HikaShop. @return object[] réservations créées */
    public static function createFromOrder(int $orderId): array
    {
        $db = self::db();
        $db->setQuery(
            $db->getQuery(true)->select('*')->from($db->quoteName('#__hikashop_order'))
                ->where($db->quoteName('order_id') . ' = :o')->bind(':o', $orderId, ParameterType::INTEGER)
        );
        $order = $db->loadObject();

        if (!$order || (string) $order->order_type !== 'sale') {
            return [];
        }

        $db->setQuery(
            $db->getQuery(true)->select('*')->from($db->quoteName('#__hikashop_order_product'))
                ->where($db->quoteName('order_id') . ' = :o')->bind(':o', $orderId, ParameterType::INTEGER)
        );
        $lines = (array) $db->loadObjectList();

        HikashopBridge::preloadParents(array_map(static function ($l) {
            return (int) $l->product_id;
        }, $lines));

        [$email, $name, $joomlaUser] = self::customer((int) $order->order_user_id);
        $lang   = (string) $order->order_lang;
        $cancel = \in_array((string) $order->order_status, self::cancelStatuses(), true);
        $out    = [];

        foreach ($lines as $l) {
            $rule = RuleService::findRule((int) $l->product_id);

            if (!$rule) {
                continue;
            }

            $opId = (int) $l->order_product_id;
            $db->setQuery(
                $db->getQuery(true)->select($db->quoteName('id'))->from($db->quoteName('#__websourcepreorder_bookings'))
                    ->where($db->quoteName('order_product_id') . ' = :op')->bind(':op', $opId, ParameterType::INTEGER)
            );

            if ($db->loadResult()) {
                continue;
            }

            $row = (object) [
                'rule_id'          => (int) $rule->id,
                'order_id'         => $orderId,
                'order_product_id' => $opId,
                'hika_user_id'     => (int) $order->order_user_id,
                'user_id'          => $joomlaUser,
                'email'            => $email,
                'customer_name'    => $name,
                'lang'             => $lang,
                'product_id'       => (int) $l->product_id,
                'product_name'     => mb_substr((string) $l->order_product_name, 0, 255),
                'quantity'         => max(1, (int) $l->order_product_quantity),
                'unit_price'       => (float) $l->order_product_price + (float) $l->order_product_tax,
                'release_date'     => $rule->date_release ?: null,
                'status'           => $cancel ? self::CANCELLED : self::RESERVED,
                'created'          => Env::nowSql(),
                'notified'         => null,
            ];

            $db->insertObject('#__websourcepreorder_bookings', $row, 'id');
            $out[] = $row;
        }

        return $out;
    }

    /** @return array{0:string,1:string,2:int} e-mail, nom, identifiant utilisateur Joomla */
    private static function customer(int $hikaUserId): array
    {
        $db = self::db();
        $db->setQuery(
            $db->getQuery(true)->select($db->quoteName(['user_email', 'user_cms_id']))->from($db->quoteName('#__hikashop_user'))
                ->where($db->quoteName('user_id') . ' = :u')->bind(':u', $hikaUserId, ParameterType::INTEGER)
        );
        $u = $db->loadObject();

        if (!$u) {
            return ['', '', 0];
        }

        $name = '';
        $cms  = (int) $u->user_cms_id;

        if ($cms) {
            $db->setQuery(
                $db->getQuery(true)->select($db->quoteName('name'))->from($db->quoteName('#__users'))
                    ->where($db->quoteName('id') . ' = :i')->bind(':i', $cms, ParameterType::INTEGER)
            );
            $name = (string) $db->loadResult();
        }

        return [(string) $u->user_email, $name, $cms];
    }

    /** Appelé à la création d'une commande : crée les réservations et envoie les e-mails de confirmation. */
    public static function onOrderCreated(int $orderId): void
    {
        Env::loadLanguage();
        $created = self::createFromOrder($orderId);

        if (!$created) {
            return;
        }

        $db = self::db();
        $db->setQuery(
            $db->getQuery(true)->select($db->quoteName(['order_number', 'order_lang']))->from($db->quoteName('#__hikashop_order'))
                ->where($db->quoteName('order_id') . ' = :o')->bind(':o', $orderId, ParameterType::INTEGER)
        );
        $order = $db->loadObject();
        $tag   = (string) $order->order_lang !== '' ? (string) $order->order_lang : Env::langTag();
        $next  = null;

        foreach ($created as $b) {
            $t = Env::ts($b->release_date);

            if ($t && ($next === null || $t > $next)) {
                $next = $t;
            }
        }

        $info = $next
            ? Text::sprintf('COM_WEBSOURCEPREORDER_MAIL_RELEASE_INFO', Env::formatDate(Env::sql($next)))
            : Text::_('COM_WEBSOURCEPREORDER_MAIL_RELEASE_TBA');
        $first = $created[0];

        if (Settings::flag('mail_order_on') && $first->email !== '') {
            [$first1, $last1] = self::splitName((string) $first->customer_name);
            MailService::send('order', $first->email, $tag, [
                'firstname'       => $first1,
                'lastname'        => $last1,
                'order_reference' => (string) $order->order_number,
                'products_html'   => self::productsHtml($created),
                'release_info'    => $info,
                'account_url'     => Urls::accountPage(),
            ], (string) $first->customer_name);
        }

        $admin = Settings::text('admin_email');

        if ($admin === '') {
            $admin = (string) Factory::getApplication()->get('mailfrom', '');
        }

        if (Settings::flag('mail_admin_on') && $admin !== '') {
            MailService::send('admin', $admin, Env::langTag(), [
                'customer'        => $first->customer_name !== '' ? $first->customer_name : $first->email,
                'customer_email'  => $first->email,
                'order_reference' => (string) $order->order_number,
                'products_html'   => self::productsHtml($created),
            ]);
        }
    }

    /** @return array{0:string,1:string} */
    private static function splitName(string $name): array
    {
        $name = trim($name);
        $pos  = strpos($name, ' ');

        return $pos === false ? [$name, ''] : [substr($name, 0, $pos), trim(substr($name, $pos + 1))];
    }

    /** Tableau HTML des produits d'un e-mail. */
    public static function productsHtml(array $bookings): string
    {
        $html = '<table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;border:1px solid #e5e5e5;font-size:14px">';

        foreach ($bookings as $b) {
            $date  = Env::formatDate($b->release_date);
            $html .= '<tr><td style="border-bottom:1px solid #eee">' . htmlspecialchars((string) $b->product_name, ENT_QUOTES, 'UTF-8') . ' × ' . (int) $b->quantity . '</td>'
                . '<td align="right" style="border-bottom:1px solid #eee">' . ($date !== '' ? htmlspecialchars(Text::_('COM_WEBSOURCEPREORDER_RELEASE') . ' ' . $date, ENT_QUOTES, 'UTF-8') : '') . '</td></tr>';
        }

        return $html . '</table>';
    }

    /** Statut de commande modifié : annule ou ré-active les réservations. */
    public static function onOrderStatus(int $orderId, string $status): void
    {
        $db     = self::db();
        $cancel = \in_array($status, self::cancelStatuses(), true);
        $q      = $db->getQuery(true)->update($db->quoteName('#__websourcepreorder_bookings'))
            ->where($db->quoteName('order_id') . ' = :o')->bind(':o', $orderId, ParameterType::INTEGER);

        if ($cancel) {
            $q->set($db->quoteName('status') . ' = ' . self::CANCELLED);
        } else {
            $q->set($db->quoteName('status') . ' = ' . self::RESERVED)
                ->where($db->quoteName('status') . ' = ' . self::CANCELLED)
                ->where($db->quoteName('notified') . ' IS NULL');
        }

        $db->setQuery($q)->execute();
        RuleService::resetCache();
    }

    /** Libère les réservations de commandes restées « non payées » au-delà du délai réglé. @return int nombre libérées */
    public static function releaseUnpaid(): int
    {
        $hours = Settings::int('unpaid_release_hours');

        if ($hours <= 0) {
            return 0;
        }

        $db     = self::db();
        $before = Env::sql(time() - $hours * 3600);
        $q      = $db->getQuery(true)
            ->select($db->quoteName('b.id'))
            ->from($db->quoteName('#__websourcepreorder_bookings', 'b'))
            ->join('INNER', $db->quoteName('#__hikashop_order', 'o') . ' ON o.order_id = b.order_id')
            ->where($db->quoteName('b.status') . ' = ' . self::RESERVED)
            ->where($db->quoteName('o.order_status') . ' = ' . $db->quote('created'))
            ->where($db->quoteName('b.created') . ' < :d')
            ->bind(':d', $before);
        $db->setQuery($q);
        $ids = array_map('intval', (array) $db->loadColumn());

        if ($ids) {
            $db->setQuery(
                $db->getQuery(true)->update($db->quoteName('#__websourcepreorder_bookings'))
                    ->set($db->quoteName('status') . ' = ' . self::CANCELLED)
                    ->whereIn($db->quoteName('id'), $ids)
            )->execute();
        }

        return \count($ids);
    }

    public static function setStatus(array $ids, int $status): void
    {
        $ids = array_filter(array_map('intval', $ids));

        if (!$ids) {
            return;
        }

        $db = self::db();
        $db->setQuery(
            $db->getQuery(true)->update($db->quoteName('#__websourcepreorder_bookings'))
                ->set($db->quoteName('status') . ' = ' . (int) $status)->whereIn($db->quoteName('id'), $ids)
        )->execute();
    }

    /** E-mail « disponible » à tous les acheteurs d'une règle. @return int envoyés */
    public static function notifyAvailable(object $rule): int
    {
        $db = self::db();
        $id = (int) $rule->id;
        $db->setQuery(
            $db->getQuery(true)->select($db->quoteName('id'))->from($db->quoteName('#__websourcepreorder_bookings'))
                ->where($db->quoteName('rule_id') . ' = :r')->where($db->quoteName('status') . ' = ' . self::RESERVED)
                ->bind(':r', $id, ParameterType::INTEGER)
        );
        $sent = 0;

        foreach ((array) $db->loadColumn() as $bid) {
            if (self::notifyBooking((int) $bid)) {
                ++$sent;
            }
        }

        return $sent;
    }

    public static function notifyBooking(int $bookingId): bool
    {
        Env::loadLanguage();
        $db = self::db();
        $db->setQuery(
            $db->getQuery(true)->select('*')->from($db->quoteName('#__websourcepreorder_bookings'))
                ->where($db->quoteName('id') . ' = :i')->bind(':i', $bookingId, ParameterType::INTEGER)
        );
        $b = $db->loadObject();

        if (!$b || $b->email === '') {
            return false;
        }

        $ok = true;

        if (Settings::flag('mail_available_on')) {
            $oid = (int) $b->order_id;
            $db->setQuery(
                $db->getQuery(true)->select($db->quoteName('order_number'))->from($db->quoteName('#__hikashop_order'))
                    ->where($db->quoteName('order_id') . ' = :o')->bind(':o', $oid, ParameterType::INTEGER)
            );
            $orderRef = (string) $db->loadResult();
            [$fn, $ln] = self::splitName((string) $b->customer_name);
            $ok = MailService::send('available', (string) $b->email, (string) $b->lang !== '' ? (string) $b->lang : Env::langTag(), [
                'firstname'       => $fn,
                'lastname'        => $ln,
                'order_reference' => $orderRef,
                'product_name'    => (string) $b->product_name,
                'product_url'     => HikashopBridge::productUrl((int) $b->product_id),
                'release_date'    => Env::formatDate($b->release_date),
                'account_url'     => Urls::accountPage(),
            ], (string) $b->customer_name);
        }

        if ($ok) {
            $now = Env::nowSql();
            $db->setQuery(
                $db->getQuery(true)->update($db->quoteName('#__websourcepreorder_bookings'))
                    ->set($db->quoteName('status') . ' = ' . self::NOTIFIED)
                    ->set($db->quoteName('notified') . ' = :n')
                    ->where($db->quoteName('id') . ' = :i')
                    ->bind(':n', $now)->bind(':i', $bookingId, ParameterType::INTEGER)
            )->execute();
        }

        return $ok;
    }
}
