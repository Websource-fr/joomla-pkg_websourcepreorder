<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  com_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Component\Websourcepreorder\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Websource\Component\Websourcepreorder\Administrator\Service\HikashopBridge;

/**
 * Sélecteur de produit HikaShop avec recherche instantanée (produit ou variante).
 */
class ProductField extends FormField
{
    protected $type = 'Product';

    protected function getInput()
    {
        $id    = (int) $this->value;
        $name  = $id ? HikashopBridge::productName($id) : '';
        $url   = 'index.php?option=com_websourcepreorder&task=rule.searchProducts&format=json&' . Session::getFormToken() . '=1';
        $doc   = Factory::getApplication()->getDocument();
        $doc->getWebAssetManager()->useScript('com_websourcepreorder.admin')->useStyle('com_websourcepreorder.admin');
        $doc->addScriptOptions('com_websourcepreorder.admin', ['searchUrl' => $url]);

        return '<div class="wspo-pp" data-url="' . htmlspecialchars($url, ENT_QUOTES) . '">'
            . '<input type="text" class="form-control wspo-pp__q" autocomplete="off" placeholder="' . htmlspecialchars(Text::_('COM_WEBSOURCEPREORDER_PRODUCT_SEARCH'), ENT_QUOTES) . '" value="' . htmlspecialchars($id ? '#' . $id . ' ' . $name : '', ENT_QUOTES) . '">'
            . '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '" value="' . $id . '">'
            . '<div class="wspo-pp__results list-group" hidden></div></div>';
    }
}
