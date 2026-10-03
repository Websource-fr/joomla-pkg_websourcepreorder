<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\UserHelper;
use Joomla\Registry\Registry;
use Websource\Component\Websourcepreorder\Administrator\Service\Env;
use Websource\Component\Websourcepreorder\Administrator\Service\MailService;
use Websource\Component\Websourcepreorder\Administrator\Service\Settings;

class SettingsModel extends FormModel
{
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_websourcepreorder.settings', 'settings', ['control' => 'jform', 'load_data' => $loadData]);

        return empty($form) ? false : $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_websourcepreorder.edit.settings.data', []);

        if (empty($data)) {
            $params = ComponentHelper::getParams('com_websourcepreorder')->toArray();
            $data   = array_merge(Settings::defaults(), $params);
        }

        return $data;
    }

    /** Jeton de cron : créé s'il n'existe pas. */
    public static function ensureToken(bool $regenerate = false): string
    {
        $params = ComponentHelper::getParams('com_websourcepreorder');
        $token  = (string) $params->get('cron_token', '');

        if ($token === '' || $regenerate) {
            $token = UserHelper::genRandomPassword(40);
            self::saveParams(['cron_token' => $token]);
        }

        return $token;
    }

    /** Fusionne des paramètres dans com_websourcepreorder. */
    public static function saveParams(array $values): bool
    {
        $table = Table::getInstance('extension');

        if (!$table->load(['element' => 'com_websourcepreorder', 'type' => 'component'])) {
            return false;
        }

        $reg = new Registry($table->params);

        foreach ($values as $k => $v) {
            $reg->set($k, $v);
        }

        $table->params = $reg->toString();
        $ok = $table->store();
        // Rafraîchit le cache des paramètres du composant.
        try {
            Factory::getContainer()->get(\Joomla\CMS\Cache\CacheControllerFactoryInterface::class)
                ->createCacheController('callback', ['defaultgroup' => '_system'])->clean();
        } catch (\Throwable $e) {
            // sans conséquence : le cache expire de lui-même
        }

        ComponentHelper::getComponent('com_websourcepreorder')->setParams($reg);
        Settings::reset();

        return (bool) $ok;
    }

    public function save($data)
    {
        $data['cron_token'] = self::ensureToken();

        return self::saveParams($data);
    }

    /** @return array<string,array<string,array{subject:string,body:string}>> clé => langue => gabarit */
    public function getMails(): array
    {
        $out = [];

        foreach (MailService::KEYS as $key) {
            foreach (array_keys(Env::languages()) as $tag) {
                $out[$key][$tag] = MailService::template($key, $tag);
            }
        }

        return $out;
    }

    /** Enregistre les e-mails depuis le tableau mail[clé][langue][subject|body]. */
    public function saveMails(array $mail): bool
    {
        $db   = $this->getDatabase();
        $tags = array_keys(Env::languages());

        foreach (MailService::KEYS as $key) {
            foreach ($tags as $tag) {
                if (!isset($mail[$key][$tag])) {
                    continue;
                }

                $subject = trim((string) $mail[$key][$tag]['subject']);
                $body    = (string) $mail[$key][$tag]['body'];
                $body    = ComponentHelper::filterText($body);
                $def     = MailService::defaults($key, $tag);
                $db->setQuery($db->getQuery(true)->delete($db->quoteName('#__websourcepreorder_mails'))
                    ->where($db->quoteName('mail_key') . ' = :k')->where($db->quoteName('lang') . ' = :l')->bind(':k', $key)->bind(':l', $tag))->execute();

                // Un gabarit identique au défaut n'est pas stocké : il suivra les futures mises à jour.
                if ($subject === '' && trim($body) === '' || ($subject === $def['subject'] && trim($body) === $def['body'])) {
                    continue;
                }

                $row = (object) ['mail_key' => $key, 'lang' => $tag, 'subject' => $subject, 'body' => $body];
                $db->insertObject('#__websourcepreorder_mails', $row);
            }
        }

        return true;
    }
}
