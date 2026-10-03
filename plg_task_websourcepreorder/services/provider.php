<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  plg_task_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Websource\Plugin\Task\Websourcepreorder\Extension\Websourcepreorder;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $dispatcher = $container->get(DispatcherInterface::class);
                $config     = (array) PluginHelper::getPlugin('task', 'websourcepreorder');

                // Joomla 4.x attend le dispatcher dans le constructeur ; 5.x et suivants utilisent setDispatcher().
                $first  = (new \ReflectionMethod(CMSPlugin::class, '__construct'))->getParameters()[0]->getName();
                $plugin = $first === 'subject' ? new Websourcepreorder($dispatcher, $config) : new Websourcepreorder($config);

                if (method_exists($plugin, 'setDispatcher')) {
                    $plugin->setDispatcher($dispatcher);
                }

                return $plugin;
            }
        );
    }
};
