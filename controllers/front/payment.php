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

require_once(dirname(__FILE__).'/../../../../config/config.inc.php');
require_once(_PS_MODULE_DIR_.'../init.php');
require_once(_PS_MODULE_DIR_.'/payplug/payplug.php');
require_once(_PS_MODULE_DIR_.'/payplug/lib/init.php');

$valid_key = Payplug::setAPIKey();
\Payplug\Payplug::setSecretKey($valid_key);

$payplug = Module::getInstanceByName('payplug');
\Payplug\Core\HttpClient::addDefaultUserAgentProduct(
    'PayPlug-Prestashop',
    $payplug->version,
    'Prestashop/'._PS_VERSION_
);

$context = Context::getContext();
$cookie = $context->cookie;

$result_currency = array();
$cart = $context->cart;

$payment_url = $payplug->preparePayment($cart->id);
if (!is_array($payment_url)) {
    Tools::redirect($payment_url);
} else {
    die($payment_url['response']);
}
