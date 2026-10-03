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
use Joomla\CMS\MVC\Controller\BaseController;
use Websource\Component\Websourcepreorder\Administrator\Service\DigestService;
use Websource\Component\Websourcepreorder\Administrator\Service\RuleService;

class ToolsController extends BaseController
{
    private function guard(): bool
    {
        $this->checkToken();

        if (!$this->app->getIdentity()->authorise('core.edit', 'com_websourcepreorder')) {
            $this->setRedirect('index.php?option=com_websourcepreorder', Text::_('JERROR_ALERTNOAUTHOR'), 'error');

            return false;
        }

        return true;
    }

    public function sync()
    {
        if ($this->guard()) {
            $n = RuleService::syncAll();
            $this->setRedirect('index.php?option=com_websourcepreorder', Text::sprintf('COM_WEBSOURCEPREORDER_N_SYNCED', $n));
        }
    }

    public function digest()
    {
        if ($this->guard()) {
            [$sent, $status] = DigestService::run(true);
            $this->setRedirect('index.php?option=com_websourcepreorder', Text::sprintf('COM_WEBSOURCEPREORDER_DIGEST_RESULT', $sent, Text::_('COM_WEBSOURCEPREORDER_DIGEST_STATUS_' . strtoupper($status))));
        }
    }
}
