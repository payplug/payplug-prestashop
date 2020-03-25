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

class PayplugDispatcherModuleFrontController extends ModuleFrontController
{
    /**
     * @return string
     * @see FrontController::postProcess()
     *
     */
    public function postProcess()
    {
        if($method = Tools::getValue('method')) {
            $payplug = new Payplug();
            $id_cart = (int)Tools::getValue('id_cart');
            $id_card = Tools::getValue('pc');
            $is_deferred = (bool)Tools::getValue('def');

            $cart = new Cart($id_cart);
            if (!Validate::isLoadedObject($cart)) {
                return false;
            }

            $options = $payplug->getAvailableOptions($cart);

            $error_url = 'index.php?controller=order&step=3&error=1';

            if($options['oney'] && $method = 'oney' && $oney_type = Tools::getValue('oney_type')) {
                $payment = $payplug->preparePayment(['is_oney' => $oney_type]);
                if(!$payment['result']) {
                    $payplug->setPaymentErrorsCookie([$payplug->l('The transaction was not completed and your card was not charged.')]);
                    Tools::redirect($error_url);
                } else {
                    Tools::redirect($payment['return_url']);
                }
            }
            // if the payment is redirect and not a one click payment, prepare the payment and redirect
            elseif (!$options['embedded'] && $method != 'one_click') {
                $payment_options = [
                    'id_card' => $id_card,
                    'is_installment' => $method == 'installment',
                    'is_deferred' => $is_deferred,
                ];
                $payment = $payplug->preparePayment($payment_options);
                if(!$payment['result']) {
                    $payplug->setPaymentErrorsCookie([$payplug->l('The transaction was not completed and your card was not charged.')]);
                    Tools::redirect($error_url);
                } else {
                    Tools::redirect($payment['return_url']);
                }
            }
            // else reload the page with lightbox arg
            else {
                $return_url = 'index.php?controller=order&step=3&lightbox=1'
                    . ($method == 'installment' ? '&inst=1' : '')
                    . ($method == 'one_click' ? '&pc=' . $id_card : '')
                    . '&def='.(int)Tools::getValue('def');
                Tools::redirect($return_url);
            }
        }
    }
}
