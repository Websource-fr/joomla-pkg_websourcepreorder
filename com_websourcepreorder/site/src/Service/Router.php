<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Site\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;

/** Routeur SEF : pages « Toutes les précommandes » et « Mes précommandes ». */
class Router extends RouterView
{
    public function __construct(SiteApplication $app, AbstractMenu $menu)
    {
        $this->registerView(new RouterViewConfiguration('preorders'));
        $this->registerView(new RouterViewConfiguration('mypreorders'));

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    /**
     * Construit l'URL : quand un élément de menu pointe déjà vers la vue demandée, le paramètre « view » est
     * superflu (URL propre /precommandes plutôt que /precommandes?view=preorders).
     */
    public function build(&$query)
    {
        $segments = parent::build($query);

        if (isset($query['view'], $query['Itemid'])) {
            $item = $this->menu->getItem((int) $query['Itemid']);

            if ($item && ($item->query['option'] ?? '') === 'com_websourcepreorder' && ($item->query['view'] ?? '') === $query['view']) {
                unset($query['view']);
            }
        }

        return $segments;
    }
}
