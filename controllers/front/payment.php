<?php
/**
 * 2013 - 2020 PayPlug SAS
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
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

require_once(dirname(__FILE__) . '/../../../../config/config.inc.php');
require_once(_PS_MODULE_DIR_ . '../init.php');
require_once(_PS_MODULE_DIR_ . '/payplug/payplug.php');
require_once(_PS_MODULE_DIR_ . '/payplug/lib/init.php');

$payplug = Module::getInstanceByName('payplug');
\Payplug\Payplug::init([
    'secretKey' => $payplug->current_api_key,
    'apiVersion' => $payplug->api_version
]);

\Payplug\Core\HttpClient::addDefaultUserAgentProduct(
    'PayPlug-Prestashop',
    $payplug->version,
    'Prestashop/' . _PS_VERSION_
);

$context = Context::getContext();
$cookie = $context->cookie;

$result_currency = array();
$cart = $context->cart;
$is_deferred = (int)Tools::getValue('def');

$payment_url = $payplug->preparePayment($cart->id, 'new_card', false, $is_deferred);

if (!is_array($payment_url)) {
    Tools::redirect($payment_url);
} else {
    Tools::redirect('index.php?controller=order&step=3&error=1');
}
