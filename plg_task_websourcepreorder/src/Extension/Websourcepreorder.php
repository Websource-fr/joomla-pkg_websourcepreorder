<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  plg_task_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Plugin\Task\Websourcepreorder\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;
use Websource\Component\Websourcepreorder\Administrator\Service\DigestService;
use Websource\Component\Websourcepreorder\Administrator\Service\RuleService;

/**
 * Tâches planifiées : synchronisation des précommandes et récapitulatif périodique.
 */
final class Websourcepreorder extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    protected const TASKS_MAP = [
        'websourcepreorder.sync' => [
            'langConstPrefix' => 'PLG_TASK_WEBSOURCEPREORDER_SYNC',
            'method'          => 'runSync',
        ],
        'websourcepreorder.digest' => [
            'langConstPrefix' => 'PLG_TASK_WEBSOURCEPREORDER_DIGEST',
            'method'          => 'runDigest',
        ],
    ];

    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList'    => 'advertiseRoutines',
            'onExecuteTask'        => 'standardRoutineHandler',
            'onContentPrepareForm' => 'enhanceTaskItemForm',
        ];
    }

    private function available(): bool
    {
        return is_dir(JPATH_ADMINISTRATOR . '/components/com_websourcepreorder/src');
    }

    /** Applique les ouvertures / clôtures de précommandes et envoie les e-mails « disponible ». */
    protected function runSync(ExecuteTaskEvent $event): int
    {
        if (!$this->available()) {
            return TaskStatus::NO_RUN;
        }

        try {
            RuleService::syncAll();
        } catch (\Throwable $e) {
            return TaskStatus::KNOCKOUT;
        }

        return TaskStatus::OK;
    }

    /**
     * Envoie le récapitulatif si l'échéance (réglée dans le composant) est atteinte ; les envois sont
     * faits par lots et repris à l'exécution suivante. Planifiez cette tâche toutes les 15 minutes à 1 heure.
     */
    protected function runDigest(ExecuteTaskEvent $event): int
    {
        if (!$this->available()) {
            return TaskStatus::NO_RUN;
        }

        try {
            RuleService::syncAll();
            DigestService::run(false);
        } catch (\Throwable $e) {
            return TaskStatus::KNOCKOUT;
        }

        return TaskStatus::OK;
    }
}
