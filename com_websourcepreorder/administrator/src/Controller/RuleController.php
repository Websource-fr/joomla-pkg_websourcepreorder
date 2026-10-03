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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Session\Session;
use Websource\Component\Websourcepreorder\Administrator\Service\HikashopBridge;

class RuleController extends FormController
{
    protected $view_list = 'rules';

    /** Recherche de produits (JSON) pour le sélecteur. */
    public function searchProducts()
    {
        if (!Session::checkToken('get') || !$this->app->getIdentity()->authorise('core.manage', 'com_websourcepreorder')) {
            echo json_encode([]);
            $this->app->close();
        }

        $q = trim((string) $this->input->getString('q', ''));
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $this->app->sendHeaders();
        echo json_encode($q === '' ? [] : HikashopBridge::search($q));
        $this->app->close();
    }
}
