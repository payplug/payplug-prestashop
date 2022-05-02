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
class PayplugApplepayModuleFrontController extends ModuleFrontController
{
    private $dependencies;
    private $plugin;
    private $configurationSpecific;

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
        $this->configurationSpecific = $this->plugin->getConfiguration();

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

        /*echo "<pre>";
        var_dump($billingAddress->phone_mobile);
        die;*/
        $currency = new Currency($this->context->cart->id_currency);
        $request_datas = array(
            'amount' => Tools::ps_round($this->context->cart->getOrderTotal()*100),
            'currency' => $currency->iso_code,
            'payment_method' => 'apple_pay',
            'domain_name' => $this->context->shop->domain_ssl,
            'billing' => array(
                'title' => $gender->name,
                'first_name' => $billingAddress->firstname,
                'last_name' => $billingAddress->lastname,
                //'mobile_phone_number' => '',
                'email' => $customer->email,
                'address1' => $billingAddress->address1 . ' ' . $billingAddress->address2,
                'postcode' => $billingAddress->postcode,
                'city' => $billingAddress->city,
                'country' => $billingAddressCountry->iso_code,
            ),
            'shipping' => array(
                'title' => $gender->name,
                'first_name' => $shippingAddress->firstname,
                'last_name' => $shippingAddress->lastname,
                //'mobile_phone_number' => '',
                'email' => $customer->email,
                'address1' => $shippingAddress->address1 . ' ' . $shippingAddress->address2,
                'postcode' => $shippingAddress->postcode,
                'city' => $shippingAddress->city,
                'country' => $shippingAddressCountry->iso_code,
            )
        );

        /*if ($billingAddress->phone != '') {
            $request_datas['billing']['mobile_phone_number'] = $billingAddress->phone;
        }
        if ($shippingAddress->phone != '') {
            $request_datas['shipping']['mobile_phone_number'] = $shippingAddress->phone;
        }*/

        $request = json_encode($request_datas);

        $sandbox = (bool)$this->configurationSpecific->get('PAYPLUG_SANDBOX_MODE');
        $secret_key = (string)$this->configurationSpecific->get(
            'PAYPLUG_' . ($sandbox ? 'TEST' : 'LIVE') . '_API_KEY'
        );

        $ch = curl_init('https://api.omicron.notpayplug.com/v1/payments');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Access-Control-Allow-Origin: *',
            'Content-type: application/json;charset=UTF-8',
            'Authorization: Bearer sk_live_6OCRdZ7awtjbmzZJtoKzc5',
            //'Authorization: Bearer '.$secret_key,
            'PayPlug-Version: 2019-08-06'
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $apiResponse = curl_exec($ch);
        curl_close($ch);

        die($apiResponse);
    }
}
