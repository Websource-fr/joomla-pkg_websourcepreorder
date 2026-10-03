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

use Joomla\CMS\Log\Log;

/** Journal dédié : logs/websourcepreorder.php (dossier de logs de Joomla). */
final class Logger
{
    private static $ready = false;

    public static function add(string $message, int $level = Log::WARNING): void
    {
        try {
            if (!self::$ready) {
                self::$ready = true;
                Log::addLogger(['text_file' => 'websourcepreorder.php'], Log::ALL, ['websourcepreorder']);
            }

            Log::add($message, $level, 'websourcepreorder');
        } catch (\Throwable $e) {
            // la journalisation ne doit jamais casser une page
        }
    }
}
