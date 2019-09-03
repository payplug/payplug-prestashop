<?php
/**
 * 2013 - 2019 PayPlug SAS
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
 * @copyright 2013 - 2019 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

class PayPlugPaymentStandardModuleFrontController extends ModuleFrontController
{
    private $is_deferred = false;

    private function buildPayment($cart)
    {
        if (!is_object($cart)) {
            $cart = new Cart((int)$cart);
        }
        if (!Validate::isLoadedObject($cart)) {
            return false;
        } else {
            $amount = $this->getAmountFromCart($cart);
            $currency = Currency::getCurrency($cart->id_currency)['iso_code'];
            $billing = $this->buildBillingAddress($cart);
            $shipping = $this->buildShippingAddress($cart);

            $payment_tab = array(
                'currency' => $currency,
                'billing' => array(
                    'title' => '',
                    'first_name' => '',
                    'last_name' => '',
                    'email' => '',
                    'mobile_phone_number' => '',
                    'landline_phone_number' => '',
                    'address1' => '',
                    'address2' => '',
                    'postcode' => '',
                    'city' => '',
                    'state' => '',
                    'country' => '',
                    'language' => '',
                ),
                'shipping' => array(
                    'title' => '',
                    'first_name' => '',
                    'last_name' => '',
                    'email' => '',
                    'mobile_phone_number' => '',
                    'landline_phone_number' => '',
                    'address1' => '',
                    'address2' => '',
                    'postcode' => '',
                    'city' => '',
                    'state' => '',
                    'country' => '',
                    'language' => '',
                    'delivery_type' => '',
                ),
                'hosted_payment' => array(
                    'return_url' => '',
                    'cancel_url' => '',
                    'sent_by' => '',
                ),
                'notification_url' => '',
                'payment_method' => '',
                'force_3ds' => '',
                'save_card' => '',
                'allow_save_card' => '',
                'description' => '',
                'metadata' => '',
            );

            if ($this->is_deferred) {
                $payment_tab['authorized_amount'];
            } else {
                $payment_tab['amount'] = $amount;
            }

            return $payment_tab;
        }

        $one_click = (int)Configuration::get('PAYPLUG_ONE_CLICK');
        $installment = (int)Configuration::get('PAYPLUG_INST');
        $embedded_mode = (int)Configuration::get('PAYPLUG_EMBEDDED_MODE');
        $deferred = (int)Configuration::get('PAYPLUG_DEFERRED');
        $current_card = null;
        if ($id_card != null && $id_card != 'new_card') {
            $current_card = $this->getCardId(
                (int)$cart->id_customer,
                $id_card,
                (int)Configuration::get('PAYPLUG_COMPANY_ID')
            );
        }
        //currency
        $result_currency = array();
        $currency = $cart->id_currency;
        $result_currency = Currency::getCurrency($currency);
        $supported_currencies = explode(';', Configuration::get('PAYPLUG_CURRENCIES'));

        if (!in_array($result_currency['iso_code'], $supported_currencies)) {
            return false;
        }

        $currency = $result_currency['iso_code'];

        //amount
        $amount = $cart->getOrderTotal(true, Cart::BOTH);
        $amount = (int)(round(($amount * 100), PHP_ROUND_HALF_UP));
        $current_amounts = $this->getAmountsByCurrency($currency);
        $current_min_amount = $current_amounts['min_amount'];
        $current_max_amount = $current_amounts['max_amount'];

        if ($amount < $current_min_amount || $amount > $current_max_amount) {
            return false;
        }

        $customer = new Customer((int)$cart->id_customer);
        $address_invoice = new Address((int)$cart->id_address_invoice);
        $address_delivery = new Address((int)$cart->id_address_delivery);

        //hosted payment
        $return_url = $this->context->link->getModuleLink($this->name, 'validation',
            array('ps' => 1, 'cartid' => (int)$cart->id), true);
        $cancel_url = $this->context->link->getModuleLink($this->name, 'validation',
            array('ps' => 2, 'cartid' => (int)$cart->id), true);

        if ($one_click != 1 || ($one_click == 1 && ($id_card == null || $id_card == 'new_card'))) {
            $hosted_payment = array(
                'return_url' => $return_url,
                'cancel_url' => $cancel_url
            );
        }
        //notification
        $notification_url = $this->context->link->getModuleLink($this->name, 'ipn', array(), true);

        //payment method
        if ($one_click == 1 && $current_card != null && $id_card != 'new_card') {
            $payment_method = $current_card;
        }

        //force 3ds
        $force_3ds = false;

        //save card
        $allow_save_card = false;
        if ($one_click == 1 && Cart::isGuestCartByCartId($cart->id) != 1) {
            $allow_save_card = true;
        }

        $delivery_type = 'NEW';
        if ($cart->id_address_delivery == $cart->id_address_invoice) {
            $delivery_type = 'BILLING';
        } elseif ($address_delivery->isUsed()) {
            $delivery_type = 'VERIFIED';
        }

        // Shipping address fields

        // Get address country iso code
        $delivery_country_iso = $this->getIsoCodeByCountryId((int)$address_delivery->id_country);
        $invoice_country_iso = $this->getIsoCodeByCountryId((int)$address_invoice->id_country);
        $additional_metadatas = array();

        if (!$delivery_country_iso || !$invoice_country_iso) {
            $default_language = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
            $iso_code_list = $this->getIsoCodeList();
            if (in_array(Tools::strtoupper($default_language->iso_code), $iso_code_list)) {
                $iso_code = $default_language->iso_code;
            } else {
                $iso_code = 'FR';
            }
            if (!$delivery_country_iso) {
                $delivery_country = new Country($address_delivery->id_country);
                $additional_metadatas['cms_shipping_country'] = $delivery_country->iso_code;
                $delivery_country_iso = $iso_code;
            }
            if (!$invoice_country_iso) {
                $invoice_country = new Country($address_invoice->id_country);
                $additional_metadatas['cms_billing_country'] = $invoice_country->iso_code;
                $invoice_country_iso = $iso_code;
            }
        }

        $shipping = array(
            'title' => null,
            'first_name' => !empty($address_delivery->firstname) ? $address_delivery->firstname : null,  // required
            'last_name' => !empty($address_delivery->lastname) ? $address_delivery->lastname : null,  // required
            'company_name' => !empty($address_delivery->company) ? $address_delivery->company : null,  // optional
            'email' => $customer->email,  // required
            'landline_phone_number' => !empty($address_delivery->phone) ? $this->formatPhoneNumber($address_delivery->phone,
                $address_delivery->id_country) : null,  // optional
            'mobile_phone_number' => !empty($address_delivery->phone) ? $this->formatPhoneNumber($address_delivery->phone_mobile,
                $address_delivery->id_country) : null,  // optional
            'address1' => !empty($address_delivery->address1) ? $address_delivery->address1 : null,  // required
            'address2' => !empty($address_delivery->address2) ? $address_delivery->address2 : null,  // optional
            'postcode' => !empty($address_delivery->postcode) ? $address_delivery->postcode : null,  // required
            'city' => !empty($address_delivery->city) ? $address_delivery->city : null,  // required
            'country' => $delivery_country_iso,  // required
            'language' => $this->getIsoFromLanguageCode($this->context->language), // optional
            'delivery_type' => $delivery_type,  // optional
        );

        // Billing address fields
        $billing = array(
            'title' => null,
            'first_name' => !empty($address_invoice->firstname) ? $address_invoice->firstname : null,
            // required
            'last_name' => !empty($address_invoice->lastname) ? $address_invoice->lastname : null,
            // required
            'company_name' => !empty($address_delivery->company) ? $address_delivery->company : $address_invoice->firstname . ' ' . $address_invoice->lastname,
            // optional
            'email' => $customer->email,
            // required
            'landline_phone_number' => !empty($address_invoice->phone) ? $this->formatPhoneNumber($address_invoice->phone,
                $address_invoice->id_country) : null,
            // optional
            'mobile_phone_number' => !empty($address_invoice->phone) ? $this->formatPhoneNumber($address_invoice->phone_mobile,
                $address_invoice->id_country) : null,
            // optional
            'address1' => !empty($address_invoice->address1) ? $address_invoice->address1 : null,
            // required
            'address2' => !empty($address_invoice->address2) ? $address_invoice->address2 : null,
            // optional
            'postcode' => !empty($address_invoice->postcode) ? $address_invoice->postcode : null,
            // required
            'city' => !empty($address_invoice->city) ? $address_invoice->city : null,
            // required
            'country' => $invoice_country_iso,
            // required
            'language' => $this->getIsoFromLanguageCode($this->context->language),
            // optional
        );

        //payment

        //meta data
        $baseurl = Tools::getShopDomainSsl(true, false);
        $metadatas = array(
            'ID Client' => (int)$customer->id,
            'ID Cart' => (int)$cart->id,
            'Website' => $baseurl,
        );

        $payment_tab = array(
            'amount' => $amount,
            'currency' => $currency,
            'shipping' => $shipping,
            'billing' => $billing,
            'notification_url' => $notification_url,
            'force_3ds' => $force_3ds,
            'metadata' => array_merge($metadatas, $additional_metadatas),
        );

        if ($one_click == 1 && $current_card != null && $id_card != 'new_card') {
            $payment_tab['payment_method'] = $payment_method;
            $payment_tab['allow_save_card'] = false;
        } else {
            $payment_tab['hosted_payment'] = array(
                'return_url' => $hosted_payment['return_url'],
                'cancel_url' => $hosted_payment['cancel_url'],
            );
            $payment_tab['allow_save_card'] = $allow_save_card;
        }

        if ($installment == 1 && $isInstallment) {
            $installment_mode = (int)Configuration::get('PAYPLUG_INST_MODE');
            $schedule = array();
            for ($i = 0; $i < $installment_mode; $i++) {
                if ($i == 0) {
                    $schedule[$i]['date'] = 'TODAY';
                    $int_part = (int)($amount / $installment_mode);
                    $schedule[$i]['amount'] = (int)($int_part + ($amount - ($int_part * $installment_mode)));
                } else {
                    $delay = $i * 30;
                    $schedule[$i]['date'] = date('Y-m-d', strtotime("+ $delay days"));
                    $schedule[$i]['amount'] = (int)($amount / $installment_mode);
                }
            }

            $installment_options = array(
                'currency' => $currency,
                'schedule' => $schedule,
                'shipping' => $payment_tab['shipping'],
                'billing' => $payment_tab['billing'],
                'hosted_payment' => $hosted_payment,
                'notification_url' => $notification_url,
                'metadata' => $payment_tab['metadata'],
            );

            try {
                $this->storeInstallment('pending', (int)$cart->id);
                if (Configuration::get('PAYPLUG_DEBUG_MODE')) {
                    $log = new MyLogPHP(_PS_MODULE_DIR_ . $this->name . '/log/prepare_payment.csv');
                    $log->info('Starting installment.');
                }

                $inst = \Payplug\InstallmentPlan::create($installment_options);
                if ($inst->failure != null && !empty($inst->failure['message'])) {
                    $data = array(
                        'result' => false,
                        'response' => $inst->failure['message'],
                    );
                    if (version_compare(_PS_VERSION_, '1.7', '<')) {
                        die(json_encode($data));
                    } else {
                        return ($data);
                    }
                }
            } catch (Exception $e) {
                $messages = $this->catchErrorsFromApi($e->__toString());
                $data = array(
                    'result' => false,
                    'response' => count($messages) > 1 ? $messages : reset($messages),
                );
                if (version_compare(_PS_VERSION_, '1.7', '<')) {
                    die(json_encode($data));
                } else {
                    return ($data);
                }
            }
            $this->storeInstallment($inst->id, (int)$cart->id);
            if ($one_click == 1 || (int)$embedded_mode == 1) {
                $data = array(
                    'result' => 'new_card',
                    'embedded_mode' => (int)$embedded_mode,
                    'payment_url' => $inst->hosted_payment->payment_url,
                );
                $data = json_encode($data);
            } else {
                $data = $inst->hosted_payment->payment_url;
            }
            return $data;
        }
        try {
            if (Configuration::get('PAYPLUG_DEBUG_MODE')) {
                $log = new MyLogPHP(_PS_MODULE_DIR_ . $this->name . '/log/prepare_payment.csv');
                $log->info('Starting payment.');
                foreach ($payment_tab as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $n_key => $n_value) {
                            $log->info($n_key . ' : ' . $n_value);
                        }
                    } else {
                        $log->info($key . ' : ' . $value);
                    }
                }
            }
            $payment = \Payplug\Payment::create($payment_tab);
            if (($payment->is_paid == false && $one_click == 1 && $current_card != null && $id_card != 'new_card')
                || ($payment->failure == true && !empty($payment->failure['message']))
            ) {
                $data = array(
                    'result' => false,
                    'response' => $payment->failure['message'],
                );
                return ($data);
            }
        } catch (Exception $e) {
            $messages = $this->catchErrorsFromApi($e->__toString());
            $data = array(
                'result' => false,
                'response' => count($messages) > 1 ? $messages : reset($messages),
            );
            return ($data);
        }
        $this->storePayment($payment->id, (int)$cart->id);
        if ($one_click == 1 && $current_card != null && $id_card != 'new_card') {
            $data = array(
                'result' => true,
                'validation_url' => $return_url
            );
            return ($data);
        } elseif (($one_click == 1 && $id_card == 'new_card') || ($one_click != 1 && $id_card == 'new_card')) {
            $data = array(
                'result' => 'new_card',
                'embedded_mode' => (int)$embedded_mode,
                'payment_url' => $payment->hosted_payment->payment_url,
            );
            die(json_encode($data));
        } else {
            $payment_url = $payment->hosted_payment->payment_url;
            return $payment_url;
        }
    }

    private function getAmountFromCart($cart)
    {
        if (!is_object($cart)) {
            $cart = new Cart((int)$cart);
        }
        if (!Validate::isLoadedObject($cart)) {
            return false;
        } else {
            $amount = $cart->getOrderTotal(true, Cart::BOTH);
            return (int)(round(($amount * 100), PHP_ROUND_HALF_UP));
        }
    }

    private function buildShippingAddress($cart)
    {
        $customer = new Customer((int)$cart->id_customer);
        $address_delivery = new Address((int)$cart->id_address_delivery);

        $shippingAddress = array(
            'title' => null,
            'first_name' => !empty($address_delivery->firstname) ? $address_delivery->firstname : null,
            'last_name' => !empty($address_delivery->lastname) ? $address_delivery->lastname : null,
            'email' => $customer->email,
            'mobile_phone_number' => '',
            'landline_phone_number' => '',
            'address1' => '',
            'address2' => '',
            'postcode' => '',
            'city' => '',
            'state' => '',
            'country' => '',
            'language' => '',
            'delivery_type' => '',



            'title' => null,
            'first_name' => !empty($address_delivery->firstname) ? $address_delivery->firstname : null,  // required
            'last_name' => !empty($address_delivery->lastname) ? $address_delivery->lastname : null,  // required
            'company_name' => !empty($address_delivery->company) ? $address_delivery->company : null,  // optional
            'email' => $customer->email,  // required
            'landline_phone_number' => !empty($address_delivery->phone) ? $this->formatPhoneNumber($address_delivery->phone,
                $address_delivery->id_country) : null,  // optional
            'mobile_phone_number' => !empty($address_delivery->phone) ? $this->formatPhoneNumber($address_delivery->phone_mobile,
                $address_delivery->id_country) : null,  // optional
            'address1' => !empty($address_delivery->address1) ? $address_delivery->address1 : null,  // required
            'address2' => !empty($address_delivery->address2) ? $address_delivery->address2 : null,  // optional
            'postcode' => !empty($address_delivery->postcode) ? $address_delivery->postcode : null,  // required
            'city' => !empty($address_delivery->city) ? $address_delivery->city : null,  // required
            'country' => $delivery_country_iso,  // required
            'language' => $this->getIsoFromLanguageCode($this->context->language), // optional
            'delivery_type' => $delivery_type,  // optional
        );
        return $shippingAddress;
    }

    /**
     * @return string
     * @see FrontController::postProcess()
     *
     */
    public function postProcess()
    {
        if ((int)Tools::getValue('disp') == 1) {
            if ((int)Tools::getValue('pay') == 1) {
                if (Tools::getValue('pc') != 'new_card') {
                    $payplug = new Payplug();
                    $id_cart = (int)Tools::getValue('id_cart');
                    $id_card = Tools::getValue('pc');
                    $payment = $payplug->preparePayment($id_cart, $id_card);
                    if ($payment['result'] == true) {
                        Tools::redirect(
                            $this->context->link->getModuleLink(
                                'payplug',
                                'validation',
                                array('cartid' => $id_cart, 'ps' => 1),
                                true
                            )
                        );
                    } else {
                        Tools::redirect('index.php?controller=order&step=3&error=1&pc=' . $id_card);
                    }
                } elseif ((int)Tools::getValue('lightbox') == 1) {
                    Tools::redirect('index.php?controller=order&step=3&lightbox=1');
                } elseif ((int)Tools::getValue('inst') == 1) {
                    $payplug = new Payplug();
                    $id_cart = (int)Tools::getValue('id_cart');
                    $payment = $payplug->preparePayment($id_cart, null, true);
                    $payment_url = false;
                    if (is_array($payment)) {
                        if (!$payment['result']) {
                            Tools::redirect('index.php?controller=order&step=3&inst=1&error=1');
                        } else {
                            $payment_url = $payment['payment_url'];
                        }
                    } else {
                        $payment_data = json_decode($payment);
                        if (is_object($payment_data)) {
                            $payment_url = $payment_data->payment_url;
                        } else {
                            $payment_url = $payment;
                        }
                    }
                    Tools::redirect($payment_url);
                } else {
                    Tools::redirect($this->context->link->getModuleLink('payplug', 'payment', array(), true));
                }
            } elseif ((int)Tools::getValue('lightbox') == 1) {
                if ((int)Tools::getValue('inst') == 1) {
                    Tools::redirect('index.php?controller=order&step=3&lightbox=1&inst=1');
                } else {
                    Tools::redirect('index.php?controller=order&step=3&lightbox=1');
                }
            }
        } else {
            Tools::redirect('index.php');
        }
    }
}
