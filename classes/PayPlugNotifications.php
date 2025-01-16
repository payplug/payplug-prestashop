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
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\classes;

use Payplug\Exception\UnknownAPIResourceException;
use Payplug\Notification;
use Payplug\Resource\InstallmentPlan;
use Payplug\Resource\Payment;
use Payplug\Resource\Refund;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class PayPlugNotifications
 * Use for treat notification from Payplug API.
 */
class PayPlugNotifications
{
    public $api_key;
    public $cart;
    public $except;
    public $flag;
    public $is_amex = false;
    public $is_applepay = false;
    public $is_bancontact = false;
    public $is_deferred = false;
    public $is_ideal = false;
    public $is_installment = false;
    public $is_mybank = false;
    public $is_oney = false;
    public $is_satispay = false;
    public $is_sofort = false;
    public $key;
    public $lock_key;
    public $logger;
    public $order;
    public $order_states = [];
    public $payment;
    public $resource;
    public $resp;
    public $sandbox;
    public $type;
    public $query;

    private $dependencies;

    // Plugin adapter
    private $addressAdapter;
    private $cartAdapter;
    private $configAdapter;
    private $constantAdapter;
    private $contextAdapter;
    private $countryAdapter;
    private $currencyAdapter;
    private $customerAdapter;
    private $languageAdapter;
    private $messageAdapter;
    private $orderAdapter;
    private $orderHistoryAdapter;
    private $shopAdapter;
    private $toolsAdapter;
    private $validateAdapter;

    private $amountCurrencyClass;
    private $apiClass;
    private $configuration;
    private $installmentClass;
    private $module;
    private $orderClass;
    private $paymentClass;
    private $payplugLock;
    private $plugin;
    private $validators;

    public function __construct()
    {
        $this->setConfig();
    }

    /**
     * @description Set the logger
     */
    public function setLogger()
    {
        $this->logger = $this->dependencies->getPlugin()->getLogger();
        $this->logger->addLog('Notification: setLogger');
        $this->logger = $this->dependencies->getPlugin()->getLogger();
        $this->logger->setProcess('notification');
    }

    /**
     * @description Entry point to treat the notification
     */
    public function treat()
    {
        // Notification identification
        $this->logger->addLog('Notification treatment and authenticity verification:');

        $this->logger->addLog('OK');

        if ($this->resource instanceof Payment) {
            $this->processPayment();
        } elseif ($this->resource instanceof Refund) {
            $this->processRefund();
        } elseif ($this->resource instanceof InstallmentPlan) {
            $this->processInstallment();
        }
    }

    /**
     * @descrition Check if the resource allow to save the payment card
     *
     * @return bool
     */
    private function canSaveCard()
    {
        $this->logger->addLog('Notification: canSaveCard');
        $can_save_card = $this->is_installment ? false : true;

        return $can_save_card && (
            $this->payment->save_card
                || (
                    $this->payment->card->id
                    && $this->payment->hosted_payment
                )
        );
    }

    /**
     * @descrition Check if the payment resource can be treated
     */
    private function checkIsValidPaymentResource()
    {
        if (!$this->payment->is_paid && !$this->is_deferred && !$this->is_oney) {
            $this->logger->addLog('The transaction is not paid yet.');
            $this->logger->addLog('No action will be done.');
            $this->exitProcess('The transaction is not paid.');
        }
    }

