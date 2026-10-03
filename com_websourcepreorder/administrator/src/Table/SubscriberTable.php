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
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseDriver;

class SubscriberTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__websourcepreorder_subscribers', 'id', $db);
    }

    public function check()
    {
        $this->email = trim(mb_strtolower((string) $this->email));

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->setError(Text::_('COM_WEBSOURCEPREORDER_ERR_EMAIL'));

            return false;
        }

        if (!$this->token) {
            $this->token = UserHelper::genRandomPassword(32);
        }

        if (!$this->created) {
            $this->created = gmdate('Y-m-d H:i:s');
        }

        if ((int) $this->confirmed && !$this->confirmed_at) {
            $this->confirmed_at = gmdate('Y-m-d H:i:s');
        }

        return true;
    }
}
