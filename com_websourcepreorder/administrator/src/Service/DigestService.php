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

/**
 * Récapitulatif périodique : x fois par jour / semaine / mois, par lots avec reprise, jamais d'envoi à vide.
 */
final class DigestService
{
    private static function db(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }

    /** Intervalle minimal entre deux envois (secondes). */
    public static function interval(): int
    {
        $times  = max(1, Settings::int('digest_times'));
        $period = ['day' => 86400, 'week' => 604800, 'month' => 2592000];
        $f      = (string) Settings::get('digest_freq');

        return (int) floor(($period[$f] ?? 604800) / $times);
    }

    /** Règles à présenter dans le récapitulatif. @return object[] */
    private static function rules(): array
    {
        $out = [];

        foreach (RuleService::running() as $r) {
            if ((int) $r->in_list) {
                $out[] = $r;
            }
        }

        return $out;
    }

    /** Bloc HTML des produits dans la langue donnée. */
    public static function productsHtml(array $rules, string $tag): string
    {
        $html = '<table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-size:14px">';

        foreach ($rules as $r) {
            $pid   = (int) $r->product_id;
            $name  = HikashopBridge::productName($pid);
            $url   = htmlspecialchars(HikashopBridge::productUrl($pid), ENT_QUOTES, 'UTF-8');
            $img   = HikashopBridge::productImage($pid);
            $price = HikashopBridge::priceHtml($pid);
            $date  = Env::formatDate($r->date_release);
            $html .= '<tr style="border-bottom:1px solid #eee"><td width="90">'
                . ($img !== '' ? '<a href="' . $url . '"><img src="' . htmlspecialchars($img, ENT_QUOTES, 'UTF-8') . '" width="80" alt=""></a>' : '')
                . '</td><td><a href="' . $url . '" style="color:#222;font-weight:bold;text-decoration:none">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</a><br>'
                . ($date !== '' ? '<span style="color:#666">' . htmlspecialchars(Text::_('COM_WEBSOURCEPREORDER_RELEASE') . ' ' . $date, ENT_QUOTES, 'UTF-8') . '</span><br>' : '')
                . ($price !== '' ? '<strong>' . strip_tags($price) . '</strong>' : '') . '</td></tr>';
        }

        return $html . '</table>';
    }

    /**
     * Envoie le récapitulatif si l'échéance est atteinte.
     *
     * @return array{0:int,1:string} nombre d'envois et statut (disabled|not_due|no_preorder|nothing_new|done|partial)
     */
    public static function run(bool $force = false, ?int $batch = null): array
    {
        Env::loadLanguage();

        if (!Settings::flag('digest_on') && !$force) {
            return [0, 'disabled'];
        }

        $batch = $batch ?: max(1, Settings::int('digest_batch'));
        $now   = time();
        $run   = (int) State::get('digest_run', '0');
        $last  = (int) State::get('digest_last', '0');

        if (!$run) {
            if (!$force && $now - $last < self::interval()) {
                return [0, 'not_due'];
            }

            $rules = self::rules();

            if (!$rules) {
                return [0, 'no_preorder'];
            }

            if ((string) Settings::get('digest_mode') === 'new' && !$force) {
                $hasNew = false;

                foreach ($rules as $r) {
                    if ((Env::ts($r->created) ?: 0) > $last) {
                        $hasNew = true;
                    }
                }

                if (!$hasNew) {
                    return [0, 'nothing_new'];
                }
            }

            $run = $now;
            State::set('digest_run', (string) $run);
        }

        $rules = self::rules();
        $max   = Settings::int('digest_max');

        if ($max > 0) {
            $rules = \array_slice($rules, 0, $max);
        }

        $db = self::db();
        $db->setQuery(
            $db->getQuery(true)->select('*')->from($db->quoteName('#__websourcepreorder_subscribers'))
                ->where($db->quoteName('confirmed') . ' = 1')->where($db->quoteName('last_sent') . ' < :r')
                ->bind(':r', $run, \Joomla\Database\ParameterType::INTEGER),
            0,
            $batch
        );
        $sent   = 0;
        $byLang = [];

        foreach ((array) $db->loadObjectList() as $s) {
            $tag = $s->lang !== '' ? (string) $s->lang : Env::langTag();
            $ok  = true;

            if ($rules) {
                $byLang[$tag] = $byLang[$tag] ?? self::productsHtml($rules, $tag);
                $ok = MailService::send('digest', (string) $s->email, $tag, [
                    'products_html'   => $byLang[$tag],
                    'count'           => (string) \count($rules),
                    'unsubscribe_url' => Urls::task('subscribe.unsubscribe', ['token' => $s->token]),
                    'list_url'        => Urls::listPage(),
                ]);
            }

            // L'abonné est marqué même en cas d'échec : pas de boucle infinie.
            $id = (int) $s->id;
            $db->setQuery(
                $db->getQuery(true)->update($db->quoteName('#__websourcepreorder_subscribers'))
                    ->set($db->quoteName('last_sent') . ' = :r')->where($db->quoteName('id') . ' = :i')
                    ->bind(':r', $run, \Joomla\Database\ParameterType::INTEGER)->bind(':i', $id, \Joomla\Database\ParameterType::INTEGER)
            )->execute();

            if ($ok) {
                ++$sent;
            }
        }

        $db->setQuery(
            $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__websourcepreorder_subscribers'))
                ->where($db->quoteName('confirmed') . ' = 1')->where($db->quoteName('last_sent') . ' < :r')
                ->bind(':r', $run, \Joomla\Database\ParameterType::INTEGER)
        );

        if ((int) $db->loadResult() === 0) {
            State::set('digest_last', (string) $run);
            State::set('digest_run', '0');

            return [$sent, 'done'];
        }

        return [$sent, 'partial'];
    }
}
