<?php

/**
 * @package     Websource.Websourcepreorder
 * @subpackage  plg_system_websourcepreorder
 *
 * @copyright   Copyright (C) 2026 Websource. Tous droits réservés.
 * @license     Licence Websource, usage libre non commercial (voir LICENSE)
 */

namespace Websource\Plugin\System\Websourcepreorder\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Websource\Component\Websourcepreorder\Administrator\Service\Assets;
use Websource\Component\Websourcepreorder\Administrator\Service\BookingService;
use Websource\Component\Websourcepreorder\Administrator\Service\DisplayHelper;
use Websource\Component\Websourcepreorder\Administrator\Service\Env;
use Websource\Component\Websourcepreorder\Administrator\Service\HikashopBridge;
use Websource\Component\Websourcepreorder\Administrator\Service\Logger;
use Websource\Component\Websourcepreorder\Administrator\Service\RuleService;
use Websource\Component\Websourcepreorder\Administrator\Service\Settings;
use Websource\Component\Websourcepreorder\Administrator\Service\State;

/**
 * Plugin système Websource Précommandes : prix, stock, panier, commande, blocs d'affichage, comptes et hooks HikaShop.
 */
final class Websourcepreorder extends CMSPlugin implements SubscriberInterface
{
    /** @var bool */
    private $cartNoticeDone = false;

