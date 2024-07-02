<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * International Registered Trademark & Property of Payplug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\classes\DependenciesClass;

class PayplugValidationModuleFrontController extends ModuleFrontController
{
    public $apiClass;
    public $logger;
    public $paymentClass;
    public $debug;
    public $type;
    private $cart_adapter;
    private $customer_adapter;
    private $dependencies;
    private $moduleInstance;
    private $order_adapter;
    private $plugin;
    private $tools_adapter;
    private $payplugLock;
    private $validate_adapter;
    private $lock_id;

    public function postProcess()
    {
        $this->setParameters();
        $this->treat();
    }

    private function setParameters()
    {
        $this->dependencies = new DependenciesClass();
        $this->plugin = $this->dependencies->getPlugin();
        $this->cart_adapter = $this->plugin->getCart();
        $this->customer_adapter = $this->plugin->getCustomer();
        $this->order_adapter = $this->plugin->getOrder();
        $this->tools_adapter = $this->plugin->getTools();
        $this->validate_adapter = $this->plugin->getValidate();

        $this->payplugLock = $this->dependencies->payplugLock;

        $this->setLogger();
        $this->moduleInstance = $this->plugin
            ->getModule()
            ->getInstanceByName($this->dependencies->name);
    }

    private function setLogger()
    {
        $this->logger = $this->plugin->getLogger();
        $this->logger->setProcess('validation');
        $this->logger->addLog('New validation');
    }

    private function treat()
    {
        $redirect_url_error = $this->context->link->getPageLink('order', true, $this->context->language->id, [
            'step' => 3,
            'has_error' => 1,
            'modulename' => $this->dependencies->name,
        ]);
        $cancel_url = $this->context->link->getPageLink('order', true, $this->context->language->id, [
            'step' => 3,
        ]);
        $error_message = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->l('The transaction was not completed and your card was not charged.', 'validation');

        $cart_id = (int) $this->tools_adapter->tool('getValue', 'cartid');
        if (!$cart_id) {
            $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([$error_message]);
            $this->exitProcess($redirect_url_error, 'No Cart ID.');
        }
        $this->logger->addLog('Cart ID : ' . (int) $cart_id);

        $ps = (int) $this->tools_adapter->tool('getValue', 'ps');
        if (!is_int($ps) || !in_array($ps, [1, 2])) {
            $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([$error_message]);
            $this->exitProcess($redirect_url_error, 'Invalid argument ps given');
        }
        if (2 == $ps) {
            $this->exitProcess($cancel_url, 'Order has been cancelled on PayPlug page');
        }

        // Check if valid cart
        $cart = $this->cart_adapter->get((int) $cart_id);
        if (!$this->validate_adapter->validate('isLoadedObject', $cart)) {
            $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([$error_message]);
            $this->exitProcess($redirect_url_error, 'Cart cannot be loaded.');
        }
        $this->logger->addLog('Cart loaded.', 'error');

        // Check if valid cart
        $customer = $this->customer_adapter->get((int) $cart->id_customer);
        if (!$this->validate_adapter->validate('isLoadedObject', $customer)) {
            $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([$error_message]);
            $this->exitProcess($redirect_url_error, 'Customer cannot be loaded.');
        }

        // Create lock
        $cart_lock = false;
        $datetime1 = date_create(date('Y-m-d H:i:s'));
        $this->logger->addLog('Check lock');

        // Before creating the lock, we wait one second to be sure that notification is priority
        sleep(1);

        // then we check/create the lock
        do {
            $cart_lock = $this->payplugLock->check($cart->id);
            if (!$cart_lock) {
                $datetime2 = date_create(date('Y-m-d H:i:s'));
                $interval = date_diff($datetime1, $datetime2);
                $diff = explode('+', $interval->format('%R%s'));
                if ($diff[1] >= 10) {
                    $this->logger->addLog('Try to create lock ($this->payplugLock->createLockG2) during ' . $diff[1] . ' sec,'
                        . ' but can\'t proceed', 'error');

                    break;
                }
                if ($this->payplugLock->createLockG2($cart->id, 'validation')) {
                    $this->logger->addLog('Lock created');
                    $this->lock_id = (int) $cart->id;

                    break;
                }
            }
        } while (!$cart_lock);

        // Check if order already exist
        $id_order = $this->order_adapter->getIdByCartId((int) $cart->id);
        if ($id_order) {
            $order = $this->dependencies
                ->getPlugin()
                ->getOrder()
                ->get((int) $id_order);
            $link_redirect = $this->context->link->getPageLink('order-confirmation', true, $this->context->language->id, [
                'id_cart' => $cart->id,
                'id_module' => $this->moduleInstance->id,
                'id_order' => $id_order,
                'key' => $order->secure_key,
            ]);
            $this->exitProcess($link_redirect, 'Redirecting to order-confirmation page');
        }

        $payment_tab = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getByCart((int) $cart_id);
        $order_create = $this->dependencies
            ->getPlugin()
            ->getOrderAction()
            ->createAction($payment_tab['resource_id']);
        if (!$order_create['result']) {
            $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([
                $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('The transaction was not completed and your card was not charged.', 'validation'),
            ]);
            $this->exitProcess($redirect_url_error, $order_create['message']);
        }

        $order = $this->dependencies
            ->getPlugin()
            ->getOrder()
            ->get((int) $order_create['id_order']);
        $link_redirect = $this->context->link->getPageLink('order-confirmation', true, $this->context->language->id, [
            'id_cart' => $cart->id,
            'id_module' => $this->moduleInstance->id,
            'id_order' => $order->id,
            'key' => $order->secure_key,
        ]);

        $this->exitProcess($link_redirect, 'Redirecting to order-confirmation page');
    }

    private function exitProcess($url = '', $message = '')
    {
        if (is_string($message) && $message) {
            $this->logger->addLog($message);
        }
        if ($this->lock_id) {
            $delete_lock = $this->dependencies
                ->getPlugin()
                ->getLockRepository()
                ->deleteLock((int) $this->lock_id);
            if (!$delete_lock) {
                $this->logger->addLog('Lock cannot be deleted.', 'error');
            } else {
                $this->logger->addLog('Lock deleted.', 'debug');
            }
        }
        $this->tools_adapter->tool('redirect', $url);
    }
}
