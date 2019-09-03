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
        $is_deferred = (bool)Tools::getValue('def') == 1;
        if ((int)Tools::getValue('disp') == 1) {
            if ((int)Tools::getValue('pay') == 1) {
                if (Tools::getValue('pc') != 'new_card') {
                    $payplug = new Payplug();
                    $id_cart = (int)Tools::getValue('id_cart');
                    $id_card = Tools::getValue('pc');
                    $payment = $payplug->preparePayment($id_cart, $id_card, false, $is_deferred);
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
                    $payment = $payplug->preparePayment($id_cart, null, true, $is_deferred);
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
                    Tools::redirect($this->context->link->getModuleLink('payplug', 'payment', array('def' => (int)$is_deferred), true));
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
