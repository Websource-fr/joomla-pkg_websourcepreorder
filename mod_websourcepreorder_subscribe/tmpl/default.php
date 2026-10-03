<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  mod_websourcepreorder_subscribe
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

\defined('_JEXEC') or die;

use Websource\Component\Websourcepreorder\Administrator\Service\DisplayHelper;

/** @var array $displayData */
if (empty($show)) {
    return;
}

echo DisplayHelper::layout('subscribe', ['place' => $place, 'title' => $title, 'text' => $text]);
