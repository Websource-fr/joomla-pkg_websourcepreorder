<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class RuleTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__websourcepreorder_rules', 'id', $db);
    }

    public function bind($array, $ignore = '')
    {
        if (\is_array($array)) {
            foreach (['date_start', 'date_end', 'date_release'] as $f) {
                if (isset($array[$f]) && trim((string) $array[$f]) === '') {
                    $array[$f] = null;
                }
            }

            if (isset($array['translations']) && \is_array($array['translations'])) {
                $rows = [];

                foreach ($array['translations'] as $t) {
                    if (\is_array($t) && (trim((string) ($t['label'] ?? '')) !== '' || trim((string) ($t['message'] ?? '')) !== '')) {
                        $rows[] = ['language' => (string) ($t['language'] ?? ''), 'label' => (string) ($t['label'] ?? ''), 'message' => (string) ($t['message'] ?? '')];
                    }
                }

                $array['translations'] = json_encode($rows);
            }
        }

        return parent::bind($array, $ignore);
    }

    public function check()
    {
        if ((int) $this->product_id <= 0) {
            $this->setError(Text::_('COM_WEBSOURCEPREORDER_ERR_PRODUCT_REQUIRED'));

            return false;
        }

        $start   = $this->date_start ? strtotime($this->date_start . ' UTC') : null;
        $release = $this->date_release ? strtotime($this->date_release . ' UTC') : null;
        $end     = $this->date_end ? strtotime($this->date_end . ' UTC') : null;

        if (($start && $release && $release < $start) || ($start && $end && $end < $start)) {
            $this->setError(Text::_('COM_WEBSOURCEPREORDER_ERR_DATES'));

            return false;
        }

        if (!$this->created) {
            $this->created = gmdate('Y-m-d H:i:s');
        }

        $this->modified = gmdate('Y-m-d H:i:s');
        $this->price_value = max(0, (float) $this->price_value);

        if ((int) $this->price_mode === 2) {
            $this->price_value = min(100, $this->price_value);
        }

        foreach (['quota', 'max_per_order', 'max_per_customer'] as $f) {
            $this->$f = max(0, (int) $this->$f);
        }

        return true;
    }

    public function store($updateNulls = true)
    {
        return parent::store(true);
    }
}
