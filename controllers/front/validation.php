<?php
/**
 * 2013 - 2016 PayPlug SAS
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
 *  @copyright 2013 - 2016 PayPlug SAS
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

require_once(dirname(__FILE__).'./../../../../config/config.inc.php');

/** Call init.php to initialize context */
require_once(_PS_MODULE_DIR_.'../init.php');
require_once(_PS_MODULE_DIR_.'payplug/classes/PayplugTools.php');
require_once(_PS_MODULE_DIR_.'payplug/classes/PayplugBackward.php');
require_once(_PS_MODULE_DIR_.'payplug/classes/PayplugLock.php');

/** Tips to include class of module and backward_compatibility */
$payplug = Module::getInstanceByName('payplug');

$debug = PayplugBackward::getConfiguration('PAYPLUG_DEBUG_MODE');

if ($debug) {
    require_once(dirname(__FILE__).'/../../classes/MyLogPHP.class.php');
    $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/validation-'.date("Y-m-d").'.csv');
    $log->info('Validation Starting.');
}

if (!($cart_id = Tools::getValue('cartid'))) {
    if ($debug) {
        $log->error('No cart ID.');
    }
    Payplug::redirectForVersion('index.php?controller=order&step=1');
}
$cart = new Cart($cart_id);
if (version_compare(_PS_VERSION_, '1.3', '<')) {
    $currency = Tools::setCurrency()->iso_code;
} elseif (version_compare(_PS_VERSION_, '1.5', '<')) {
    $currency = Currency::getCurrent()->iso_code;
} else {
    $id_currency = $cart->id_currency;
    $currency = Currency::getCurrency($id_currency);
    $context = Context::getContext();
}

if (version_compare(_PS_VERSION_, '1.5', '<')) {
    $order_confirmation_url = 'order-confirmation.php?';
} else {
    $order_confirmation_url = 'index.php?controller=order-confirmation&';
}

if ($pay_id = $payplug->getPaymentByCart((int)$cart_id)) {
    try {
        $payment = \Payplug\Payment::retrieve($pay_id);
        if ($payment->failure) {
            if ($debug) {
                $log->error($payment->failure->message);
            }
            Payplug::redirectForVersion('index.php?controller=order&step=1');
        }
        $is_paid = $payment->is_paid;
    } catch (Exception $e) {
        if ($debug) {
            $log->error('Payment cannot be retrieved.');
        }
        Payplug::redirectForVersion('index.php?controller=order&step=1');
    }
} else {
    $id_order = Order::getOrderByCartId($cart->id);
    $customer = new Customer((int)$cart->id_customer);
    $link_redirect = __PS_BASE_URI__.$order_confirmation_url.'id_cart='.$cart->id
        .'&id_module='.$payplug->id.'&id_order='.$id_order.'&key='.$customer->secure_key;
    Payplug::redirectForVersion($link_redirect);
}

if ($payment->save_card == 1 || ($payment->card->id != '' && $payment->hosted_payment != '')) {
    $res_payplug_card = $payplug->saveCard($payment);

    if (!$res_payplug_card) {
        if ($debug) {
            $log->error('Card cannot be saved.');
        }
    }
}

$payplug->deletePayment($payment->id, (int)$cart_id);

/**
 * If no current cart, redirect to order page
 */
if (!$cart->id) {
    if ($debug) {
        $log->error('No cart ID');
    }
    Payplug::redirectForVersion('index.php?controller=order&step=1');
}

/**
 * If no GET parameter with payment status code
 */
if (!($ps = Tools::getValue('ps')) || $ps != 1) {
    if ($debug) {
        if ($ps == 2) {
            $log->debug('GET parameter ps = '.$ps.' > Order has been cancelled on PayPlug page', $cart->id);
        } else {
            $log->error('Get cart > wrong GET parameter ps = '.$ps, $cart->id);
        }
    }
    Payplug::redirectForVersion('index.php?controller=order&step=1');
}

