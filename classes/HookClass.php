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

use Symfony\Component\Dotenv\Dotenv;

if (!defined('_PS_VERSION_')) {
    exit;
}

class HookClass
{
    private $assign;
    private $cache;
    private $cart;
    private $configuration;
    private $constant;
    private $context;
    private $dependencies;
    private $dispatcher;
    private $html;
    private $media;
    private $module;
    private $orderAdapter;
    private $orderStateAdapter;
    private $sql;
    private $tools;
    private $validate;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->assign = $this->dependencies->getPlugin()->getAssign();
        $this->cache = $this->dependencies->getPlugin()->getCache();
        $this->cart = $this->dependencies->getPlugin()->getCart();
        $this->configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->dispatcher = $this->dependencies->getPlugin()->getDispatcher();
        $this->media = $this->dependencies->getPlugin()->getMedia();
        $this->module = $this->dependencies->getPlugin()->getModule();
        $this->orderAdapter = $this->dependencies->getPlugin()->getOrder();
        $this->orderStateAdapter = $this->dependencies->getPlugin()->getOrderStateAdapter();
        $this->sql = $this->dependencies->getPlugin()->getSql();
        $this->tools = $this->dependencies->getPlugin()->getTools();
        $this->validate = $this->dependencies->getPlugin()->getValidate();
    }

    public function actionAdminLanguagesControllerSaveAfter($params)
    {
        $language = $params['return'];

        if (!$this->validate->validate('isLoadedObject', $language)) {
            // Given $params['lang'] must be an Language object
            return false;
        }

        if (!isset($language->iso_code) || !is_string($language->iso_code)) {
            // Given $params['lang']->iso_code must be a non empty string
            return false;
        }

        return $this->updateOrderStateLang($language->iso_code, (int) $language->id);
    }

    /**
     * @description Flush PayPlugCache, when PrestaShop cache cleared
     *
     * @return bool
     */
    public function actionClearCompileCache()
    {
        if ($this->sql->checkExistingTable($this->dependencies->name . '_cache', 1)) {
            return $this->cache->flushCache();
        }

        return true;
    }

    /**
     * @param $customer
     *
     * @return false|string
     */
    public function actionDeleteGDPRCustomer($customer)
    {
        $deleted = $this->dependencies
            ->getPlugin()
            ->getCardAction()
            ->deleteByCustomerAction((int) $customer['id']);
        if (!$deleted) {
            return \json_encode($this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('hook.actionDeleteGDPRCustomer.unableDelete', 'hookclass'));
        }

        return \json_encode(true);
    }

    /**
     * @param $customer
     *
     * @return string
     */
    public function actionExportGDPRData($customer)
    {
        if (!$cards = $this->dependencies->configClass->gdprCardExport((int) $customer['id'])) {
            return \json_encode($this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('hook.actionExportGDPRData.unableToExport', 'hookclass'));
        }

        return \json_encode($cards);
    }

    /**
     * @param $params
     *
     * @return bool
     */
    public function actionUpdateLangAfter($params)
    {
        if (!$this->validate->validate('isLoadedObject', $params['lang'])) {
            // Given $params['lang'] must be an Language object

            return false;
        }

        if (!isset($params['lang']->iso_code) || !is_string($params['lang']->iso_code)) {
            // Given $params['lang']->iso_code must be a non empty string

            return false;
        }

        return $this->updateOrderStateLang($params['lang']->iso_code, (int) $params['lang']->id);
    }

    /**
     * @description retrocompatibility of hookDisplayAdminOrderMain for version before 1.7.7.0
     *
     * @param $params
     *
     * @return false|string|void
     */
    public function adminOrder($params)
    {
        if (\version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
            return $this->displayAdminOrderMain($params);
        }
    }

    /**
     * @return string|void
     */
    public function customerAccount()
    {
        if (!$this->dependencies->configClass->isAllowed()) {
            return '';
        }

        $payplug_cards_url = $this->context->link->getModuleLink(
            $this->dependencies->name,
            'cards',
            ['process' => 'cardlist'],
            true
        );

        $this->assign->assign([
            'version' => _PS_VERSION_[0] . '.' . _PS_VERSION_[2],
            'payplug_cards_url' => $payplug_cards_url,
        ]);

        return $this->dependencies->configClass->fetchTemplate('customer/my_account.tpl');
    }

    /**
     * @param $params
     *
     * @return string
     */
    public function displayAdminOrderMain($params)
    {
        $this->html = '';

        $order = $this->orderAdapter->get((int) $params['id_order']);
        if (!$this->validate->validate('isLoadedObject', $order)
            || $order->module != $this->dependencies->name) {
            return $this->html;
        }

        $order_detail = $this->dependencies
            ->getPlugin()
            ->getOrderAction()
            ->renderDetail((int) $order->id);

        if (empty($order_detail)) {
            return $this->html;
        }

        $this->assign->assign($order_detail);

        $this->html .= $this->dependencies->configClass->fetchTemplate('/views/templates/admin/order/order.tpl');

        return $this->html;
    }

    public function actionAdminControllerSetMedia()
    {
        $controller = $this->dispatcher->getInstance()->getController();
        if ($controller
            && 'adminorders' == $this->tools->tool('strtolower', $controller)
            && $this->tools->tool('getValue', 'id_order')
        ) {
            $id_order = $this->tools->tool('getValue', 'id_order');
            $order = $this->orderAdapter->get((int) $id_order);

            if ($order->module == $this->dependencies->name) {
                $module_url = $this->constant->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/';
                $this->dependencies->mediaClass->setMedia([
                    $module_url . 'views/css/admin_order-' . $this->dependencies->version . '.css',
                    $module_url . 'views/js/admin_order-' . $this->dependencies->version . '.js',
                    $module_url . 'views/js/utilities-' . $this->dependencies->version . '.js',
                ]);
            }
        }
    }

    /**
     * @param $params
     *
     * @return false|void
     */
    public function displayHeader($params)
    {
        if (!$this->dependencies->configClass->isAllowed()) {
            return false;
        }

        $payplug_ajax_url = $this->context->link->getModuleLink($this->dependencies->name, 'ajax', [], true);
        $dotenv = new Dotenv();
        $dotenvFile = \dirname(\dirname(\dirname(__FILE__))) . '/payplugroutes/.env';
        if (\file_exists($dotenvFile)) {
            $dotenv->load($dotenvFile);
            $payplug_domain = $_ENV['PAYPLUG_DOMAIN'];
        } else {
            $payplug_domain = 'https://secure.payplug.com';
        }

        $this->media->addJsDef(
            [
                $this->dependencies->name . '_ajax_url' => $payplug_ajax_url,
                'PAYPLUG_DOMAIN' => $payplug_domain,
                'is_sandbox_mode' => (bool) $this->configuration->getValue('sandbox_mode'),
            ]
        );

        $moduleName = $this->tools->tool('getValue', 'modulename');

        if ($this->tools->tool('getValue', 'has_error')
            && $this->dependencies->name == $moduleName) {
            $this->media->addJsDef(['check_errors' => true]);
        }

        $adapter = $this->dependencies->loadAdapterPresta();

        if ($adapter && \method_exists($adapter, 'displayHeader')) {
            $this->media->addJsDef([
                'module_name' => $this->dependencies->name,
            ]);
            $adapter->displayHeader();
        }

        $id_card = $this->tools->tool('getValue', 'pc', 'new_card');

        // Is embeddedMode configured to show the lightbox..
        $show_lightbox = 'popup' == $this->configuration->getValue('embedded_mode')
            || (
                'integrated' == $this->configuration->getValue('embedded_mode')
                && ($this->tools->tool('getValue', 'inst') || $this->tools->tool('getValue', 'amex'))
            );
        // ... or is the payment with one click
        $show_lightbox = $show_lightbox || 'new_card' != $id_card;

        $show_lightbox = $show_lightbox
            && $this->tools->tool('getValue', 'embedded')
            && $this->dependencies->name == $moduleName;

        if ($show_lightbox) {
            $cart = $params['cart'];
            if (!$this->validate->validate('isLoadedObject', $cart)) {
                return;
            }
            $views_path = $this->constant->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/';
            $this->context->controller->addJS($views_path . 'js/embedded-' . $this->dependencies->version . '.js');

            $payment_options = [
                'id_card' => $id_card,
                'is_installment' => (bool) $this->tools->tool('getValue', 'inst'),
                'is_deferred' => (bool) $this->tools->tool('getValue', 'def'),
                'is_amex' => (bool) $this->tools->tool('getValue', 'amex'),
            ];
            $payment_method = 'new_card' != $id_card
                ? 'one_click'
                : ((bool) $this->tools->tool('getValue', 'inst')
                    ? 'installment'
                    : ((bool) $this->tools->tool('getValue', 'amex')
                        ? 'amex'
                        : 'standard'));
            $payment = $this->dependencies
                ->getPlugin()
                ->getPaymentAction()
                ->dispatchAction($payment_method, true);

            $dotenv = new Dotenv();
            $dotenvFile = \dirname(__FILE__, 4) . '/payplugroutes/.env';
            if (\file_exists($dotenvFile)) {
                $dotenv->load($dotenvFile);
                $integrated_payment_js_url = $_ENV['INTEGRATED_PAYMENT_DOMAIN'];
            } else {
                $integrated_payment_js_url = $this->dependencies
                    ->getPlugin()
                    ->getRoutes()
                    ->getSourceUrl()['integrated'];
            }

            if (!empty($payment)) {
                if (isset($payment['embedded']) && $payment['embedded']) {
                    // else show the popin
                    if ('integrated' == $this->configuration->getValue('embedded_mode')) {
                        $api_url = $integrated_payment_js_url;
                        $this->media->addJsDef([
                            'isIntegratedPayment' => true,
                        ]);
                    } else {
                        $api_url = $this->dependencies
                            ->getPlugin()
                            ->getRoutes()
                            ->getSourceUrl()['embedded'];
                    }

                    $this->assign->assign([
                        'payment_url' => $payment['return_url'],
                        'api_url' => $api_url,
                    ]);

                    return $this->dependencies->configClass->fetchTemplate('checkout/embedded.tpl');
                }
                $this->tools->tool('redirect', $payment['return_url']);
            } else {
                $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([
                    $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('hook.header.transactionNotCompleted', 'hookclass'),
                ]);
                $error_url = 'index.php?controller=order&step=3&has_error=1&modulename=' . $this->dependencies->name;
                $this->tools->tool('redirect', $error_url);
            }
        }

        $payment_methods = json_decode($this->dependencies->getPlugin()->getConfigurationClass()->getValue('payment_methods'), true);

        if ((bool) $payment_methods['oney']) {
            $this->media->addJsDef([
                $this->dependencies->name . '_oney' => true,
                $this->dependencies->name . '_oney_loading_msg' => $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('hook.header.loading', 'hookclass'),
            ]);
        }

        if ('integrated' == $this->configuration->getValue('embedded_mode')) {
            $integratedPaymentError = $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('hook.header.integratedPayment.error', 'hookclass');
            $sandbox = $this->configuration->getValue('sandbox_mode');
            $this->media->addJsDef([
                'integratedPaymentError' => $integratedPaymentError,
            ]);
        }

        if ((bool) $payment_methods['applepay']) {
            $this->media->addJsDef([
                'applePayPaymentRequestAjaxURL' => $this->context->link->getModuleLink($this->dependencies->name, 'applepaypaymentrequest', [], true),
                'applePayMerchantSessionAjaxURL' => $this->context->link->getModuleLink($this->dependencies->name, 'dispatcher', [], true),
                'applePayPaymentAjaxURL' => $this->context->link->getModuleLink($this->dependencies->name, 'validation', [], true),
                'applePayIdCart' => $this->context->cart->id,
            ]);
        }
    }

    /**
     * @return false
     */
    public function paymentReturn()
    {
        if (!$this->dependencies->configClass->isAllowed()) {
            return false;
        }

        $order_id = $this->tools->tool('getValue', 'id_order');
        $order = $this->orderAdapter->get((int) $order_id);
        // Check order state to display appropriate message
        $state = null;
        if (isset($order->current_state)
            && $order->current_state == $this->configuration->getValue('order_state_pending')
        ) {
            $state = 'pending';
        } elseif (isset($order->current_state)
            && $order->current_state == $this->configuration->getValue('order_state_paid')
        ) {
            $state = 'paid';
        } elseif (isset($order->current_state)
            && $order->current_state == $this->configuration->getValue('order_state_pending_test')
        ) {
            $state = 'pending_test';
        } elseif (isset($order->current_state)
            && $order->current_state == $this->configuration->getValue('order_state_paid_test')
        ) {
            $state = 'paid_test';
        }

        $this->assign->assign('state', $state);
        // Get order information for display
        $total_paid = \number_format($order->total_paid, 2, ',', '');
        $context = ['totalPaid' => $total_paid];
        if (isset($order->reference)) {
            $context['reference'] = $order->reference;
        }
        $this->assign->assign($context);

        return $this->dependencies->configClass->fetchTemplate('checkout/order-confirmation.tpl');
    }

    /**
     * @param string $iso_code
     * @param int $id_lang
     *
     * @return bool
     */
    private function updateOrderStateLang($iso_code = '', $id_lang = 0)
    {
        if (!$iso_code || !is_string($iso_code)) {
            // Given $iso_code must be a non empty string
            return false;
        }

        if (!$id_lang || !is_int($id_lang)) {
            // Given $id_lang must be a non null integer
            return false;
        }

        if (!in_array($iso_code, $this->configuration->allowed_iso_lang)) {
            return true;
        }

        $order_states = $this->configuration->order_states;

        foreach ($order_states as $name => $order_state) {
            // Update Live order state
            $config_name = 'order_state_' . $name;
            $order_state_name = $order_state['name'][$iso_code] . ' [PayPlug]';
            $id_order_state = $this->configuration->getValue($config_name);
            $live_order_state = $this->orderStateAdapter->get((int) $id_order_state);
            $live_order_state->name[$id_lang] = $order_state_name;
            $live_order_state->save();

            // Update Test order state
            $config_name .= '_test';
            $order_state_name = $order_state['name'][$iso_code] . ' [TEST]';
            $id_order_state = $this->configuration->getValue($config_name);
            $test_order_state = $this->orderStateAdapter->get((int) $id_order_state);
            $test_order_state->name[$id_lang] = $order_state_name;
            $test_order_state->save();
        }

        return true;
    }
}