    /** @var bool */
    private $productBlockDone = false;

    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterRoute'                => 'onAfterRoute',
            'onAfterDispatch'             => 'onAfterDispatch',
            'onBeforeCompileHead'         => 'onBeforeCompileHead',
            // Événements HikaShop.
            'onAfterLoadProductPrice'     => 'onAfterLoadProductPrice',
            'onAfterProductQuantityCheck' => 'onAfterProductQuantityCheck',
            'onAfterOrderCreate'          => 'onAfterOrderCreate',
            'onAfterOrderUpdate'          => 'onAfterOrderUpdate',
            'onHikashopAfterDisplayView'  => 'onHikashopAfterDisplayView',
        ];
    }

    /** Le composant est-il installé et ses tables présentes ? (évite tout plantage pendant une désinstallation) */
    private function ready(): bool
    {
        static $ok = null;

        if ($ok === null) {
            $ok = is_dir(JPATH_ADMINISTRATOR . '/components/com_websourcepreorder/src') && HikashopBridge::isInstalled();
        }

        return $ok;
    }

    private function guard(callable $fn)
    {
        if (!$this->ready()) {
            return null;
        }

        try {
            return $fn();
        } catch (\Throwable $e) {
            Logger::add($e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine(), \Joomla\CMS\Log\Log::ERROR);

            return null;
        }
    }

    /** Synchronisation « paresseuse » (au plus une fois par minute) quand aucun cron n'est configuré. */
    public function onAfterRoute(Event $event): void
    {
        $this->guard(function () {
            $app = Factory::getApplication();

            if (!$app->isClient('site') || !Settings::flag('lazy_sync')) {
                return;
            }

            if (time() - (int) State::get('last_sync', '0') > 60) {
                State::set('last_sync', (string) time());
                RuleService::syncAll();
            }
        });
    }

    /** Blocs « prochains produits en précommande » sur les pages de connexion, d'inscription et de mot de passe oublié. */
    public function onAfterDispatch(Event $event): void
    {
        $this->guard(function () {
            $app = Factory::getApplication();

            if (!$app->isClient('site')) {
                return;
            }

            $doc = $app->getDocument();

            if (!$doc || $doc->getType() !== 'html') {
                return;
            }

            $input = $app->getInput();

            if ($input->getCmd('option') !== 'com_users') {
                return;
            }

            $view = $input->getCmd('view', '');
            $map  = ['login' => 'blk_login', 'registration' => 'blk_register', 'reset' => 'blk_reset', 'remind' => 'blk_reset'];

            // Vue par défaut de com_users selon l'élément de menu.
            if ($view === '' && ($menu = $app->getMenu()->getActive())) {
                $view = (string) ($menu->query['view'] ?? '');
            }

            if (!isset($map[$view]) || !Settings::flag($map[$view])) {
                return;
            }

            $wantProducts = Settings::flag('blk_products');
            $wantForm     = Settings::flag('blk_form');

            if (!$wantProducts && !$wantForm) {
                return;
            }

            $items = $wantProducts ? DisplayHelper::items([], max(1, Settings::int('blk_max'))) : [];

            if (!$items && !$wantForm) {
                return;
            }

            Env::loadLanguage();
            $html = DisplayHelper::layout('account_block', [
                'title' => Settings::text('blk_title') ?: Text::_('COM_WEBSOURCEPREORDER_BLK_TITLE'),
                'items' => $items,
                'form'  => $wantForm,
                'place' => 'account-' . $view,
            ]);
            $doc->setBuffer($doc->getBuffer('component') . $html, 'component');
        });
    }

    public function onBeforeCompileHead(Event $event): void
    {
        $this->guard(function () {
            $app = Factory::getApplication();

            if (!$app->isClient('site') || $app->getDocument()->getType() !== 'html') {
                return;
            }

            // Charge CSS/JS sur les pages HikaShop dès qu'une précommande existe (badges, bouton, compte à rebours).
            if ($app->getInput()->getCmd('option') === 'com_hikashop' && RuleService::running()) {
                Assets::load();
            }
        });
    }

    /* ------------------------------------------------------------------ HikaShop : prix */

    /** onAfterLoadProductPrice(&$prices, &$rows, $filters, $options) */
    public function onAfterLoadProductPrice(Event $event): void
    {
        $this->guard(function () use ($event) {
            $args   = $event->getArguments();
            $prices = $args[0] ?? null;

            if (\is_array($prices) && $prices) {
                RuleService::applyPrices($prices);
            }
        });
    }

    /* ------------------------------------------------------------------ HikaShop : panier */

    /** onAfterProductQuantityCheck(&$product, &$wantedQuantity, &$quantity, &$cart_products, &$cart_product_id, &$displayErrors) */
    public function onAfterProductQuantityCheck(Event $event): void
    {
        $this->guard(function () use ($event) {
            $args = $event->getArguments();

            if (!isset($args[0]) || !\is_object($args[0]) || Factory::getApplication()->isClient('administrator')) {
                return;
            }

            $wanted = (int) ($args[2] ?? 0);

            if ($wanted <= 0) {
                return;
            }

            Env::loadLanguage();
            [$allowed, $msg] = RuleService::checkLine($args[0], $wanted, (array) ($args[3] ?? []), $args[4] ?? 0);

            if ($allowed < $wanted) {
                $args[2] = $allowed;

                if ($msg) {
                    Factory::getApplication()->enqueueMessage($msg, 'warning');
                }
            }
        });
    }

    /* ------------------------------------------------------------------ HikaShop : commandes */

    /** onAfterOrderCreate(&$order, &$send_email) */
    public function onAfterOrderCreate(Event $event): void
    {
        $this->guard(function () use ($event) {
            $args  = $event->getArguments();
            $order = $args[0] ?? null;

            if (\is_object($order) && !empty($order->order_id)) {
                BookingService::onOrderCreated((int) $order->order_id);
            }
        });
    }

    /** onAfterOrderUpdate(&$order, &$send_email) */
    public function onAfterOrderUpdate(Event $event): void
    {
        $this->guard(function () use ($event) {
            $args  = $event->getArguments();
            $order = $args[0] ?? null;

            if (!\is_object($order) || empty($order->order_id) || empty($order->order_status)) {
                return;
            }

            BookingService::onOrderStatus((int) $order->order_id, (string) $order->order_status);
        });
    }

    /* ------------------------------------------------------------------ HikaShop : affichage */

    /** Bloc précommande sous la fiche produit (déplacé par le JS près du bouton d'achat) et notice panier. */
    public function onHikashopAfterDisplayView(Event $event): void
    {
        $this->guard(function () use ($event) {
            $args = $event->getArguments();
            $view = $args[0] ?? null;

            if (!\is_object($view) || !Factory::getApplication()->isClient('site') || !RuleService::running()) {
                return;
            }

            $name   = method_exists($view, 'getName') ? (string) $view->getName() : '';
            $layout = method_exists($view, 'getLayout') ? (string) $view->getLayout() : '';

            // Le gabarit « show » charge des sous-gabarits (show_microdata…) : on se base sur la présence de l'élément produit.
            if ($name === 'product' && strpos($layout, 'show') === 0 && !empty($view->element->product_id) && !$this->productBlockDone) {
                $this->productBlockDone = true;
                $this->productBlock($view);
            } elseif (\in_array($name, ['cart', 'checkout'], true) && !$this->cartNoticeDone && Settings::flag('show_cart_notice')) {
                $this->cartNoticeDone = true;
                $this->cartNotice($view);
            }
        });
    }

    private function productBlock(object $view): void
    {
        $pid = (int) ($view->element->product_id ?? 0);

        if (!$pid) {
            return;
        }

        $rule = RuleService::findRule($pid);

        // Règle portée par une variante du produit affiché.
        if (!$rule) {
            foreach (HikashopBridge::variantIds($pid) as $vid) {
                if ($rule = RuleService::findRule($vid)) {
                    break;
                }
            }
        }

        if (!$rule) {
            return;
        }

        Env::loadLanguage();
        echo '<div class="wspo-product-slot" data-wspo-slot>' . DisplayHelper::layout('box', ['box' => DisplayHelper::box($rule)]);

        if (Settings::flag('sub_product')) {
            echo DisplayHelper::layout('subscribe', ['place' => 'product']);
        }

        echo '</div>';
    }

    private function cartNotice(object $view): void
    {
        $lines = [];
        $cart  = $view->cart ?? null;

        // La vue « checkout » ne porte pas toujours les produits : on interroge alors le panier courant.
        if ((!\is_object($cart) || empty($cart->products)) && HikashopBridge::load()) {
            try {
                $cart = hikashop_get('class.cart')->getFullCart();
            } catch (\Throwable $e) {
                $cart = null;
            }
        }

        if (!\is_object($cart) || empty($cart->products)) {
            return;
        }

        foreach ((array) $cart->products as $p) {
            $rule = RuleService::findRule((int) ($p->product_id ?? 0));

            if ($rule) {
                $lines[(int) $p->product_id] = ['name' => (string) ($p->product_name ?? HikashopBridge::productName((int) $p->product_id)), 'release_date' => Env::formatDate($rule->date_release)];
            }
        }

        if ($lines) {
            Env::loadLanguage();
            echo DisplayHelper::layout('cart_notice', ['lines' => array_values($lines)]);
        }
    }
}
