<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Websource\Component\Websourcepreorder\Administrator\Service\RuleService;

class RulesController extends AdminController
{
    protected $text_prefix = 'COM_WEBSOURCEPREORDER_RULES';

    public function getModel($name = 'Rule', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /** « Disponible maintenant » : termine la règle et prévient les acheteurs. */
    public function release()
    {
        $this->checkToken();

        if (!$this->app->getIdentity()->authorise('core.edit.state', 'com_websourcepreorder')) {
            $this->setRedirect('index.php?option=com_websourcepreorder&view=rules', Text::_('JERROR_CORE_EDIT_STATE_NOT_PERMITTED'), 'error');

            return false;
        }

        $ids = (array) $this->input->get('cid', [], 'array');
        $n   = 0;

        foreach (array_filter(array_map('intval', $ids)) as $id) {
            RuleService::releaseNow($id);
            ++$n;
        }

        $this->setRedirect('index.php?option=com_websourcepreorder&view=rules', Text::sprintf('COM_WEBSOURCEPREORDER_N_RELEASED', $n));

        return true;
    }
}