    /**
     * @descrition Dispatch the payment to create or update the relative order
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function dispatchPayment()
    {
        $this->logger->addLog('Notification: dispatchPayment');
        $id_order = $this->orderAdapter->getIdByCartId($this->cart->id);
        if ($id_order) {
            if (isset($this->resource->installment_plan_id) && $this->resource->installment_plan_id) {
                $this->exitProcess('No need to update a installment plan schedule.');
            }

            $order_update = $this->dependencies
                ->getPlugin()
                ->getOrderAction()
                ->updateAction($this->resource->id);
            if (!$order_update['result']) {
                $this->exitProcess('An error while order update: ' . $order_update['message'], 500);
            }
            $this->exitProcess('Order updated: ' . $order_update['message']);
        } else {
            if (isset($this->resource->failure) && null !== $this->resource->failure) {
                $this->logger->addLog('The payment has failed due to failure in resource.');
                $this->exitProcess('No treatment because payment has failed.');
            }

            $resource_id = isset($this->resource->installment_plan_id) && $this->resource->installment_plan_id
                ? $this->resource->installment_plan_id
                : $this->resource->id;

            $order_create = $this->dependencies
                ->getPlugin()
                ->getOrderAction()
                ->createAction($resource_id);
            if (!$order_create['result']) {
                $this->exitProcess('An error while order creation: ' . $order_create['message'], 500);
            }
            $this->exitProcess('Order created.');
        }
    }

    /**
     * @description Entry point to treat the notification
     *
     * @param string $str
     * @param int $http_code
     */
    private function exitProcess($str = '', $http_code = 200)
    {
        $this->logger->addLog('Notification: exitProcess');
        if (is_string($str) && $str) {
            $this->logger->addLog($str);
        }
        // check if queuing system is enabled
        if ($this->dependencies->configClass->isValidFeature('feature_queueing_system')) {
            $this->logger->addLog('PaypPlugNotifications::exitProcess: Attempting to update queue', 'debug');
            if ($this->lock_key) {
                $update_queue = $this->dependencies
                    ->getPlugin()
                    ->getQueueAction()
                    ->updateAction($this->cart->id);

                if (!$update_queue['result']) {
                    $this->logger->addLog('Queue entry cannot be updated.', 'error');
                }

                if (!empty($update_queue['exists']) && isset($update_queue['exists']['resource_id'])) {
                    $this->payment = $this->dependencies->apiClass->retrievePayment(
                        $update_queue['exists']['resource_id']
                    );
                    $this->processPayment();
                }
            }
            $this->logger->addLog('PaypPlugNotifications::exitProcess: exit process successfully', 'debug');
        } else {
            if ($this->lock_key) {
                $delete_lock = $this->dependencies
                    ->getPlugin()
                    ->getLockRepository()
                    ->deleteBy('id_cart', (int) $this->lock_key);
                if (!$delete_lock) {
                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                } else {
                    $this->logger->addLog('Lock deleted.', 'debug');
                }
            }
        }

        header($_SERVER['SERVER_PROTOCOL'] . ' ' . $http_code . ' ' . $str, true, $http_code);

        exit;
    }

    /**
     * @description Get the resource from the notification
     */
    private function getResource()
    {
        $this->logger->addLog('Notification: getResource');
        $body = $this->toolsAdapter->tool('file_get_contents', 'php://input');
        if (!$body) {
            $this->exitProcess('No resource found', 500);
        }

        $resource = json_decode($body, true);
        if (!is_array($resource) || empty($resource)) {
            $this->exitProcess('No resource found', 500);
        }

        $is_live = isset($resource['is_live']) && $resource['is_live'];
        $this->api_key = (bool) $is_live ?
            $this->configuration->getValue('live_api_key') :
            $this->configuration->getValue('test_api_key');

        try {
            $this->apiClass->setSecretKey($this->api_key);
            $this->resource = Notification::treat($body);

            $this->logger->addLog('Resource ID: ' . $this->resource->id);
        } catch (UnknownAPIResourceException $exception) {
            $this->exitProcess($exception->getMessage(), 500);
        }
    }

    /**
     * @description Treat the notification has an installment
     */
    private function processInstallment()
    {
        $this->logger->addLog('Notification: processInstallment');
        $this->logger->addLog('Installment ID: ' . $this->resource->id);
        $this->logger->addLog('Active : ' . (int) $this->resource->is_active);
        $this->exitProcess('Installment notification');
    }

