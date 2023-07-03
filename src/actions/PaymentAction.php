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

namespace PayPlug\src\actions;

class PaymentAction
{
    private $dependencies;
    private $available_payment = [
        'amex',
        'applepay',
        'bancontact',
        'giropay',
        'ideal',
        'installment',
        'mybank',
        'one_click',
        'oney',
        'satispay',
        'sofort',
        'standard',
    ];

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    public function dispatchAction($method = '')
    {
        if (!is_string($method) || !$method) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $method must be a string.',
            ];
        }

        if (!in_array($method, $this->available_payment)) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $method given is not expected.',
            ];
        }

        $cartAdapter = $this->dependencies->getPlugin()->getCart();
        $toolsAdapter = $this->dependencies->getPlugin()->getTools();

        $cart = $cartAdapter->get((int) $toolsAdapter->tool('getValue', 'id_cart'));
        $options = $this->dependencies->configClass->getAvailableOptions($cart);

        if (!isset($options[$method])) {
            return [
                'result' => false,
                'message' => 'Given $method not found from getAvailableOptions',
            ];
        }

        if (!$options[$method]) {
            return [
                'result' => false,
                'message' => 'Given $method is not available',
            ];
        }

        switch ($method) {
            case 'applepay':
                $context = $this->dependencies->getPlugin()->getContext()->get();
                $payment_options = [
                    'is_applepay' => true,
                    'payment_context' => [
                        'apple_pay' => [
                            'domain_name' => $context->shop->domain_ssl,
                            'application_data' => base64_encode(json_encode([
                                'apple_pay_domain' => $context->shop->domain_ssl,
                            ])),
                        ],
                    ],
                ];

                break;
            case 'bancontact':
                $payment_options = [
                    'is_bancontact' => true,
                ];

                break;
            case 'giropay':
                $payment_options = [
                    'is_giropay' => true,
                ];

                break;
            case 'ideal':
                $payment_options = [
                    'is_ideal' => true,
                ];

                break;
            case 'mybank':
                $payment_options = [
                    'is_mybank' => true,
                ];

                break;
            case 'satispay':
                $payment_options = [
                    'is_satispay' => true,
                ];

                break;
            case 'sofort':
                $payment_options = [
                    'is_sofort' => true,
                ];

                break;
            case 'one_click':
                return [
                    'result' => true,
                    'return_url' => 'index.php?controller=order&step=3&embedded=1'
                        . '&pc=' . $toolsAdapter->tool('getValue', 'pc')
                        . '&def=' . (int) $options['deferred']
                        . '&modulename=' . $this->dependencies->name,
                ];
            case 'oney':
                $payment_options = [
                    'is_oney' => $toolsAdapter->tool('getValue', $this->dependencies->name . 'Oney_type'),
                ];

                break;
            default:
                if ('redirect' != $options['embedded']) {
                    return [
                        'result' => true,
                        'return_url' => 'index.php?controller=order&step=3&embedded=1'
                            . ('installment' == $method ? '&inst=1' : '')
                            . ('amex' == $method ? '&amex=1' : '')
                            . ('amex' != $method && $options['deferred'] ? '&def=1' : '')
                            . '&modulename=' . $this->dependencies->name,
                    ];
                }
                $payment_options = [
                    'is_installment' => 'installment' == $method,
                    'is_amex' => 'amex' == $method,
                    'is_deferred' => 'amex' != $method && $options['deferred'],
                ];

                break;
        }

        return $this->dependencies->paymentClass->preparePayment($payment_options);
    }
}
