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
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Websource\Component\Websourcepreorder\Administrator\Service\HikashopBridge;
use Websource\Component\Websourcepreorder\Administrator\Service\RuleService;

class RuleModel extends AdminModel
{
    public $typeAlias = 'com_websourcepreorder.rule';

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_websourcepreorder.rule', 'rule', ['control' => 'jform', 'load_data' => $loadData]);

        return empty($form) ? false : $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_websourcepreorder.edit.rule.data', []);

        if (empty($data)) {
            $data = $this->getItem();

            if (\is_object($data) && \is_string($data->translations ?? null)) {
                $rows = json_decode($data->translations, true);
                $sub  = [];

                foreach ((array) $rows as $i => $r) {
                    $sub['translations' . $i] = $r;
                }

                $data->translations = $sub;
            }

            if (\is_object($data) && empty($data->id)) {
                $data->published      = 1;
                $data->end_on_release = 1;
                $data->in_list        = 1;
                $data->in_ticker      = 1;
            }
        }

        return $data;
    }

    public function validate($form, $data, $group = null)
    {
        $valid = parent::validate($form, $data, $group);

        if ($valid === false) {
            return false;
        }

        $pid = (int) ($valid['product_id'] ?? 0);

        if ($pid <= 0 || !HikashopBridge::product($pid)) {
            $this->setError(Text::_('COM_WEBSOURCEPREORDER_ERR_PRODUCT_REQUIRED'));

            return false;
        }

        // Une seule règle non terminée par produit.
        $db = $this->getDatabase();
        $id = (int) ($valid['id'] ?? 0);
        $q  = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__websourcepreorder_rules'))
            ->where($db->quoteName('product_id') . ' = :p')->where($db->quoteName('state') . ' < 2')->where($db->quoteName('id') . ' <> :i')
            ->bind(':p', $pid, \Joomla\Database\ParameterType::INTEGER)->bind(':i', $id, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($q);

        if ((int) $db->loadResult() > 0) {
            $this->setError(Text::_('COM_WEBSOURCEPREORDER_ERR_DUPLICATE_RULE'));

            return false;
        }

        return $valid;
    }

    public function save($data)
    {
        $ok = parent::save($data);

        if ($ok) {
            $id = (int) $this->getState($this->getName() . '.id');
            RuleService::refresh($id);
        }

        return $ok;
    }

    public function delete(&$pks)
    {
        foreach ((array) $pks as $pk) {
            RuleService::disable((int) $pk);
        }

        return parent::delete($pks);
    }

    public function publish(&$pks, $value = 1)
    {
        $ok = parent::publish($pks, $value);

        foreach ((array) $pks as $pk) {
            RuleService::refresh((int) $pk);
        }

        return $ok;
    }
}
