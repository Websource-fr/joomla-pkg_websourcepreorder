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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;

class SubscriberModel extends AdminModel
{
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_websourcepreorder.subscriber', 'subscriber', ['control' => 'jform', 'load_data' => $loadData]);

        return empty($form) ? false : $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_websourcepreorder.edit.subscriber.data', []);

        return empty($data) ? $this->getItem() : $data;
    }

    public function validate($form, $data, $group = null)
    {
        $valid = parent::validate($form, $data, $group);

        if ($valid === false) {
            return false;
        }

        $db = $this->getDatabase();
        $id = (int) ($valid['id'] ?? 0);
        $e  = trim(mb_strtolower((string) $valid['email']));
        $q  = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__websourcepreorder_subscribers'))
            ->where($db->quoteName('email') . ' = :e')->where($db->quoteName('id') . ' <> :i')
            ->bind(':e', $e)->bind(':i', $id, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($q);

        if ((int) $db->loadResult() > 0) {
            $this->setError(Text::_('COM_WEBSOURCEPREORDER_ERR_SUB_EXISTS'));

            return false;
        }

        return $valid;
    }
}
