<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Chargement unique du CSS / JS public et des variables de couleurs.
 */
final class Assets
{
    /** @var bool */
    private static $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $app = Factory::getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $doc = $app->getDocument();

        if (!$doc || $doc->getType() !== 'html') {
            return;
        }

        self::$loaded = true;
        Env::loadLanguage();
        $wa = $doc->getWebAssetManager();
        // Enregistre explicitement le registre d'assets du composant (modules, plugins et pages d'autres composants).
        $wa->getRegistry()->addExtensionRegistryFile('com_websourcepreorder');
        $wa->useStyle('com_websourcepreorder.front')->useScript('com_websourcepreorder.front');

        $vars = [];

        foreach (Settings::cssVars() as $name => $value) {
            $vars[] = $name . ':' . $value;
        }

        if ($vars) {
            $wa->addInlineStyle(':root{' . implode(';', $vars) . '}');
        }

        $doc->addScriptOptions('com_websourcepreorder', [
            'now'   => time(),
            'btn'   => self::buttonLabel(),
            'i18n'  => [
                'd' => Text::_('COM_WEBSOURCEPREORDER_UNIT_DAYS'), 'h' => Text::_('COM_WEBSOURCEPREORDER_UNIT_HOURS'),
                'm' => Text::_('COM_WEBSOURCEPREORDER_UNIT_MINUTES'), 's' => Text::_('COM_WEBSOURCEPREORDER_UNIT_SECONDS'),
                'd1' => Text::_('COM_WEBSOURCEPREORDER_UNIT_DAY'), 'h1' => Text::_('COM_WEBSOURCEPREORDER_UNIT_HOUR'),
                'm1' => Text::_('COM_WEBSOURCEPREORDER_UNIT_MINUTE'), 's1' => Text::_('COM_WEBSOURCEPREORDER_UNIT_SECOND'),
                'done' => Text::_('COM_WEBSOURCEPREORDER_AVAILABLE_NOW'),
                'sending' => Text::_('COM_WEBSOURCEPREORDER_SUB_SENDING'),
                'error' => Text::_('COM_WEBSOURCEPREORDER_SUB_ERROR'),
            ],
            'map'   => self::productMap(),
            'flag'  => Settings::flag('show_flag') ? self::badgeLabel() : '',
        ]);
    }

    public static function buttonLabel(): string
    {
        Env::loadLanguage();
        $custom = Settings::text('btn_label');

        return $custom !== '' ? $custom : Text::_('COM_WEBSOURCEPREORDER_BTN_LABEL');
    }

    public static function badgeLabel(): string
    {
        Env::loadLanguage();
        $custom = Settings::text('badge_label');

        return $custom !== '' ? $custom : Text::_('COM_WEBSOURCEPREORDER_BADGE_LABEL');
    }

    /** Correspondance produit => libellé du bouton (règles en cours), utilisée par le JS pour rebaptiser les boutons et ajouter les badges. */
    private static function productMap(): array
    {
        $map = [];

        foreach (RuleService::running() as $r) {
            $ids = array_merge([(int) $r->product_id], HikashopBridge::variantIds((int) $r->product_id));
            $label = trim(RuleService::langData($r)['label']);

            foreach ($ids as $id) {
                $map[(string) $id] = $label !== '' ? $label : '';
            }
        }

        return $map;
    }
}
