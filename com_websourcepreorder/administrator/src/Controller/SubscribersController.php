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

class SubscribersController extends AdminController
{
    protected $text_prefix = 'COM_WEBSOURCEPREORDER_SUBSCRIBERS';

    public function getModel($name = 'Subscriber', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /** Confirme manuellement des abonnés. */
    public function confirm()
    {
        $this->checkToken();
        $ids = array_filter(array_map('intval', (array) $this->input->get('cid', [], 'array')));

        if ($ids && $this->app->getIdentity()->authorise('core.edit', 'com_websourcepreorder')) {
            $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $db->setQuery(
                $db->getQuery(true)->update($db->quoteName('#__websourcepreorder_subscribers'))
                    ->set($db->quoteName('confirmed') . ' = 1')
                    ->set($db->quoteName('confirmed_at') . ' = COALESCE(' . $db->quoteName('confirmed_at') . ', UTC_TIMESTAMP())')
                    ->whereIn($db->quoteName('id'), $ids)
            )->execute();
        }

        $this->setRedirect('index.php?option=com_websourcepreorder&view=subscribers', Text::_('COM_WEBSOURCEPREORDER_SUBSCRIBERS_CONFIRMED'));
    }

    public function export()
    {
        if (!$this->app->getIdentity()->authorise('core.manage', 'com_websourcepreorder')) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $model = $this->getModel('Subscribers', 'Administrator', ['ignore_request' => false]);
        $model->setState('list.limit', 0);
        $model->setState('list.start', 0);
        $this->app->setHeader('Content-Type', 'text/csv; charset=utf-8', true);
        $this->app->setHeader('Content-Disposition', 'attachment; filename="precommandes-abonnes-' . gmdate('Ymd-His') . '.csv"', true);
        $this->app->sendHeaders();
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID', 'E-mail', 'Langue', 'Confirme', 'Source', 'IP', 'Inscrit (UTC)', 'Confirme le (UTC)'], ';', '"', '\\');

        foreach ($model->getItems() as $i) {
            fputcsv($out, [$i->id, $i->email, $i->lang, $i->confirmed ? 'oui' : 'non', $i->source, $i->ip, $i->created, $i->confirmed_at], ';', '"', '\\');
        }

        fclose($out);
        $this->app->close();
    }
}
