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

require_once(dirname(__FILE__).'/../../../../config/config.inc.php');
require_once(_PS_MODULE_DIR_.'../init.php');
require_once(_PS_MODULE_DIR_.'payplug/payplug.php');
require_once(_PS_MODULE_DIR_.'payplug/classes/PayplugTools.php');
require_once(_PS_MODULE_DIR_.'payplug/classes/PayplugBackward.php');
require_once(_PS_MODULE_DIR_.'payplug/classes/PayplugLock.php');

$debug = PayplugBackward::getConfiguration('PAYPLUG_DEBUG_MODE');

if ($debug) {
    require_once(dirname(__FILE__).'/../../classes/MyLogPHP.class.php');
    $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/ipn-'.date("Y-m-d").'.csv');
    $log->info('IPN Starting.');
}

/** Call to payplug-php API */
require_once(_PS_MODULE_DIR_.'/payplug/lib/init.php');

/** Tips to include class of module and backward_compatibility */
$payplug = Module::getInstanceByName('payplug');

/** Check if logs is enable */
if (PayplugBackward::getConfiguration('PAYPLUG_DEBUG_MODE')) {
    /** Get display errors configuration */
    $display_errors = @ini_get('display_errors');
    /** Set display errors to true */
    @ini_set('display_errors', true);
}
/**
 * Check that payplug module is enabled
 */
if (!Payplug::moduleIsActive()) {
    if ($debug) {
        $log->info('IPN Failed: module not enabled.');
    }
    die('PayPlug module is not enabled.');
}

/**
 * define getallheaders function for nginx web server
 */
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (PayplugBackward::substr($name, 0, 5) == 'HTTP_') {
                $name = str_replace(
                    ' ',
                    '-',
                    ucwords(PayplugBackward::strtolower(str_replace('_', ' ', PayplugBackward::substr($name, 5, false))))
                );
                $headers[$name] = $value;
            } elseif ($name == 'CONTENT_TYPE') {
                $headers['Content-Type'] = $value;
            } elseif ($name == 'CONTENT_LENGTH') {
                $headers['Content-Length'] = $value;
            } else {
                $headers[$name] = $value;
            }
        }
        return $headers;
    }
}

/**
 * Get data from http request
 */
$headers = getallheaders();
if (isset($headers) && !empty($headers) && sizeof($headers)) {
    if ($debug) {
        $log->info('Reading headers.');
        foreach ($headers as $key => $value) {
            $log->debug($key . ' : ' . $value);
        }
    }
}

$body = PayplugBackward::fileGetContents('php://input');

/** Use the method of "Tools" for compatibility with future and older versions. */

