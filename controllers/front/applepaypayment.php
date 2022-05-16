<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 * @author    PayPlug SAS
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

/**
 * @description
 * Treat ajax call
 */
class PayplugApplepaypaymentModuleFrontController extends ModuleFrontController
{
    private $dependencies;
    private $logger;
    private $plugin;

    /**
     * @description
     * Method that is executed after init() and checkAccess().
     * Used to process user input.
     *
     * return void
     * @throws Exception
     */
    public function postProcess()
    {
        $this->dependencies = new \PayPlugModule\classes\DependenciesClass();
        $this->plugin = $this->dependencies->getPlugin();
        $this->logger = $this->plugin->getLogger();
        $this->moduleInstance = $this
            ->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name);

        $order_confirmation_url = 'index.php?controller=order-confirmation&';

        try {
            // Patch Payment
            $token = Tools::getValue('token');
            $id_payment = Tools::getValue('id_payment');

            $apple_pay = array();
            $apple_pay['payment_token'] = $token;

            $payment = $this->dependencies->apiClass->retrievePayment($id_payment);

            // To update metadatas keys
            $data = array(
                'apple_pay' => $apple_pay
            );
            $update = $payment['resource']->update($data);

            if ($update->is_paid !== true) {
                $this->logger->addLog($update->failure->message);
                die(json_encode([
                    'result' => false
                ]));
            }

            // Create order in BO
            $cart_amount = (float)$this->context->cart->getOrderTotal(true, Cart::BOTH);

            $validateOrder_result = $this->moduleInstance->validateOrder(
                $this->context->cart->id,
                Configuration::get('PAYPLUG_ORDER_STATE_PAID'),
                $cart_amount,
                'Apple Pay with ' . $this->dependencies->name,
                false,
                array(), //$extra_vars
                (int)$this->context->cart->id_currency,
                false,
                $this->context->customer->secure_key
            );

            if ($validateOrder_result === true) {
                $id_order = Order::getOrderByCartId($this->context->cart->id);

                $link_redirect = __PS_BASE_URI__ . $order_confirmation_url
                    . 'id_cart=' . $this->context->cart->id . '&id_module=' . $this->moduleInstance->id
                    . '&id_order=' . $id_order . '&key=' . $this->context->customer->secure_key;

                die(json_encode([
                    'result' => true,
                    'link_redirect' => $link_redirect
                ]));
            }
        } catch (Exception $e) {
            $this->logger->addLog('Front controller applepaypayment : ' . $e);
            die(json_encode([
                'result' => false
            ]));
        }
    }
}
