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

class SubscribersModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] = ['id', 'a.id', 'email', 'a.email'];

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.id', $direction = 'DESC')
    {
        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search') . ':' . $this->getState('filter.confirmed');

        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)->select('a.*')->from($db->quoteName('#__websourcepreorder_subscribers', 'a'));
        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where($db->quoteName('a.email') . ' LIKE :s')->bind(':s', $like);
        }

        $c = $this->getState('filter.confirmed');

        if ($c !== '' && $c !== null) {
            $c = (int) $c;
            $query->where($db->quoteName('a.confirmed') . ' = :c')->bind(':c', $c, ParameterType::INTEGER);
        }

        $query->order($db->escape($this->getState('list.ordering', 'a.id')) . ' ' . $db->escape($this->getState('list.direction', 'DESC')));

        return $query;
    }
}