//*/======= API
$payment = array();
$refund = array();
$type = 'call';
try {
    $resource = \Payplug\Notification::treat($body);

    if ($resource instanceof \Payplug\Resource\Payment
        && $resource->is_paid
    ) {
        if ($debug) {
            $log->info('Getting payment ressource.');
        }
        $type = 'payment';

        $payment['id'] = $resource->id;
        $payment['is_paid'] = $resource->is_paid;
        $payment['amount'] = $resource->amount;
        $payment['object'] = $resource->object;
        $payment['metadata'] = $resource->metadata;

        $payment['card']['exp_month'] = $resource->card->exp_month;
        $payment['card']['country'] = $resource->card->country;
        $payment['card']['brand'] = $resource->card->brand;
        $payment['card']['last4'] = $resource->card->last4;
        $payment['card']['exp_year'] = $resource->card->exp_year;
        $payment['card']['id'] = $resource->card->id;
        $payment['card']['metadata'] = $resource->card->metadata;

        $payment['customer']['city'] = $resource->customer->city;
        $payment['customer']['first_name'] = $resource->customer->first_name;
        $payment['customer']['last_name'] = $resource->customer->last_name;
        $payment['customer']['address1'] = $resource->customer->address1;
        $payment['customer']['address2'] = $resource->customer->address2;
        $payment['customer']['postcode'] = $resource->customer->postcode;
        $payment['customer']['country'] = $resource->customer->country;
        $payment['customer']['email'] = $resource->customer->email;

        $payment['hosted_payment']['cancel_url'] = $resource->hosted_payment->cancel_url;
        $payment['hosted_payment']['paid_at'] = $resource->hosted_payment->paid_at;
        $payment['hosted_payment']['return_url'] = $resource->hosted_payment->return_url;
        $payment['hosted_payment']['payment_url'] = $resource->hosted_payment->payment_url;

        $payment['failure'] = $resource->failure;

        $payment['notification']['url'] = $resource->notification->url;
        $payment['notification']['response_code'] = $resource->notification->response_code;
    } elseif ($resource instanceof \Payplug\Resource\Refund) {
        if ($debug) {
            $log->info('Getting refund ressource.');
        }
        $type = 'refund';

        $refund['payment_id'] = $resource->payment_id;
        $refund['created_at'] = $resource->created_at;
        $refund['object'] = $resource->object;
        $refund['amount'] = $resource->amount;
        $refund['currency'] = $resource->currency;
        $refund['is_live'] = $resource->is_live;
        $refund['id'] = $resource->id;
        $refund['metadata'] = $resource->metadata;

        if ($debug) {
            $log->error($refund['id']);
        }
    }
} catch (\Payplug\Exception\PayplugException $exception) {
    if ($debug) {
        $log->info('IPN Failed: catching exception or printing debug informations.');
    }
    $modules = Module::getModulesOnDisk();
    $mod_tab = array();
    foreach ($modules as $mod) {
        if ($mod->active == 1) {
            $mod_tab[] = $mod->name;
        }
    }
    $response = array(
        //'exception' => $exception,
        'is_module_active' => (int)$payplug->active,
        'sandbox_mode' => (int)PayplugBackward::getConfiguration('PAYPLUG_SANDBOX_MODE'),
        'embedded_mode' => (int)PayplugBackward::getConfiguration('PAYPLUG_EMBEDDED_MODE'),
        'one_click' => (int)PayplugBackward::getConfiguration('PAYPLUG_ONE_CLICK'),
        'cid' => PayplugBackward::getConfiguration('PAYPLUG_COMPANY_ID'),
        'module_list' => $mod_tab,
    );

    $cid = (int)PayplugBackward::getConfiguration('PAYPLUG_COMPANY_ID');
    if (Tools::getValue('debug') == 1 && Tools::getValue('cid') == $cid) {
        die(PayplugBackward::jsonEncode($response));
    }
}

$status = 0;
switch ($type) {
    case 'payment':
        $status = 0;
        break;
    case 'refund':
        $status = 4;
        break;
    default:
        $status = 0;
        break;
}
if ($debug) {
    $log->info('Type of IPN: ' . $status);
}
$status_available = array(
    Payplug::PAYMENT_STATUS_PAID,
    Payplug::PAYMENT_STATUS_REFUND
);

