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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Websource\Component\Websourcepreorder\Administrator\Service\RuleService;
use Websource\Component\Websourcepreorder\Administrator\Service\State;

class DashboardModel extends BaseDatabaseModel
{
    private function count(string $table, string $where = ''): int
    {
        $db = $this->getDatabase();
        $q  = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName($table));

        if ($where !== '') {
            $q->where($where);
        }

        return (int) $db->setQuery($q)->loadResult();
    }

    public function getStats(): array
    {
        $db = $this->getDatabase();
        $db->setQuery("SELECT COALESCE(SUM(quantity),0) FROM " . $db->quoteName('#__websourcepreorder_bookings') . " WHERE status <> 2");

        return [
            'rules_running'   => $this->count('#__websourcepreorder_rules', 'state = 1'),
            'rules_pending'   => $this->count('#__websourcepreorder_rules', 'state = 0'),
            'rules_ended'     => $this->count('#__websourcepreorder_rules', 'state = 2'),
            'bookings'        => $this->count('#__websourcepreorder_bookings', 'status <> 2'),
            'units'           => (int) $db->loadResult(),
            'subs_confirmed'  => $this->count('#__websourcepreorder_subscribers', 'confirmed = 1'),
            'subs_pending'    => $this->count('#__websourcepreorder_subscribers', 'confirmed = 0'),
            'last_sync'       => (int) State::get('last_sync', '0'),
            'digest_last'     => (int) State::get('digest_last', '0'),
            'digest_run'      => (int) State::get('digest_run', '0'),
            'next'            => array_slice(array_values(RuleService::running()), 0, 5),
        ];
    }
}
