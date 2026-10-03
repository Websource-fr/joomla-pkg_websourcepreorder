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

class BookingsModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] = ['id', 'a.id', 'release_date', 'a.release_date'];

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.id', $direction = 'DESC')
    {
        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search') . ':' . $this->getState('filter.status') . ':' . $this->getState('filter.rule_id');

        return parent::getStoreId($id);
    }

    /** Requête filtrée (utilisée aussi par l'export CSV). */
    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('a.*, o.order_number AS order_number, o.order_status AS order_status')
            ->from($db->quoteName('#__websourcepreorder_bookings', 'a'))
            ->join('LEFT', $db->quoteName('#__hikashop_order', 'o') . ' ON o.order_id = a.order_id');

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where('(a.email LIKE :s1 OR a.customer_name LIKE :s2 OR a.product_name LIKE :s3 OR o.order_number LIKE :s4)')
                ->bind(':s1', $like)->bind(':s2', $like)->bind(':s3', $like)->bind(':s4', $like);
        }

        $status = $this->getState('filter.status');

        if ($status !== '' && $status !== null) {
            $status = (int) $status;
            $query->where($db->quoteName('a.status') . ' = :st')->bind(':st', $status, ParameterType::INTEGER);
        }

        $rule = (int) $this->getState('filter.rule_id');

        if ($rule > 0) {
            $query->where($db->quoteName('a.rule_id') . ' = :r')->bind(':r', $rule, ParameterType::INTEGER);
        }

        $query->order($db->escape($this->getState('list.ordering', 'a.id')) . ' ' . $db->escape($this->getState('list.direction', 'DESC')));

        return $query;
    }
}
