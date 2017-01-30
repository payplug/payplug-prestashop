<?php
/**
 * 2013 - 2017 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2017 PayPlug SAS
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

class PayplugDispatcherModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     *
     * @return string
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
                        Tools::redirect('index.php?controller=order&step=3&error=1&pc='.$id_card);
                    }
                } elseif ((int)Tools::getValue('lightbox') == 1) {
                    Tools::redirect('index.php?controller=order&step=3&lightbox=1');
                } else {
                    Tools::redirect($this->context->link->getModuleLink('payplug', 'payment', array(), true));
                }
            } elseif ((int)Tools::getValue('lightbox') == 1) {
                Tools::redirect('index.php?controller=order&step=3&lightbox=1');
            }
        } else {
            Tools::redirect('index.php');
        }
    }
}
