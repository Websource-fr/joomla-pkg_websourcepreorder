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
 * E-mails personnalisables (objet + corps HTML par langue, variables {xxx}) dans une enveloppe HTML aux couleurs du site.
 */
final class MailService
{
    public const KEYS = ['order', 'available', 'admin', 'optin', 'digest'];

    /** Variables documentées par type d'e-mail. */
    public static function variables(string $key): array
    {
        $map = [
            'order'     => ['firstname', 'lastname', 'order_reference', 'products_html', 'release_info', 'shop_name', 'shop_url', 'account_url'],
            'available' => ['firstname', 'lastname', 'order_reference', 'product_name', 'product_url', 'release_date', 'shop_name', 'shop_url', 'account_url'],
            'admin'     => ['customer', 'customer_email', 'order_reference', 'products_html', 'shop_name'],
            'optin'     => ['confirm_url', 'shop_name', 'shop_url'],
            'digest'    => ['products_html', 'count', 'unsubscribe_url', 'shop_name', 'shop_url', 'list_url'],
        ];

        return $map[$key] ?? [];
    }

    /** Valeurs HTML déjà sûres (non échappées). */
    private static function rawVars(): array
    {
        return ['products_html'];
    }

    public static function defaults(string $key, string $tag): array
    {
        $fr  = strncasecmp($tag, 'fr', 2) === 0;
        $btn = 'display:inline-block;padding:10px 18px;background:#e8590c;color:#ffffff;text-decoration:none;border-radius:4px';
        $d   = [
            'order' => [
                'fr' => ['Votre précommande {order_reference} est bien enregistrée', '<p>Bonjour {firstname},</p><p>Merci pour votre précommande <strong>{order_reference}</strong> sur {shop_name}.</p>{products_html}<p>{release_info}</p><p>Nous vous préviendrons par e-mail dès que vos articles seront disponibles. Suivez vos précommandes ici : <a href="{account_url}">mes précommandes</a>.</p>'],
                'en' => ['Your pre-order {order_reference} is confirmed', '<p>Hello {firstname},</p><p>Thank you for your pre-order <strong>{order_reference}</strong> at {shop_name}.</p>{products_html}<p>{release_info}</p><p>We will email you as soon as your items are available. Follow your pre-orders here: <a href="{account_url}">my pre-orders</a>.</p>'],
            ],
            'available' => [
                'fr' => ['{product_name} est disponible !', '<p>Bonjour {firstname},</p><p>Bonne nouvelle : <strong>{product_name}</strong> (commande {order_reference}) est maintenant disponible. Votre commande va être préparée.</p><p><a href="{product_url}">Voir le produit</a></p>'],
                'en' => ['{product_name} is now available!', '<p>Hello {firstname},</p><p>Good news: <strong>{product_name}</strong> (order {order_reference}) is now available. Your order is being prepared.</p><p><a href="{product_url}">View the product</a></p>'],
            ],
            'admin' => [
                'fr' => ['Nouvelle précommande {order_reference}', '<p>Nouvelle précommande <strong>{order_reference}</strong> de {customer} ({customer_email}).</p>{products_html}'],
                'en' => ['New pre-order {order_reference}', '<p>New pre-order <strong>{order_reference}</strong> from {customer} ({customer_email}).</p>{products_html}'],
            ],
            'optin' => [
                'fr' => ['Confirmez votre inscription aux alertes précommandes', '<p>Bonjour,</p><p>Pour recevoir les alertes de précommandes de {shop_name}, confirmez votre adresse e-mail :</p><p><a href="{confirm_url}" style="' . $btn . '">Confirmer mon inscription</a></p><p>Si vous n\'êtes pas à l\'origine de cette demande, ignorez simplement ce message.</p>'],
                'en' => ['Confirm your pre-order alerts subscription', '<p>Hello,</p><p>To receive pre-order alerts from {shop_name}, please confirm your email address:</p><p><a href="{confirm_url}" style="' . $btn . '">Confirm my subscription</a></p><p>If you did not request this, just ignore this message.</p>'],
            ],
            'digest' => [
                'fr' => ['{count} précommande(s) à découvrir chez {shop_name}', '<p>Bonjour,</p><p>Voici les précommandes actuellement ouvertes :</p>{products_html}<p><a href="{list_url}">Voir toutes les précommandes</a></p><p style="font-size:12px;color:#888">Vous recevez ce message car vous êtes inscrit aux alertes. <a href="{unsubscribe_url}">Se désinscrire</a></p>'],
                'en' => ['{count} pre-order(s) to discover at {shop_name}', '<p>Hello,</p><p>Here are the pre-orders currently open:</p>{products_html}<p><a href="{list_url}">See all pre-orders</a></p><p style="font-size:12px;color:#888">You receive this because you subscribed to alerts. <a href="{unsubscribe_url}">Unsubscribe</a></p>'],
            ],
        ];
        $l = $fr ? 'fr' : 'en';

        return ['subject' => $d[$key][$l][0], 'body' => $d[$key][$l][1]];
    }

