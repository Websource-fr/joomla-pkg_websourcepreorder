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
use Websource\Component\Websourcepreorder\Administrator\Service\BookingService;
use Websource\Component\Websourcepreorder\Administrator\Service\Env;
use Websource\Component\Websourcepreorder\Administrator\Service\RuleService;

class BookingsController extends BaseController
{
    private function guard(): bool
    {
        $this->checkToken();

        if (!$this->app->getIdentity()->authorise('core.edit', 'com_websourcepreorder')) {
            $this->setRedirect('index.php?option=com_websourcepreorder&view=bookings', Text::_('JERROR_ALERTNOAUTHOR'), 'error');

            return false;
        }

        return true;
    }

    private function ids(): array
    {
        return array_filter(array_map('intval', (array) $this->input->get('cid', [], 'array')));
    }

    public function cancel()
    {
        if ($this->guard()) {
            BookingService::setStatus($this->ids(), BookingService::CANCELLED);
            RuleService::resetCache();
            $this->setRedirect('index.php?option=com_websourcepreorder&view=bookings', Text::_('COM_WEBSOURCEPREORDER_BOOKINGS_CANCELLED'));
        }
    }

    public function restore()
    {
        if ($this->guard()) {
            BookingService::setStatus($this->ids(), BookingService::RESERVED);
            $this->setRedirect('index.php?option=com_websourcepreorder&view=bookings', Text::_('COM_WEBSOURCEPREORDER_BOOKINGS_RESTORED'));
        }
    }

    public function resend()
    {
        if ($this->guard()) {
            $ok = 0;

            foreach ($this->ids() as $id) {
                if (BookingService::notifyBooking($id)) {
                    ++$ok;
                }
            }

            $this->setRedirect('index.php?option=com_websourcepreorder&view=bookings', Text::sprintf('COM_WEBSOURCEPREORDER_N_MAILS_SENT', $ok));
        }
    }

    /** Export CSV des réservations selon les filtres courants. */
    public function export()
    {
        if (!$this->app->getIdentity()->authorise('core.manage', 'com_websourcepreorder')) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $model = $this->getModel('Bookings', 'Administrator', ['ignore_request' => false]);
        $model->setState('list.limit', 0);
        $model->setState('list.start', 0);
        $items = $model->getItems();

        $this->app->setHeader('Content-Type', 'text/csv; charset=utf-8', true);
        $this->app->setHeader('Content-Disposition', 'attachment; filename="precommandes-reservations-' . gmdate('Ymd-His') . '.csv"', true);
        $this->app->sendHeaders();
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID', 'Regle', 'Commande', 'Statut commande', 'Client', 'E-mail', 'Produit', 'Quantite', 'Prix unitaire TTC', 'Sortie (UTC)', 'Statut', 'Cree (UTC)', 'Prevenu (UTC)'], ';', '"', '\\');

        foreach ($items as $i) {
            fputcsv($out, [$i->id, $i->rule_id, $i->order_number, $i->order_status, $i->customer_name, $i->email, $i->product_name, $i->quantity, $i->unit_price, $i->release_date, Text::_('COM_WEBSOURCEPREORDER_BSTATUS_' . (int) $i->status), $i->created, $i->notified], ';', '"', '\\');
        }

        fclose($out);
        $this->app->close();
    }
}
