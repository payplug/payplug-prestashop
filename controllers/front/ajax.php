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

class PayplugAjaxModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        require_once(dirname(__FILE__).'/../../../../config/config.inc.php');
        require_once(_PS_MODULE_DIR_.'../init.php');
        include_once(_PS_MODULE_DIR_.'payplug/payplug.php');

        if (Tools::getValue('_ajax') == 1) {
            if (Tools::getIsset('pc')) {
                $payplug = new Payplug();
                if ((int)Tools::getValue('pay') == 1) {
                    $id_cart = (int)Tools::getValue('cart');
                    $id_card = Tools::getValue('pc');
                    $payment = $payplug->preparePayment($id_cart, $id_card);
                    die($payment);
                } else {
                    $context = Context::getContext();
                    $cookie = $context->cookie;
                    $id_customer = (int)$cookie->id_customer;
                    if ((int)$id_customer == 0) {
                        die(false);
                    }
                    $id_payplug_card = Tools::getValue('pc');
                    $valid_key = Payplug::setAPIKey();
                    $deleted = $payplug->deleteCard($id_customer, $id_payplug_card, $valid_key);
                    if ($deleted) {
                        die(true);
                    } else {
                        die(false);
                    }
                }
            }
        }
    }
}