    /**
     * @description Treat the notification has a payment
     */
    private function processPayment()
    {
        $this->logger->addLog('Notification: processPayment');

        // Set the payment
        $this->setPayment();
        if (!$this->payment) {
            $this->logger->addLog('Can\'t retrieve payment with the TEST and LIVE API Key');
            $this->exitProcess('Can\'t retrieve payment with the TEST and LIVE API Key', 500);
        }

        // Set the order state
        $this->setOrderStates();

        // Set the order state
        $this->setResourceProps(); // hydrate $resource_props

        // Check the payment ressource
        $this->checkIsValidPaymentResource();

        // Set cart from resource
        $this->setCartFromResource();

        // Set Context
        $this->setContext();

        // Set Lock or Queue
        $this->setLockOrQueue();

        // Dipatch to the create|update process
        $this->dispatchPayment();
    }

    /**
     * @description Treat the notification has a refund
     */
    private function processRefund()
    {
        $this->logger->addLog('Notification: processRefund');
        $this->logger->addLog('Refund ID : ' . $this->resource->id);

        $payment = $this->apiClass->retrievePayment($this->resource->payment_id);
        if (!$payment['result']) {
            $this->logger->addLog('Payment cannot be retrieved: ' . $payment['message'], 'error');
            $this->exitProcess($payment['message'], 500);
        }
        $this->payment = $payment['resource'];
        $this->setOrderStates();

        if ($this->payment->installment_plan_id) {
            $installment = $this->apiClass->retrieveInstallment($this->payment->installment_plan_id);
            if (!$installment['result']) {
                $this->logger->addLog('Installment cannot be retrieved: ' . $installment['message'], 'error');
                $this->exitProcess($installment['message'], 500);
            }

            $installment = $installment['resource'];
            $meta = $installment->metadata;
        } else {
            $meta = $this->payment->metadata;
        }

        $is_totaly_refunded = $this->payment->is_refunded;
        if ($is_totaly_refunded) {
            $this->logger->addLog('TOTAL REFUND MODE');
            $cart_id = '';

            if (isset($meta['Cart'])) {
                $cart_id = (int) $meta['Cart'];
                $this->logger->addLog('Cart ID : ' . $cart_id);
            } elseif (isset($meta['ID Cart'])) {
                $cart_id = (int) $meta['ID Cart'];
                $this->logger->addLog('Cart ID : ' . $cart_id);
            } else {
                $this->logger->addLog(
                    'Can\'t be refunded, because there is an error during retrieving Cart ID.',
                    'error'
                );
                $this->exitProcess('Can\'t be refunded, because there is an error during retrieving Cart ID.', 500);
            }

            $this->cart = $this->cartAdapter->get((int) $cart_id);

            if (!$this->validateAdapter->validate('isLoadedObject', $this->cart)) {
                $this->logger->addLog('Cart cannot be loaded.', 'error');
                $this->logger->addLog('$cart_id : ' . $cart_id, 'debug');
                $this->exitProcess('Cart cannot be loaded.', 500);
            }

            $id_order = (int) $this->orderAdapter->getIdByCartId((int) $cart_id);
            $this->order = $this->orderAdapter->get((int) $id_order);
            $this->logger->addLog('Order ID : ' . $this->order->id);
            if (!$this->validateAdapter->validate('isLoadedObject', $this->order)) {
                $this->logger->addLog('Order cannot be loaded.', 'error');
                $this->exitProcess('Order cannot be loaded.', 500);
            }

            // Set lock Lock the process with id_cart from order object
            if ($this->dependencies->configClass->isValidFeature('feature_queueing_system')) {
                $this->logger->addLog('Notification: Attempting to set queue for Cart ID: ' . $this->cart->id, 'notice');
                $create_queue = $this->dependencies
                    ->getPlugin()
                    ->getQueueAction()
                    ->hydrateAction($this->cart->id, $this->resource->id);
                if (!$create_queue['result']) {
                    $this->exitProcess('Error: Queue cannot be created for Cart ID: ' . $this->cart->id, 500);
                }
                if ($create_queue['exists']) {
                    $this->exitProcess('Queue already exists for Cart ID: ' . $this->cart->id);
                }

                $this->logger->addLog('Queue created successfully for Cart ID: ' . $this->cart->id, 'notice');
            } else {
                do {
                    $cart_lock = $this->payplugLock->createLockG2((int) $this->cart->id, 'ipn');
                    if (!$cart_lock) {
                        $checkReturn = $this->payplugLock->check((int) $this->cart->id);
                        if ('stop ipn' == $checkReturn) {
                            $this->exitProcess('Lock cannot be created.', 500);
                        }
                    } else {
                        $this->logger->addLog('Lock created', 'notice');
                        $this->lock_key = $this->cart->id;
                    }
                } while (!$cart_lock);
            }

            $new_order_state = $this->order_states['refund'];
            $current_state = (int) $this->dependencies
                ->getPlugin()
                ->getOrderRepository()
                ->getCurrentOrderState((int) $this->order->id);

            $this->logger->addLog('Current state: ' . $current_state);

            if ($current_state != $new_order_state) {
                $this->dependencies
                    ->getPlugin()
                    ->getOrderClass()
                    ->updateOrderState($this->order, (int) $new_order_state);
            } else {
                $this->logger->addLog('Order status is already \'refunded\'');
                $this->exitProcess('Order status is already \'refunded\'');
            }
        } else {
            $this->logger->addLog('PARTIAL REFUND');
            $this->exitProcess('PARTIAL REFUND');
        }
    }