if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$payplug->active) {
    if ($debug) {
        $log->debug('Get cart > $cart->id_customer = '.$cart->id_customer);
        $log->debug('Get cart > $cart->id_address_delivery = '.$cart->id_address_delivery);
        $log->debug('Get cart > $cart->id_address_invoice = '.$cart->id_address_invoice);
        $log->debug('Get cart > $payplug->active = '.$payplug->active);
    }
    Payplug::redirectForVersion('index.php?controller=order&step=1');
}

if (!Payplug::moduleIsActive()) {
    if ($debug) {
        $log->error('Check if module is active > module is not yet activated validation process is going to die');
    }
    die($payplug->l('This payment method is not available.', 'validation'));
}

/**
 * Check customer
 */

if ($debug) {
    $log->debug('Check customer > $cart->id_customer = '.$cart->id_customer, $cart->id);
}
$customer = new Customer((int)$cart->id_customer);
if (!Validate::isLoadedObject($customer)) {
    if ($debug) {
        $log->error('Check customer > $customer is NOT valid = '.$cart->id_customer, $cart->id);
    }
    Payplug::redirectForVersion('index.php?controller=order&step=1');
}

/**
 * Check total cart
 */
if (version_compare(_PS_VERSION_, 1.4, '<')) {
    $total = (float)$cart->getOrderTotal(true, 3);
} else {
    $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
}

if ($debug) {
    $log->debug('Check total cart > $total = '.$total, $cart->id);
}

/**
 * Check cart 
 */
$check_cart = PayplugLock::check($cart->id);

if ($debug) {
    $log->debug('Check cart > PayplugLock::check', $cart->id);
}

/**
 * Create order
 */
$id_order = Order::getOrderByCartId($cart->id);

if (!$id_order) {
    if ($debug) {
        $log->debug('Create order > order is going to be created ', $cart->id);
    }

    $cart_lock = PayplugLock::addLock($cart->id);
    if ($debug) {
        $log->debug('Create order > lock is added to cart :'.$cart_lock, $cart->id);
    }

    /** Get the right order status following module configuration (Sandbox or not) */
    if ($is_paid) {
        $order_state = Payplug::getOsConfiguration('paid');
    } else {
        $order_state = Payplug::getOsConfiguration('pending');
    }

    if ($debug) {
        $log->debug('Create order > get the right order status (Sandbox or not) : '.$order_state, $cart->id);
    }

    if ($debug) {
        $log->debug('Create order >  validateOrder() params : ', $cart->id);
        $log->debug('Create order >  $cart->id = '.$cart->id, $cart->id);
        $log->debug('Create order >  $order_state = '.$order_state, $cart->id);
        $log->debug('Create order >  $total = '.$total, $cart->id);
        $log->debug('Create order >  $payplug->displayName = PayPlug', $cart->id);
        $log->debug('Create order >  false', $cart->id);
        $log->debug('Create order >  array()', $cart->id);
        $log->debug('Create order >  $currency->id = '.$currency->id, $cart->id);
        $log->debug('Create order >  false', $cart->id);
        $log->debug('Create order >  $customer->secure_key = '.$customer->secure_key, $cart->id);
    }

    $extra_vars = array(
        'transaction_id' => $payment->id
    );

    $validateOrder_result = $payplug->validateOrder(
        $cart->id,
        $order_state,
        $total,
        $payplug->displayName,
        false,
        $extra_vars,
        (int)$cart->id_currency,
        false,
        $customer->secure_key
    );
    $id_order = $payplug->currentOrder;

    if (version_compare(_PS_VERSION_, '1.5', '<')) {
        if (!$payplug->addPayplugOrderPayment($id_order, $payment->id)) {
            d('unable to create order_payment');
        }
    }

    $cart_unlock = PayplugLock::deleteLock($cart->id);
}

/** Change variable name, because $link is already instanciated */
$link_redirect = __PS_BASE_URI__.$order_confirmation_url.'id_cart='.$cart->id.'&id_module='.$payplug->id
    .'&id_order='.$id_order.'&key='.$customer->secure_key;
if ($debug) {
    $log->debug('Create order > $link_redirect = '.$link_redirect, $cart->id);
}
Payplug::redirectForVersion($link_redirect);
