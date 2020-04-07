<?php


function hookAdminOrder($params)
{
    if (!$this->active) {
        return;
    }

    $this->html = '';
    $order = new Order((int)$params['id_order']);
    if (!Validate::isLoadedObject($order)) {
        return false;
    }

    if ($order->module != $this->name) {
        return false;
    }

    $show_popin = false;
    $display_refund = false;
    $show_menu_refunded = false;
    $show_menu_update = false;
    $show_menu_installment = false;
    $show_menu_payment = false;
    $pay_error = '';
    $amount_refunded_payplug = 0;
    $amount_available = 0;

    $admin_ajax_url = $this->getAdminAjaxUrl('AdminModules', (int)$params['id_order']);
    $amount_refunded_presta = $this->getTotalRefunded($order->id);

    if ($inst_id = $this->getPayplugInstallmentCart($order->id_cart)) {
        $payment_list = array();
        if (!$inst_id || empty($inst_id) || !$installment = $this->retrieveInstallment($inst_id)) {
            if (Configuration::get('PAYPLUG_SANDBOX_MODE') == 1) {
                $this->setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
                if (empty($inst_id) || !$installment = $this->retrieveInstallment($inst_id)) {
                    $this->setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
                    return false;
                }
            } elseif (Configuration::get('PAYPLUG_SANDBOX_MODE') == 0) {
                $this->setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
                if (empty($inst_id) || !$installment = $this->retrieveInstallment($inst_id)) {
                    $this->setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
                    return false;
                }
            }
        }

        $pay_mode = $installment->is_live ? $this->l('LIVE') : $this->l('TEST');
        $payments = $order->getOrderPaymentCollection();
        $pps = array();
        if (count($payments) > 0) {
            foreach ($payments as $payment) {
                $pps[] = $payment->transaction_id;
            }
        }

        $payment_list_new = array();
        foreach ($installment->schedule as $schedule) {
            if ($schedule->payment_ids != null) {
                foreach ($schedule->payment_ids as $pay_id) {
                    $p = $this->retrievePayment($pay_id);
                    $payment_list_new[] = $this->buildPaymentDetails($p);
                    if ((int)$p->is_paid == 0) {
                        $amount_refunded_payplug += 0;
                        $amount_available += 0;
                    } elseif ((int)$p->is_refunded == 1) {
                        $amount_refunded_payplug += ($p->amount_refunded) / 100;
                        $amount_available += ($p->amount - $p->amount_refunded) / 100;
                    } elseif ((int)$p->amount_refunded > 0) {
                        $amount_refunded_payplug += ($p->amount_refunded) / 100;
                        $amount_refundable_payment = ($p->amount - $p->amount_refunded);
                        $amount_available += ($amount_refundable_payment >= 10 ? $amount_refundable_payment / 100 : 0);
                    } else {
                        $amount_available += ($p->amount >= 10 ? $p->amount / 100 : 0);
                    }

                    if ($amount_available > 0) {
                        $display_refund = true;
                    }

                    if ($p->amount_refunded > 0) {
                        $show_menu_refunded = true;
                    }
                }
            } else {
                $payment_list_new[] = array(
                    'id' => null,
                    'status' => $inst_status = $installment->is_active ? $this->payment_status[6] : $this->payment_status[7],
                    'amount' => (int)$schedule->amount / 100,
                    'card_brand' => null,
                    'card_mask' => null,
                    'tds' => null,
                    'card_date' => null,
                    'mode' => null,
                    'authorization' => null,
                    'status_class' => $inst_status = $installment->is_active ? 'pp_success' : 'pp_error',
                    'date' => date('d/m/Y', strtotime($schedule->date)),
                );
            }
        }

        $id_currency = (int)Currency::getIdByIsoCode($installment->currency);
        $show_menu_installment = true;
        $inst_status = $installment->is_active ? $this->l('ongoing') : ($installment->is_fully_paid ? $this->l('paid') : $this->l('suspended'));
        $inst_aborted = !$installment->is_active;
        $ppInstallment = new PPPaymentInstallment($installment->id);
        $instPaymentOne = $ppInstallment->getFirstPayment();
        $inst_can_be_aborted = !($inst_aborted || ($instPaymentOne->isDeferred() && !$instPaymentOne->isPaid()));
        $inst_paid = $installment->is_fully_paid;
        $this->context->smarty->assign(array(
            'inst_id' => $inst_id,
            'inst_status' => $inst_status,
            'inst_aborted' => $inst_aborted,
            'inst_paid' => $inst_paid,
            'payment_list' => $payment_list,
            'payment_list_new' => $payment_list_new,
            'inst_can_be_aborted' => $inst_can_be_aborted,
        ));

        $sandbox = ((int)$installment->is_live == 1 ? false : true);
        $state_addons = ($sandbox ? '' : '_TEST');
        $id_new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND' . $state_addons);

        $this->updatePayplugInstallment($installment);
    } else {
        if (!$pay_id = $this->isTransactionPending((int)$order->id_cart)) {
            $payments = $order->getOrderPaymentCollection();
            if (count($payments) > 1 || !isset($payments[0])) {
                return false;
            } else {
                $pay_id = $payments[0]->transaction_id;
            }
        }

        $sandbox = (bool)Configuration::get('PAYPLUG_SANDBOX_MODE');

        if (!$pay_id || empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
            if ($sandbox) {
                $this->setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
                if (empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
                    $this->setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
                    return false;
                }
            } else {
                $this->setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
                if (empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
                    $this->setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
                    return false;
                }
            }
        }

        $this->updateOrderState($payment);

        $single_payment = $this->buildPaymentDetails($payment);
        $amount_refunded_payplug = ($payment->amount_refunded) / 100;
        $amount_available_payment = ($payment->amount - $payment->amount_refunded);
        $amount_available = ($amount_available_payment >= 10 ? $amount_available_payment / 100 : 0);
        $id_currency = (int)Currency::getIdByIsoCode($payment->currency);
        $sandbox = ((int)$payment->is_live == 1 ? false : true);
        $state_addons = ($sandbox ? '' : '_TEST');

        $id_new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND' . $state_addons);
        $id_pending_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING' . $state_addons);

        $current_state = (int)$order->getCurrentState();

        if ((int)$payment->is_paid == 0) {
            if (isset($payment->failure) && isset($payment->failure->message)) {
                $pay_error = '(' . $payment->failure->message . ')';
            } else {
                $pay_error = '';
            }
            $display_refund = false;
            if ($current_state != 0 && $current_state == $id_pending_order_state) {
                $show_menu_update = true;
            }
        }
        elseif ((((int)$payment->amount_refunded > 0) || $amount_refunded_presta > 0) && (int)$payment->is_refunded != 1) {
            $display_refund = true;
        }
        elseif ((int)$payment->is_refunded == 1) {
            $show_menu_refunded = true;
            $display_refund = false;
        }
        else {
            $display_refund = true;
        }

        $conf = (int)Tools::getValue('conf');
        if (($conf == 30 || $conf == 31) && version_compare(_PS_VERSION_, '1.5', '>=')) {
            $show_popin = true;

            $admin_ajax_url = $this->getAdminAjaxUrl('AdminModules', (int)$params['id_order']);

            $this->html .= '
<a class="pp_admin_ajax_url" href="' . $admin_ajax_url . '"></a>
';
        }

        $pay_status = (int)$payment->is_paid == 1 ? $this->l('PAID') : $this->l('NOT PAID');
        if ((int)$payment->is_refunded == 1) {
            $pay_status = $this->l('REFUNDED');
        }
        elseif ((int)$payment->amount_refunded > 0) {
            $pay_status = $this->l('PARTIALLY REFUNDED');
        }
        $pay_amount = (int)$payment->amount / 100;
        $pay_date = date('d/m/Y H:i', (int)$payment->created_at);
        if ($payment->card->brand != '') {
            $pay_brand = $payment->card->brand;
        } else {
            $pay_brand = $this->l('Unavailable in test mode');
        }
        if ($payment->card->country != '') {
            $pay_brand .= ' ' . $this->l('Card') . ' (' . $payment->card->country . ')';
        }
        if ($payment->card->last4 != '') {
            $pay_card_mask = '**** **** **** ' . $payment->card->last4;
        } else {
            $pay_card_mask = $this->l('Unavailable in test mode');
        }

        // Deferred payment does'nt display 3DS option before capture so we have to consider it null
        if ($payment->is_3ds !== null) {
            $pay_tds = $payment->is_3ds ? $this->l('YES') : $this->l('NO');
            $this->context->smarty->assign(array('pay_tds' => $pay_tds));
        }

        $pay_mode = $payment->is_live ? $this->l('LIVE') : $this->l('TEST');

        if ($payment->card->exp_month === null) {
            $pay_card_date = $this->l('Unavailable in test mode');
        }
        else {
            $pay_card_date = date('m/y',
                strtotime('01.' . $payment->card->exp_month . '.' . $payment->card->exp_year));
        }

        $show_menu_payment = true;

        $this->context->smarty->assign(array(
            'pay_id' => $pay_id,
            'pay_status' => $pay_status,
            'pay_amount' => $pay_amount,
            'pay_date' => $pay_date,
            'pay_brand' => $pay_brand,
            'pay_card_mask' => $pay_card_mask,
            'pay_card_date' => $pay_card_date,
            'pay_error' => $pay_error,
        ));

//Deferred payment does'nt display 3DS option before capture so we have to consider it null
        if ($payment->is_3ds !== null) {
            $pay_tds = $payment->is_3ds ? $this->l('YES') : $this->l('NO');
            $this->context->smarty->assign(array('pay_tds' => $pay_tds));
        }
    }

    $currency = new Currency($id_currency);
    if (!Validate::isLoadedObject($currency)) {
        return false;
    }

    $amount_suggested = (min($amount_refunded_presta, $amount_available) - $amount_refunded_payplug);
    $amount_suggested = number_format((float)$amount_suggested, 2);
    if ($amount_suggested < 0) {
        $amount_suggested = 0;
    }

    if ($display_refund) {
        $this->context->smarty->assign(array(
            'order' => $order,
            'amount_refunded_payplug' => $amount_refunded_payplug,
            'amount_available' => $amount_available,
            'amount_refunded_presta' => $amount_refunded_presta,
            'currency' => $currency,
            'amount_suggested' => $amount_suggested,
            'id_new_order_state' => $id_new_order_state,
        ));
    }
    elseif ($show_menu_refunded) {
        $this->context->smarty->assign(array(
            'amount_refunded_payplug' => $amount_refunded_payplug,
            'currency' => $currency,
        ));
    }
    elseif ($show_menu_update) {
        $this->context->smarty->assign(array(
            'admin_ajax_url' => $admin_ajax_url,
            'order' => $order,
        ));
    }

    $display_single_payment = $show_menu_payment;
    $this->context->smarty->assign(array(
        'logo_url' => __PS_BASE_URI__ . 'modules/payplug/views/img/logo_payplug.png',
        'admin_ajax_url' => $admin_ajax_url,
        'display_single_payment' => $display_single_payment,
        'display_refund' => $display_refund,
        'show_menu_payment' => $show_menu_payment,
        'show_menu_refunded' => $show_menu_refunded,
        'show_menu_update' => $show_menu_update,
        'show_menu_installment' => $show_menu_installment,
        'pay_mode' => $pay_mode,
        'order' => $order,
    ));

    if ($display_single_payment) {
        $this->context->smarty->assign(array(
            'single_payment' => $single_payment,
        ));
    }

    if ($show_popin && $display_refund) {
        $this->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/admin_order_popin.js');
    }


    $this->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/admin_order.js');
    $this->addCSSRC(__PS_BASE_URI__ . 'modules/payplug/views/css/admin_order.css');

    $this->html .= $this->fetchTemplateRC('/views/templates/admin/admin_order.tpl');
    return $this->html;
}
