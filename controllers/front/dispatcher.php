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
 * @description Dispatch payment method
 */
class PayplugDispatcherModuleFrontController extends ModuleFrontController
{
    private $dependenciesClass;
    private $toolsAdapter;

    public function __construct()
    {
        parent::__construct();
        $this->dependenciesClass = new \PayPlug\classes\DependenciesClass();
        $this->toolsAdapter = $this->dependenciesClass->getPlugin()->getTools();
    }

    /**
     * @description
     * Method that is executed after init() and checkAccess().
     * Used to process user input.
     *
     * @throws Exception
     *
     * @return bool|void
     */
    public function postProcess()
    {
        if ($method = $this->toolsAdapter->tool('getValue', 'method')) {
            $id_cart = (int) $this->toolsAdapter->tool('getValue', 'id_cart');
            $id_card = $this->toolsAdapter->tool('getValue', 'pc');
            $is_deferred = (bool) $this->toolsAdapter->tool('getValue', 'def');
            $is_one_click = (bool) ($method === 'one_click');
            $is_installment = (bool) ($method === 'installment');
            $oney_type = $this->toolsAdapter->tool('getValue', $this->dependenciesClass->name . 'Oney_type');
            $is_oney = (bool) ($method === 'oney' && $oney_type);
            $is_bancontact = (bool) ($method === 'bancontact');
            $is_applepay = (bool) ($method === 'applepay');
            $is_amex = (bool) ($method === 'amex');

            $cart = new Cart($id_cart);
            if (!Validate::isLoadedObject($cart)) {
                return false;
            }

            $options = $this->dependenciesClass->configClass->getAvailableOptions($cart);

            $embedded = $options['embedded'] != 'redirected';
            $error_url = 'index.php?controller=order&step=3&has_error=1&modulename=' . $this->dependenciesClass->name;

            if ($options['oney'] && $is_oney) {
                $payment = $this->dependenciesClass->paymentClass->preparePayment(['is_oney' => $oney_type]);
                if (!$payment['result']) {
                    $this->toolsAdapter->tool('redirect', $error_url);
                } else {
                    $this->toolsAdapter->tool('redirect', $payment['return_url']);
                }
            } elseif ($options['amex'] && $is_amex) {
                $payment_options = [
                    'is_amex' => $is_amex,
                ];
                $payment = $this->dependenciesClass->paymentClass->preparePayment($payment_options);
                if (!$payment['result']) {
                    $this->dependenciesClass->paymentClass->setPaymentErrorsCookie([
                        $this->dependenciesClass->l('The transaction was not completed and your card was not charged.'),
                    ]);
                    $this->toolsAdapter->tool('redirect', $error_url);
                } else {
                    $this->toolsAdapter->tool('redirect', $payment['return_url']);
                }
            } elseif ($options['bancontact'] && $is_bancontact) {
                $payment_options = [
                    'id_card' => $id_card,
                    'is_bancontact' => $is_bancontact,
                ];
                $payment = $this->dependenciesClass->paymentClass->preparePayment($payment_options);
                if (!$payment['result']) {
                    $this->dependenciesClass->paymentClass->setPaymentErrorsCookie([
                        $this->dependenciesClass->l('The transaction was not completed and your card was not charged.'),
                    ]);
                    $this->toolsAdapter->tool('redirect', $error_url);
                } else {
                    $this->toolsAdapter->tool('redirect', $payment['return_url']);
                }
            } elseif (!$embedded && !$is_one_click && !$is_applepay) {
                // if the payment is redirect and not a one click payment, prepare the payment and redirect
                $payment_options = [
                    'id_card' => $id_card,
                    'is_installment' => $is_installment,
                    'is_deferred' => $is_deferred,
                ];
                $payment = $this->dependenciesClass->paymentClass->preparePayment($payment_options);
                if (!$payment['result']) {
                    $this->dependenciesClass->paymentClass->setPaymentErrorsCookie([
                        $this->dependenciesClass->l('The transaction was not completed and your card was not charged.'),
                    ]);
                    $this->toolsAdapter->tool('redirect', $error_url);
                } else {
                    $this->toolsAdapter->tool('redirect', $payment['return_url']);
                }
            } elseif ($options['applepay'] && $is_applepay) {
                $payment_options = [
                    'is_applepay' => $is_applepay,
                    'payment_context' => [
                        'apple_pay' => [
                            'domain_name' => $this->context->shop->domain_ssl,
                            'application_data' => base64_encode(json_encode([
                                'apple_pay_domain' => $this->context->shop->domain_ssl,
                            ])),
                        ],
                    ],
                ];
                $payment = $this->dependenciesClass->paymentClass->preparePayment($payment_options);
                if (!$payment['result']) {
                    exit(json_encode([
                        'result' => false,
                        'message' => 'Failed preparePayment',
                    ]));
                }

                exit(json_encode([
                    'result' => true,
                    'apiResponse' => $payment['resource']->payment_method,
                    'idPayment' => $payment['paymentDetails']['paymentId'],
                    'idCart' => $this->context->cart->id,
                ]));
            } else {
                // else reload the page with lightbox arg
                $return_url = 'index.php?controller=order&step=3&embedded=1'
                    . ($is_installment ? '&inst=1' : '')
                    . ($is_one_click ? '&pc=' . $id_card : '')
                    . '&def=' . (int) $is_deferred
                    . '&modulename=' . $this->dependenciesClass->name;

                $this->toolsAdapter->tool('redirect', $return_url);
            }
        }
    }
}