    /**
     * @description Set $this->cart in the DB from the resource id
     */
    private function setCartFromResource()
    {
        $this->logger->addLog('Notification: setCartFromResource');
        $resource_id = isset($this->resource->installment_plan_id) && $this->resource->installment_plan_id
            ? $this->resource->installment_plan_id
            : $this->resource->id;
        $payment = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $resource_id);
        if (empty($payment)) {
            if (isset($this->resource->failure->code) && 'timeout' == $this->resource->failure->code) {
                $this->logger->addLog('Payment timeout for payment ID: ' . $this->resource->id);
                $this->exitProcess('Payment timeout for payment ID: ' . $this->resource->id, 200);
            }

            $error_msg = 'The cart cannot be found with payment ID: ' . $this->resource->id;
            $this->exitProcess($error_msg, $this->is_oney ? 242 : 500);
        }

        $this->cart = $this->cartAdapter->get((int) $payment['id_cart']);
        if (!$this->validateAdapter->validate('isLoadedObject', $this->cart)) {
            $this->logger->addLog('The cart cannot be loaded with id ' . $payment['id_cart'], 'error');
            $this->exitProcess('The cart cannot be loaded.', 500);
        }
    }

    /**
     * @description Set adapter use for the notification
     */
    private function setAdapters()
    {
        $this->addressAdapter = $this->dependencies->getPlugin()->getAddress();
        $this->cartAdapter = $this->dependencies->getPlugin()->getCart();
        $this->configAdapter = $this->dependencies->getPlugin()->getConfiguration();
        $this->constantAdapter = $this->dependencies->getPlugin()->getConstant();
        $this->contextAdapter = $this->dependencies->getPlugin()->getContext();
        $this->countryAdapter = $this->dependencies->getPlugin()->getCountry();
        $this->currencyAdapter = $this->dependencies->getPlugin()->getCurrency();
        $this->customerAdapter = $this->dependencies->getPlugin()->getCustomer();
        $this->languageAdapter = $this->dependencies->getPlugin()->getLanguage();
        $this->messageAdapter = $this->dependencies->getPlugin()->getMessage();
        $this->orderAdapter = $this->dependencies->getPlugin()->getOrder();
        $this->orderHistoryAdapter = $this->dependencies->getPlugin()->getOrderHistory();
        $this->shopAdapter = $this->dependencies->getPlugin()->getShop();
        $this->toolsAdapter = $this->dependencies->getPlugin()->getTools();
        $this->validateAdapter = $this->dependencies->getPlugin()->getValidate();
    }

    /**
     * @description Set the notification's global configuration
     */
    private function setConfig()
    {
        $this->key = microtime(true) * 10000;
        $this->flag = false;
        $this->except = null;
        $this->resp = [];
        $this->dependencies = new DependenciesClass();
        $this->validators = $this->dependencies->getValidators();
        $this->setAdapters();

        $this->apiClass = $this->dependencies->apiClass;
        $this->orderClass = $this->dependencies->orderClass;
        $this->paymentClass = $this->dependencies->paymentClass;
        $this->installmentClass = $this->dependencies->installmentClass;
        $this->amountCurrencyClass = $this->dependencies->amountCurrencyClass;
        $this->payplugLock = $this->dependencies->payplugLock;

        $this->configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $this->module = $this->dependencies->getPlugin()->getModule()->getInstanceByName($this->dependencies->name);
        $this->sandbox = $this->configuration->getValue('sandbox_mode');
        $this->query = $this->dependencies->getPlugin()->getQueryRepository();

        $this->setLogger();
        $this->getResource();
    }

    /**
     * @description Set the context of the order
     *
     * @param $id_cart
     */
    private function setContext()
    {
        $this->logger->addLog('Notification: setContext');
        if (!isset($this->context)) {
            $this->context = $this->contextAdapter->get();
        }

        $this->context->cart = $this->cart;
        $address = $this->addressAdapter->get((int) $this->cart->id_address_invoice);
        $this->context->country = $this->countryAdapter->get((int) $address->id_country);
        $this->context->customer = $this->customerAdapter->get((int) $this->cart->id_customer);
        $this->context->language = $this->languageAdapter->get((int) $this->cart->id_lang);
        $this->context->currency = $this->currencyAdapter->get((int) $this->cart->id_currency);
        if (isset($this->cart->id_shop)) {
            $this->context->shop = $this->shopAdapter->get((int) $this->cart->id_shop);
        }

        $this->logger->addLog('Context setted');
    }

    /**
     * set up the locking mechanism or queue creation for a given cart.
     */
    private function setLockOrQueue()
    {
        if (!isset($this->cart) || !isset($this->resource)) {
            $this->exitProcess('Cart or resource is not set.', 400);
        }
        // check if queueing system is enabled
        if ($this->dependencies->configClass->isValidFeature('feature_queueing_system')) {
            $this->logger->addLog('Notification: Attempting to set queue for Cart ID: ' . $this->cart->id);
            $create_queue = $this->dependencies
                ->getPlugin()
                ->getQueueAction()
                ->hydrateAction($this->cart->id, $this->resource->id);

            if (!$create_queue['result']) {
                $this->exitProcess('Error: Queue cannot be created for Cart ID: ' . $this->cart->id, 500);
            }

            if ($create_queue['exists']) {
                $this->exitProcess('Queue already exists for Cart ID: ' . $this->cart->id);
            }

            $this->lock_key = $this->cart->id;

            $this->logger->addLog('Queue created successfully for Cart ID: ' . $this->cart->id);
        } else {
            $this->logger->addLog('Notification: Attempting to set lock for Cart ID: ' . $this->cart->id);
            $cart_lock = $this->payplugLock->createLockG2($this->cart->id, 'ipn');
            if (!$cart_lock) {
                $this->exitProcess('Lock cannot be created for Cart ID: ' . $this->cart->id, 500);
            }
            $this->lock_key = $this->cart->id;
            $this->logger->addLog('Lock created for Cart ID: ' . $this->cart->id);
        }
    }

    /**
     * @description Set the order state from configuration
     */
    private function setOrderStates()
    {
        $this->logger->addLog('Notification: setOrderStates');
        $state_addons = ($this->payment->is_live ? '' : '_test');
        $this->order_states = [
            'auth' => $this->configuration->getValue('order_state_auth' . $state_addons),
            'cancelled' => $this->configuration->getValue('order_state_canceled' . $state_addons),
            'error' => $this->configuration->getValue('order_state_error' . $state_addons),
            'expired' => $this->configuration->getValue('order_state_exp' . $state_addons),
            'oney' => $this->configuration->getValue('order_state_oney_pg' . $state_addons),
            'outofstock_paid' => $this->configAdapter->get('PS_OS_OUTOFSTOCK_PAID'),
            'paid' => $this->configuration->getValue('order_state_paid' . $state_addons),
            'pending' => $this->configuration->getValue('order_state_pending' . $state_addons),
            'refund' => $this->configuration->getValue('order_state_refund' . $state_addons),
        ];
    }

    private function setPayment()
    {
        $this->logger->addLog('Notification: setPayment');
        if ($this->payment) {
            return true;
        }
        $payment = $this->apiClass->retrievePayment($this->resource->id);
        if (!$payment['result']) {
            if ($this->sandbox) {
                $this->apiClass->initializeApi(false);
                $payment = $this->apiClass->retrievePayment($this->resource->id);
            } else {
                $this->apiClass->initializeApi(true);
                $payment = $this->apiClass->retrievePayment($this->resource->id);
            }
        }

        if (!$payment['result']) {
            $this->logger->addLog('Can\'t retrieve payment with pay id: ' . $this->resource->id, 'debug');
            $this->apiClass->initializeApi((bool) $this->sandbox);
            $this->payment = null;
        } else {
            $this->payment = $payment['resource'];
        }
    }

    private function setResourceProps()
    {
        $this->logger->addLog('Notification: setResourceProps');
        // Define if payment is oney resource
        $oney_payment_methods = [
            'oney_x3_with_fees',
            'oney_x4_with_fees',
            'oney_x3_without_fees',
            'oney_x4_without_fees',
        ];
        if (isset($this->payment->payment_method, $this->payment->payment_method['type'])) {
            $this->is_oney = in_array($this->payment->payment_method['type'], $oney_payment_methods);
        }

        // Define if payment is bancontact resource
        if (isset($this->payment->payment_method, $this->payment->payment_method['type'])) {
            $this->is_bancontact = 'bancontact' == $this->payment->payment_method['type'];
        }

        // Define if payment is deferred resource
        $this->is_deferred = !$this->is_oney && $this->validators['payment']->isDeferred($this->payment)['result'];

        // Define if payment is from installment
        $this->is_installment = $this->validators['payment']->isInstallment($this->payment->id)['result'];

        // Define if payment is applepay resource
        if (isset($this->payment->payment_method, $this->payment->payment_method['type'])) {
            $this->is_applepay = 'apple_pay' == $this->payment->payment_method['type'];
        }

        // Define if payment is amex resource
        if (isset($this->payment->payment_method, $this->payment->payment_method['type'])) {
            $this->is_amex = 'american_express' == $this->payment->payment_method['type'];
        }

        // Define if payment is ideal resource
        if (isset($this->payment->payment_method, $this->payment->payment_method['type'])) {
            $this->is_ideal = 'ideal' == $this->payment->payment_method['type'];
        }

        // Define if payment is mybank resource
        if (isset($this->payment->payment_method, $this->payment->payment_method['type'])) {
            $this->is_mybank = 'mybank' == $this->payment->payment_method['type'];
        }

        // Define if payment is satispay resource
        if (isset($this->payment->payment_method, $this->payment->payment_method['type'])) {
            $this->is_satispay = 'satispay' == $this->payment->payment_method['type'];
        }

        // Define if payment is sofort resource
        if (isset($this->payment->payment_method, $this->payment->payment_method['type'])) {
            $this->is_sofort = 'sofort' == $this->payment->payment_method['type'];
        }
    }
}
