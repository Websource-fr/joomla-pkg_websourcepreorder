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
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Abonnés aux alertes : inscription (double opt-in), confirmation, désinscription, anti-spam.
 */
final class SubscriberService
{
    private static function db(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }

    public static function byEmail(string $email): ?object
    {
        $db = self::db();
        $db->setQuery(
            $db->getQuery(true)->select('*')->from($db->quoteName('#__websourcepreorder_subscribers'))
                ->where($db->quoteName('email') . ' = :e')->bind(':e', $email)
        );

        return $db->loadObject() ?: null;
    }

    public static function byToken(string $token): ?object
    {
        if (!preg_match('/^[a-zA-Z0-9]{16,64}$/', $token)) {
            return null;
        }

        $db = self::db();
        $db->setQuery(
            $db->getQuery(true)->select('*')->from($db->quoteName('#__websourcepreorder_subscribers'))
                ->where($db->quoteName('token') . ' = :t')->bind(':t', $token)
        );

        return $db->loadObject() ?: null;
    }

    /** Jeton horodaté signé (anti-spam « délai minimum »). */
    public static function timeToken(int $ts): string
    {
        return $ts . '.' . hash_hmac('sha256', 'wspo-' . $ts, (string) Factory::getApplication()->get('secret'));
    }

    public static function validTimeToken(string $value): ?int
    {
        $parts = explode('.', $value, 2);

        if (\count($parts) !== 2 || !ctype_digit($parts[0])) {
            return null;
        }

        return hash_equals(self::timeToken((int) $parts[0]), $value) ? (int) $parts[0] : null;
    }

    /** Limite par IP et par heure. @return bool true si la requête est autorisée */
    public static function rateLimitOk(string $ip): bool
    {
        $max = max(1, Settings::int('sub_rate_max'));
        $db  = self::db();
        $now = time();
        $db->setQuery(
            $db->getQuery(true)->select('*')->from($db->quoteName('#__websourcepreorder_ratelimit'))
                ->where($db->quoteName('ip') . ' = :ip')->bind(':ip', $ip)
        );
        $row = $db->loadObject();

        if (!$row || $now - (int) $row->window_start > 3600) {
            $db->setQuery($db->getQuery(true)->delete($db->quoteName('#__websourcepreorder_ratelimit'))->where($db->quoteName('ip') . ' = :ip')->bind(':ip', $ip))->execute();
            $row = (object) ['ip' => $ip, 'hits' => 1, 'window_start' => $now];
            $db->insertObject('#__websourcepreorder_ratelimit', $row);

            return true;
        }

        if ((int) $row->hits >= $max) {
            return false;
        }

        $db->setQuery(
            $db->getQuery(true)->update($db->quoteName('#__websourcepreorder_ratelimit'))
                ->set($db->quoteName('hits') . ' = ' . $db->quoteName('hits') . ' + 1')
                ->where($db->quoteName('ip') . ' = :ip')->bind(':ip', $ip)
        )->execute();

        return true;
    }

    /**
     * Inscription depuis le site.
     *
     * @return string invalid|exists|confirmed|sent|error
     */
    public static function subscribe(string $email, string $source, string $tag, string $ip = ''): string
    {
        $email = trim(mb_strtolower($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            return 'invalid';
        }

        $sub = self::byEmail($email);

        if ($sub && (int) $sub->confirmed) {
            return 'exists';
        }

        $double = Settings::flag('sub_doubleoptin');
        $db     = self::db();
        $now    = Env::nowSql();

        if (!$sub) {
            $db->setQuery(
                $db->getQuery(true)->select($db->quoteName('id'))->from($db->quoteName('#__users'))
                    ->where('LOWER(' . $db->quoteName('email') . ') = :e')->bind(':e', $email)
            );
            $sub = (object) [
                'email'        => $email,
                'user_id'      => (int) $db->loadResult(),
                'token'        => UserHelper::genRandomPassword(32),
                'created'      => $now,
                'confirmed'    => 0,
                'confirmed_at' => null,
                'last_sent'    => 0,
                'lang'         => $tag,
                'source'       => mb_substr($source, 0, 32),
                'ip'           => mb_substr($ip, 0, 45),
            ];
            $sub->confirmed = $double ? 0 : 1;
            $sub->confirmed_at = $double ? null : $now;
            $db->insertObject('#__websourcepreorder_subscribers', $sub, 'id');
        } else {
            $sub->lang      = $tag;
            $sub->source    = mb_substr($source, 0, 32);
            $sub->ip        = mb_substr($ip, 0, 45);
            $sub->confirmed = $double ? 0 : 1;
            $sub->confirmed_at = $double ? null : $now;
            $db->updateObject('#__websourcepreorder_subscribers', $sub, 'id', true);
        }

        if (!$double) {
            return 'confirmed';
        }

        $ok = MailService::send('optin', $email, $tag, [
            'confirm_url' => Urls::task('subscribe.confirm', ['token' => $sub->token]),
        ]);

        return $ok ? 'sent' : 'error';
    }

    public static function confirm(string $token): bool
    {
        $sub = self::byToken($token);

        if (!$sub) {
            return false;
        }

        if (!(int) $sub->confirmed) {
            $db  = self::db();
            $now = Env::nowSql();
            $id  = (int) $sub->id;
            $db->setQuery(
                $db->getQuery(true)->update($db->quoteName('#__websourcepreorder_subscribers'))
                    ->set($db->quoteName('confirmed') . ' = 1')->set($db->quoteName('confirmed_at') . ' = :d')
                    ->where($db->quoteName('id') . ' = :i')->bind(':d', $now)->bind(':i', $id, ParameterType::INTEGER)
            )->execute();
        }

        return true;
    }

    public static function unsubscribe(string $token): bool
    {
        $sub = self::byToken($token);

        if (!$sub) {
            return false;
        }

        $db = self::db();
        $id = (int) $sub->id;
        $db->setQuery($db->getQuery(true)->delete($db->quoteName('#__websourcepreorder_subscribers'))->where($db->quoteName('id') . ' = :i')->bind(':i', $id, ParameterType::INTEGER))->execute();

        return true;
    }
}
