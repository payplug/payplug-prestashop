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

namespace PayPlug\classes;

class ApplePayClass
{
    private $carrier;
    private $context;
    private $dependencies;
    private $logger;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->carrier =   $this->dependencies->getPlugin()->getCarrier();
        $this->logger =   $this->dependencies->getPlugin()->getLogger();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->currency = $this->dependencies->getPlugin()->getCurrency();
    }

    public function getPaymentRequest($page)
    {
        $additionalPaymentRequestDatas = array();
        $currency = $this->currency->getCurrency($this->context->cart->id_currency);

        if ($page != 'order') {
            $carriers = $this->carrier->getCarriers($this->context->language->id, true);
            $shippingMethods = array();

            foreach ($carriers as $key => $carrier) {
                $shippingMethods[$key]['label'] = $carrier['name'];
                $shippingMethods[$key]['detail'] = $carrier['delay'];
                $shippingMethods[$key]['amount'] = $this->context->cart->getPackageShippingCost($carrier['id_carrier']);
                $shippingMethods[$key]['identifier'] = 'FreeShip';
            }

            $summaryDetails = $this->context->cart->getSummaryDetails();

            $additionalPaymentRequestDatas = array(
                'shippingType' => 'storePickup',
                'shippingMethods' => $shippingMethods,
                'requiredShippingContactFields' => array(
                    'postalAddress',
                    'name',
                    'phone',
                    'email'
                ),
                'lineItems' => array(
                    array(
                        'label' => 'Products',
                        'amount' => $summaryDetails['total_products_wt']
                    ),
                    array(
                        'label' => 'Shipping',
                        'amount' => $summaryDetails['total_shipping']
                    )
                ),
            );
        }

        $applePayPaymentRequest = array(
            'countryCode' => $this->context->country->iso_code,
            'currencyCode' => $currency->iso_code,
            'merchantCapabilities' => array(
                'supports3DS'
            ),
            'supportedNetworks' => array(
                'visa',
                'masterCard',
                //'amex', Amex is not supported yet by PayPlug
                'discover'
            ),
            'total' => array(
                'label' => $this->context->shop->name,
                'type' => 'final',
                'amount' => $this->context->cart->getOrderTotal()
            ),
            'applicationData' => base64_encode(json_encode(array(
                'apple_pay_domain' => $this->context->shop->domain_ssl
            )))
        );

        $applePayPaymentRequest = array_merge($applePayPaymentRequest, $additionalPaymentRequestDatas);
        return $applePayPaymentRequest;
    }
}
