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

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Websource\Component\Websourcepreorder\Administrator\Service\Env;
use Websource\Component\Websourcepreorder\Administrator\Service\RuleService;

class RulesModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] = ['id', 'a.id', 'date_release', 'a.date_release', 'state', 'a.state', 'product_name'];

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.date_release', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search') . ':' . $this->getState('filter.state');

        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('a.*, p.product_name AS product_name, p.product_parent_id AS product_parent_id')
            ->select('(SELECT COALESCE(SUM(b.quantity),0) FROM ' . $db->quoteName('#__websourcepreorder_bookings') . ' b WHERE b.rule_id = a.id AND b.status <> 2) AS booked')
            ->from($db->quoteName('#__websourcepreorder_rules', 'a'))
            ->join('LEFT', $db->quoteName('#__hikashop_product', 'p') . ' ON p.product_id = a.product_id');

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $id = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :sid')->bind(':sid', $id, ParameterType::INTEGER);
            } else {
                $like = '%' . $search . '%';
                $query->where('(p.product_name LIKE :s1 OR p.product_code LIKE :s2)')->bind(':s1', $like)->bind(':s2', $like);
            }
        }

        $state = $this->getState('filter.state');

        if ($state !== '' && $state !== null) {
            $state = (int) $state;
            $query->where($db->quoteName('a.state') . ' = :st')->bind(':st', $state, ParameterType::INTEGER);
        }

        $query->order($db->escape($this->getState('list.ordering', 'a.date_release')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

        return $query;
    }

    public function getItems()
    {
        $items = parent::getItems();

        foreach ((array) $items as $item) {
            $item->effective_state = RuleService::desiredState($item);
            $item->product_label   = $item->product_name !== null
                ? \Websource\Component\Websourcepreorder\Administrator\Service\HikashopBridge::productName((int) $item->product_id)
                : '#' . $item->product_id;
        }

        return $items;
    }
}