if (in_array($status, $status_available)) {
    if ($debug) {
        $log->info('Status available.');
    }
    $bool_sign = false;
    if ($type == 'payment') {
        $cart = new Cart((int)$payment['metadata']['ID Cart']);
        if (Validate::isLoadedObject($cart)) {
            $address = new Address((int)$cart->id_address_invoice);

            if (Validate::isLoadedObject($address)) {
                Context::getContext()->country = new Country((int)$address->id_country);
                Context::getContext()->customer = new Customer((int)$cart->id_customer);
                Context::getContext()->language = new Language((int)$cart->id_lang);
                Context::getContext()->currency = new Currency((int)$cart->id_currency);

                PayplugLock::check($cart->id);

                $order = new Order();
                $order_id = $order->getOrderByCartId($cart->id);

                if ($order_id) {
                    if ($debug) {
                        $log->info('Order already exists.');
                    }
                    if ($status == Payplug::PAYMENT_STATUS_PAID) {
                        $order = new Order($order_id);

                        $order_state = Payplug::getOsConfiguration('pending');
                        $current_state = $order->getCurrentState();

                        if ($current_state == $order_state) {
                            if (PayPlug::checkAmountPaidIsCorrect($payment['amount'] /100, $order)) {
                                $new_order_state = Payplug::getOsConfiguration('paid');
                            } else {
                                $new_order_state = PayplugBackward::getConfiguration('PAYPLUG_ORDER_STATE_ERROR');
                            }

                            $order_history = new OrderHistory();
                            $order_history->id_order = $order_id;
                            $order_history->changeIdOrderState((int)$new_order_state, $order_id);
                            $order_history->save();

                            if ($new_order_state == PayplugBackward::getConfiguration('PAYPLUG_ORDER_STATE_ERROR')) {
                                if ($debug) {
                                    $log->error('Order state error');
                                }
                                //Add message to warn user
                                $message = new Message();
                                $message->message = $payplug->l('The amount collected by PayPlug is not the same')
                                    .$payplug->l(' as the total value of the order');
                                $message->id_order = $order->id;
                                $message->id_cart = $order->id_cart;
                                $message->private = true;
                                $message->save();
                            }

                            //Ajout de la reférence de paiement
                            if (count($order->getOrderPayments()) == 0) {
                                $order->addOrderPayment($payment['amount'] / 100);
                            }
                            $order->current_state = $order_history->id_order_state;
                            $order->update();
                        }
                    }
                } else {
                    if ($debug) {
                        $log->info('Order does\'nt exists yet.');
                    }

                    PayplugLock::addLock($cart->id);

                    if ($status == Payplug::PAYMENT_STATUS_PAID) {
                        $extra_vars = array();

                        $extra_vars['transaction_id'] = $payment['id'];
                        $currency = (int)$cart->id_currency;
                        $customer = new Customer((int)$cart->id_customer);
                        $order_state = Payplug::getOsConfiguration('paid');
                        $amount = (float)$payment['amount'] / 100;
                        if ($debug) {
                            $log->debug('Customer ID: ' . $customer->id);
                            $log->debug('Order state: '.$order_state);
                            $log->debug('Amount: '.$amount);
                        }

                        $test = $payplug->validateOrder(
                            $cart->id,
                            $order_state,
                            $amount,
                            $payplug->displayName,
                            null,
                            $extra_vars,
                            $currency,
                            false,
                            $customer->secure_key
                        );

                        $order_id = Order::getOrderByCartId($cart->id);
                        $order = new Order($order_id);

                        if (version_compare(_PS_VERSION_, '1.5', '<')) {
                            if (!$payplug->addPayplugOrderPayment($order_id, $payment['id'])) {
                                if ($debug) {
                                    $log->error('IPN Failed: unable to create order payment.');
                                }
                                d('unable to create order_payment');
                            }
                        }

                        if (version_compare(_PS_VERSION_, '1.5', '>') && version_compare(_PS_VERSION_, '1.5.2', '<')) {
                            $order_payment = end($order->getOrderPayments());
                            $order_payment->transaction_id = $extra_vars['transaction_id'];
                            $order_payment->update();
                        }
                    }

                    PayplugLock::deleteLock($cart->id);
                }
                PayplugBackward::updateConfiguration('PAYPLUG_CONFIGURATION_OK', true);
            } else {
                if ($debug) {
                    $log->error('Missing or wrong parameters for address');
                }
                echo 'Error : missing or wrong parameters.';
                header($_SERVER['SERVER_PROTOCOL'].' 400 Missing or wrong parameters for address', true, 400);
                die;
            }
        } else {
            if ($debug) {
                $log->error('Missing or wrong parameters for cart');
            }
            echo 'Error : missing or wrong parameters.';
            header($_SERVER['SERVER_PROTOCOL'].' 400 Missing or wrong parameters for cart', true, 400);
            die;
        }
    } elseif ($type == 'refund') {
        $payment = $payplug->retrievePayment($refund['payment_id']);
        $is_totaly_refunded = $payment->is_refunded;
        if ($is_totaly_refunded) {
            if ($debug) {
                $log->debug('Totaly refunded');
                $log->debug('Cart ID: ' . $payment->metadata['ID Cart']);
            }
            $cart = new Cart((int)$payment->metadata['ID Cart']);
            $order = new Order();
            $order_id = $order->getOrderByCartId($cart->id);
            $order = new Order($order_id);
            if ($debug) {
                $log->debug('Order ID: ' . $order_id);
            }

            if ($payment->is_live == 1) {
                $new_order_state = (int)PayplugBackward::getConfiguration('PAYPLUG_ORDER_STATE_REFUND');
            } else {
                $new_order_state = (int)PayplugBackward::getConfiguration('PAYPLUG_ORDER_STATE_REFUND_TEST');
            }

            $current_state = $order->getCurrentState();

            if ($current_state != $new_order_state) {
                $order_history = new OrderHistory();
                $order_history->id_order = $order_id;
                $order_history->changeIdOrderState((int)$new_order_state, $order_id);
                $order_history->save();
            }
        } else {
            if ($debug) {
                $log->info('Partialy refunded.');
            }
        }
    }
} else {
    if ($debug) {
        $log->error('Status unavailable.');
    }
}

if (PayplugBackward::getConfiguration('PAYPLUG_DEBUG_MODE')) {
    @ini_set('display_errors', $display_errors);
}