    /** Gabarit personnalisé (ou défaut) pour un type et une langue. */
    public static function template(string $key, string $tag): array
    {
        $def = self::defaults($key, $tag);
        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $q   = $db->getQuery(true)
            ->select($db->quoteName(['subject', 'body']))
            ->from($db->quoteName('#__websourcepreorder_mails'))
            ->where($db->quoteName('mail_key') . ' = :k')
            ->where($db->quoteName('lang') . ' = :l')
            ->bind(':k', $key)
            ->bind(':l', $tag);
        $db->setQuery($q);
        $row = $db->loadObject();

        return [
            'subject' => ($row && trim((string) $row->subject) !== '') ? (string) $row->subject : $def['subject'],
            'body'    => ($row && trim((string) $row->body) !== '') ? (string) $row->body : $def['body'],
        ];
    }

    public static function render(string $text, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $val  = \in_array($k, self::rawVars(), true) ? (string) $v : htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
            $text = str_replace('{' . $k . '}', $val, $text);
        }

        return $text;
    }

    /** Enveloppe HTML aux couleurs du site. */
    public static function envelope(string $contentHtml): string
    {
        $accent = (string) Settings::get('c_accent');
        $accent = preg_match('/^#[0-9a-fA-F]{6}$/', $accent) ? $accent : '#e8590c';
        $on     = (string) Settings::get('c_on_accent');
        $on     = preg_match('/^#[0-9a-fA-F]{6}$/', $on) ? $on : '#ffffff';
        $shop   = htmlspecialchars(Env::shopName(), ENT_QUOTES, 'UTF-8');
        $url    = htmlspecialchars(Urls::root(), ENT_QUOTES, 'UTF-8');

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>' . $shop . '</title></head>'
            . '<body style="margin:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#222">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5"><tr><td align="center" style="padding:20px 10px">'
            . '<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:6px;overflow:hidden">'
            . '<tr><td style="background:' . $accent . ';padding:16px 24px;text-align:center"><a href="' . $url . '" style="color:' . $on . ';font-size:20px;font-weight:bold;text-decoration:none">' . $shop . '</a></td></tr>'
            . '<tr><td style="padding:24px;font-size:15px;line-height:1.55">' . $contentHtml . '</td></tr>'
            . '<tr><td style="padding:14px 24px;background:#fafafa;font-size:12px;color:#888;text-align:center">' . $shop . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    /** Envoi d'un e-mail personnalisé. @return bool succès */
    public static function send(string $key, string $to, string $tag, array $vars, ?string $toName = null): bool
    {
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $tag = $tag !== '' ? $tag : Env::langTag();
        $tpl = self::template($key, $tag);
        $vars += ['shop_name' => Env::shopName(), 'shop_url' => Urls::root()];

        $subject  = html_entity_decode(strip_tags(self::render($tpl['subject'], $vars)), ENT_QUOTES, 'UTF-8');
        $bodyHtml = self::render($tpl['body'], $vars);
        $bodyTxt  = trim(preg_replace("/\n{3,}/", "\n\n", html_entity_decode(strip_tags(preg_replace('#<(br|/p|/li|/tr|/h[1-6])\s*/?>#i', "\n", $bodyHtml)), ENT_QUOTES, 'UTF-8')));

        try {
            $mailer = self::mailer();
            $mailer->isHtml(true);
            $mailer->addRecipient($to, (string) $toName);
            $mailer->setSubject($subject);
            $mailer->setBody(self::envelope($bodyHtml));
            $mailer->AltBody = $bodyTxt;

            return $mailer->Send() === true;
        } catch (\Throwable $e) {
            Logger::add('échec e-mail ' . $key . ' vers ' . $to . ' : ' . $e->getMessage());

            return false;
        }
    }

    private static function mailer()
    {
        $iface = '\\Joomla\\CMS\\Mail\\MailerFactoryInterface';

        if (interface_exists($iface) && Factory::getContainer()->has($iface)) {
            return Factory::getContainer()->get($iface)->createMailer(Factory::getApplication()->getConfig());
        }

        return Factory::getMailer();
    }

    /** Exemple de valeurs pour le bouton « envoyer un test ». */
    public static function sampleVars(string $key): array
    {
        return [
            'firstname'       => 'Camille',
            'lastname'        => 'Martin',
            'order_reference' => 'TEST-0001',
            'products_html'   => '<table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;border:1px solid #e5e5e5;font-size:14px"><tr><td>Produit exemple × 1</td><td align="right">Sortie ' . Env::formatDate(Env::sql(time() + 864000)) . '</td></tr></table>',
            'release_info'    => 'Date de sortie prévue : ' . Env::formatDate(Env::sql(time() + 864000)) . '.',
            'product_name'    => 'Produit exemple',
            'product_url'     => Urls::root(),
            'release_date'    => Env::formatDate(Env::sql(time() + 864000)),
            'confirm_url'     => Urls::root(),
            'unsubscribe_url' => Urls::root(),
            'list_url'        => Urls::listPage(),
            'account_url'     => Urls::accountPage(),
            'count'           => '3',
            'customer'        => 'Camille Martin',
            'customer_email'  => 'camille@example.com',
        ];
    }
}
