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
class PayplugApplepaymerchantsessionModuleFrontController extends ModuleFrontController
{
    private $configurationSpecific;
    private $dependencies;
    private $logger;
    private $paymentClass;
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
        $this->configurationSpecific = $this->plugin->getConfiguration();
        $this->paymentClass = $this->dependencies->paymentClass;

        if ($this->context->cart->id_address_delivery == $this->context->cart->id_address_invoice) {
            $address = new Address($this->context->cart->id_address_delivery);
            $shippingAddress = $address;
            $billingAddress = $address;

            $country = new Country($address->id_country, $this->context->language->id, $this->context->shop->id);
            $shippingAddressCountry = $country;
            $billingAddressCountry = $country;
        } else {
            $shippingAddress = new Address($this->context->cart->id_address_delivery);
            $billingAddress = new Address($this->context->cart->id_address_invoice);

            $shippingAddressCountry = new Country($shippingAddress->id_country, $this->context->language->id, $this->context->shop->id);
            $billingAddressCountry = new Country($billingAddress->id_country, $this->context->language->id, $this->context->shop->id);
        }

        $customer = new Customer($this->context->cart->id_customer);
        $gender = new Gender($customer->id_gender, $this->context->language->id, $this->context->shop->id);


        $currency = new Currency($this->context->cart->id_currency);
        $request_datas = array(
            'amount' => Tools::ps_round($this->context->cart->getOrderTotal()*100),
            'currency' => $currency->iso_code,
            'payment_method' => 'apple_pay',
            'payment_context' => array(
                'apple_pay' => array(
                    'domain_name' => $this->context->shop->domain_ssl,
                    'application_data' => base64_encode(json_encode(array(
                        'apple_pay_domain' => $this->context->shop->domain_ssl
                    )))
                )
            ),
            'billing' => array(
                'title' => $gender->name,
                'first_name' => $billingAddress->firstname,
                'last_name' => $billingAddress->lastname,
                'email' => $customer->email,
                'address1' => $billingAddress->address1 . ' ' . $billingAddress->address2,
                'postcode' => $billingAddress->postcode,
                'city' => $billingAddress->city,
                'country' => $billingAddressCountry->iso_code
            ),
            'shipping' => array(
                'title' => $gender->name,
                'first_name' => $shippingAddress->firstname,
                'last_name' => $shippingAddress->lastname,
                'email' => $customer->email,
                'address1' => $shippingAddress->address1 . ' ' . $shippingAddress->address2,
                'postcode' => $shippingAddress->postcode,
                'city' => $shippingAddress->city,
                'country' => $shippingAddressCountry->iso_code
            )
        );

        $errors = array($this->dependencies->l('payplug.applepayMerchantSession.transactionNotCompleted', 'applepaymerchantsession'));

        try {
            $createPayment = $this->dependencies->apiClass->createPayment($request_datas);

            die(json_encode([
                'result' => true,
                'apiResponse' => $createPayment['resource']->payment_method,
                'idPayment' => $createPayment['resource']->id,
                'template' => $this->paymentClass->displayPaymentErrors($errors)
            ]));
        } catch (Exception $e) {
            die(json_encode([
                'result' => false,
                'error_message' => $e
            ]));
        }
    }
}
