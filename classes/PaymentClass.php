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

namespace PayPlug\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PaymentClass
{
    private $address;
    private $assign;
    private $cart;
    private $configurationAdapter;
    private $configuration;
    private $constant;
    private $context;
    private $country;
    private $currency;
    private $customer;
    private $dependencies;
    private $language;
    private $logger;
    private $module;
    private $oney;
    private $oney_allowed_iso_codes;
    private $order;
    private $orderHistory;
    private $query;
    private $tools;
    private $validate;
    private $validators;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;

        $this->address = $this->dependencies->getPlugin()->getAddress();
        $this->assign = $this->dependencies->getPlugin()->getAssign();
        $this->cart = $this->dependencies->getPlugin()->getCart();
        $this->configurationAdapter = $this->dependencies->getPlugin()->getConfiguration();
        $this->configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->country = $this->dependencies->getPlugin()->getCountry();
        $this->currency = $this->dependencies->getPlugin()->getCurrency();
        $this->customer = $this->dependencies->getPlugin()->getCustomer();
        $this->language = $this->dependencies->getPlugin()->getLanguage();
        $this->logger = $this->dependencies->getPlugin()->getLogger();
        $this->module = $this->dependencies->getPlugin()->getModule();
        $this->oney = $this->dependencies->getPlugin()->getOney();
        $this->oney_allowed_iso_codes = ['FR', 'IT', 'ES', 'NL'];
        $this->order = $this->dependencies->getPlugin()->getOrder();
        $this->orderHistory = $this->dependencies->getPlugin()->getOrderHistory();
        $this->query = $this->dependencies->getPlugin()->getQueryRepository();
        $this->tools = $this->dependencies->getPlugin()->getTools();
        $this->validate = $this->dependencies->getPlugin()->getValidate();
        $this->validators = $this->dependencies->getValidators();
    }

    /**
     * @description Build the payment details for order detail block
     *
     * @param $payment
     *
     * @return array|Exception
     */
    public function buildPaymentDetails($payment)
    {
        if (!is_object($payment)) {
            $payment = $this->dependencies->apiClass->retrievePayment($payment);
            if (!$payment['result']) {
                return $payment['message'];
            }
            $payment = $payment['resource'];
        }

        $pay_status = $this->getPaymentStatusByPayment($payment);
        $status_class = null;

        switch ($pay_status) {
            case 1: // not paid
            case 5: // refunded
            case 8: // authorized
            case 11: // abandoned
                $status_class = 'pp_warning';

                break;

            case 2: // paid
                $status_class = 'pp_success';

                break;

            case 3: // failed
            case 7: // cancelled
            case 9: // authorization expired
                $status_class = 'pp_error';

                break;

            case 4: // partially refunded
            case 6: // on going
                $status_class = 'pp_neutral';

                break;

            default:
                $status_class = 'pp_other';

                break;
        }

        switch ($pay_status) {
            case 1:
                $status_code = 'not_paid';

                break;

            case 2:
                $status_code = 'paid';

                break;

            case 3:
                $status_code = 'failed';

                break;

            case 4:
                $status_code = 'partially_refunded';

                break;

            case 5:
                $status_code = 'refunded';

                break;

            case 6:
                $status_code = 'on_going';

                break;

            case 7:
                $status_code = 'cancelled';

                break;

            case 8:
                $status_code = 'authorized';

                break;

            case 9:
                $status_code = 'authorization_expired';

                break;

            case 10:
                $status_code = 'oney_pending';

                break;

            case 11:
                $status_code = 'abandoned';

                break;

            default: // none
                $status_code = 'none';

                break;
        }

        $pay_status = $this->getPaymentStatusById($pay_status);

        /*
         * Get card details to order details (views/templates/admin/order/details.tpl)
         * Mask (last4), exp date...
         */
        $card_details = false;
        if (isset($payment->card->last4) && (!empty($payment->card->last4))) {
            $card_details = $this->dependencies
                ->getPlugin()
                ->getCardAction()
                ->renderOrderDetail($payment);
        }

        // Card brand
        $card_brand = null;
        if ($card_details
            && isset($card_details['brand'])
            && !empty($card_details['brand'])
            && ('none' !== $card_details['brand'])) {
            $card_brand = $this
                ->dependencies
                ->l('payplug.adminAjaxController.card', 'paymentclass') . ' ' . $card_details['brand'];
        }

        // Card Country
        $card_country = null;
        if ($card_details
            && isset($card_details['country'])
            && ('none' !== $card_details['country'])) {
            $card_country = $card_details['country'];
            $card_brand .= ' (' . $card_details['country'] . ')';
        }

        // Card mask
        $card_mask = null;
        if ($card_details && isset($card_details['last4']) && !empty($card_details['last4'])) {
            $card_mask = '**** **** **** ' . $card_details['last4'];
        }

        // Card exp. date
        $card_date = null;
        if ($card_details && (isset($card_details['exp_month']) && !empty($card_details['exp_month']))
            && (isset($card_details['exp_year']) && !empty($card_details['exp_year']))) {
            $card_date = $card_details['exp_month'] . '/' . $card_details['exp_year'];
        }

        $payment_details = [
            'id' => $payment->id,
            'status' => $pay_status,
            'status_code' => $status_code,
            'status_class' => $status_class,
            'amount' => (int) $payment->amount / 100,
            'refunded' => (int) $payment->amount_refunded / 100,
            'card_brand' => $card_brand,
            'card_mask' => $card_mask,
            'card_date' => $card_date,
            'card_country' => $card_country,
            'mode' => ($payment->is_live)
                ? $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('payplug.buildPaymentDetails.live', 'paymentclass')
                : $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('payplug.buildPaymentDetails.test', 'paymentclass'),
            'paid' => (bool) $payment->is_paid,
        ];

        // Deferred payment does'nt display 3DS option before capture so we have to consider it null
        if (null !== $payment->is_3ds) {
            $payment_details['tds'] = ($payment->is_3ds)
                ? $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('payplug.buildPaymentDetails.yes', 'paymentclass')
                : $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('payplug.buildPaymentDetails.no', 'paymentclass');
        }

        $is_oney = false;
        $is_amex = false;
        $is_bancontact = false;
        if (isset($payment->payment_method, $payment->payment_method['type'])) {
            switch ($payment->payment_method['type']) {
                case 'oney_x3_with_fees':
                    $is_oney = true;
                    $payment_details['type'] = $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('payplug.buildPaymentDetails.oneyX3WithFees', 'paymentclass');

                    break;

                case 'oney_x4_with_fees':
                    $is_oney = true;
                    $payment_details['type'] = $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('payplug.buildPaymentDetails.oneyX4WithFees', 'paymentclass');

                    break;

                case 'oney_x3_without_fees':
                    $is_oney = true;
                    $payment_details['type'] = $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('payplug.buildPaymentDetails.oneyX3WithoutFees', 'paymentclass');

                    break;

                case 'oney_x4_without_fees':
                    $is_oney = true;
                    $payment_details['type'] = $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('payplug.buildPaymentDetails.oneyX4WithoutFees', 'paymentclass');

                    break;

                case 'bancontact':
                    $is_bancontact = true;
                    $payment_details['type'] = $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('payplug.buildPaymentDetails.bancontact', 'paymentclass');

                    break;

                case 'apple_pay':
                    $payment_details['type'] = $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('payplug.buildPaymentDetails.applepay', 'paymentclass');

                    break;

                case 'american_express':
                    $is_amex = true;
                    $payment_details['type'] = $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('payplug.buildPaymentDetails.amex', 'paymentclass');

                    break;

                default:
                    $payment_details['type'] = $payment->payment_method['type'];
            }
            $payment_details['type_code'] = $payment->payment_method['type'];
        }

        $is_paid = $this->validators['payment']->isPaid($payment)['result'];
        $can_be_captured = $this->validators['payment']->isPayment($payment)['result']
            && !$this->validators['payment']->isFailed($payment)['result']
            && !$is_paid
            && $this->validators['payment']->isDeferred($payment)['result']
            && !$this->validators['payment']->isExpired($payment)['result'];
        $payment_details['can_be_captured'] = $can_be_captured;

        if (null !== $payment->authorization && !$is_oney) {
            $payment_details['authorization'] = true;
            if ($is_paid) {
                $payment_details['date'] = date('d/m/Y', $payment->paid_at);
                if (!isset($payment_details['type'])) {
                    $payment_details['status_message'] = '(' . $this
                        ->dependencies
                        ->l('payplug.buildPaymentDetails.deferred', 'paymentclass') . ')';
                }
            } else {
                $expiration = date('d/m/Y', $payment->authorization->expires_at);
                if ($can_be_captured) {
                    $payment_details['status_message'] = sprintf(
                        '(' . $this
                            ->dependencies
                            ->l('payplug.buildPaymentDetails.captureAuthorizedBefore', 'paymentclass') . ')',
                        $expiration
                    );
                    $payment_details['date'] = date('d/m/Y', $payment->authorization->authorized_at);
                    $payment_details['date_expiration'] = $expiration;
                    $payment_details['expiration_display'] = sprintf(
                        $this->dependencies
                            ->getPlugin()
                            ->getTranslationClass()
                            ->l('payplug.buildPaymentDetails.captureAuthorizedBeforeWarning', 'paymentclass'),
                        $expiration
                    );
                } elseif (isset($payment->authorization->authorized_at)
                    && null != $payment->authorization->authorized_at
                ) {
                    $payment_details['date'] = date('d/m/Y', $payment->authorization->authorized_at);
                }
            }
        } else {
            $payment_details['authorization'] = false;
            $payment_details['date'] = date('d/m/Y', $payment->created_at);
        }

        if ($this->validators['payment']->isFailed($payment)['result']) {
            $payment_details['error'] = '(' . $payment->failure->message . ')';
        }

        if ($is_oney) {
            unset($payment_details['card_brand'], $payment_details['card_mask'], $payment_details['card_date']);
        }
        if ($is_bancontact || $is_amex) {
            unset($payment_details['tds'], $payment_details['card_brand']);
        }

        return $payment_details;
    }

    /**
     * @description Display payment errors messages template
     *
     * @param array $errors
     *
     * @return mixed
     */
    public function displayPaymentErrors($errors = [])
    {
        if (empty($errors)) {
            return false;
        }

        $formated = [];
        $with_msg_button = false;

        foreach ($errors as $error) {
            if (false !== strpos($error, 'oney_required_field')) {
                $this->assign->assign(['is_popin_tpl' => true]);
                $fields = $this->oney->getOneyRequiredFields();
                $this->assign->assign([
                    'oney_type' => str_replace('oney_required_field_', '', $error),
                    'oney_required_fields' => $fields,
                ]);
                $formated[] = [
                    'type' => 'template',
                    'value' => 'oney/required.tpl',
                ];
            } else {
                $with_msg_button = true;
                $formated[] = [
                    'type' => 'string',
                    'value' => $error,
                ];
            }
        }

        $this->assign->assign([
            'is_error_message' => true,
            'messages' => $formated,
            'with_msg_button' => $with_msg_button,
        ]);

        return $this->dependencies->configClass->fetchTemplate('_partials/messages.tpl');
    }

    /**
     * @description Get the payment method for a given payment card
     * @unused
     *
     * @param string $card
     *
     * @return object PayPlugPaymentStandard|PayPlugPaymentInstallment|PayPlugPaymentOneClick|PayPlugPaymentOney
     */
    public function getCurrentPaymentMethod($card = null)
    {
        $card = null != $card ? $card : $this->tools->tool('getValue', 'pc', null);

        // check if is Installment
        if ($this->tools->tool('getValue', 'io') || 'oney' == $this->tools->tool('getValue', 'type')) {
            $payment_method = 'PayPlugPaymentOney';
        } elseif ($this->tools->tool('getValue', 'i') || 'installment' == $this->tools->tool('getValue', 'type')) {
            $payment_method = 'PayPlugPaymentInstallment';
        } elseif ((null != $card && 'new_card' != $card) || 'oneclick' == $this->tools->tool('getValue', 'type')) {
            $payment_method = 'PayPlugPaymentOneClick';
        } elseif ('standard' == $this->tools->tool('getValue', 'type')) {
            $payment_method = 'PayPlugPaymentStandard';
        } else {
            $payment_method = 'PayPlugPaymentStandard';
        }

        return $payment_method;
    }

    /**
     * @description Get the status name for a given status id
     *
     * @param $id_status
     *
     * @return mixed
     */
    public function getPaymentStatusById($id_status)
    {
        $paymentStatus = $this->dependencies->configClass->getPaymentStatus();

        return $paymentStatus[$id_status];
    }

    /**
     * @description Get the status for a given payment
     *
     * @param $payment
     *
     * @return int
     */
    public function getPaymentStatusByPayment($payment)
    {
        /*
            1 => 'not paid',
            2 => 'paid',
            3 => 'failed',
            4 => 'partially refunded',
            5 => 'refunded',
            6 => 'on going',
            7 => 'cancelled',
            8 => 'authorized',
            9 => 'authorization expired',
            10 => 'oney pending',
            11 => 'abandoned',
        */
        if (!is_object($payment)) {
            $payment = $this->dependencies->apiClass->retrievePayment($payment);
            if (!$payment['result']) {
                return false;
            }
            $payment = $payment['resource'];
        }

        if (null !== $payment->installment_plan_id) {
            $installment = $this->dependencies->apiClass->retrieveInstallment($payment->installment_plan_id);
            if (!$installment['result']) {
                return false;
            }

            $installment = $installment['resource'];
        } else {
            $installment = null;
        }

        $pay_status = 1; // not paid
        if (1 == (int) $payment->is_paid) {
            $pay_status = 2; // paid
        } elseif (isset($payment->payment_method, $payment->payment_method['is_pending'])
            && 1 == (int) $payment->payment_method['is_pending']
        ) {
            $pay_status = 10; // oney pending
        } elseif ($this->validators['payment']->isFailed($payment)['result'] && 9 != $pay_status) {
            if ('aborted' == $payment->failure->code) {
                $pay_status = 7; // cancelled
            } elseif ('timeout' == $payment->failure->code) {
                $pay_status = 11; // abandoned
            } else {
                $pay_status = 3; // failed
            }
        } elseif (null !== $payment->authorization && ($payment->authorization->expires_at - time()) > 0) {
            $pay_status = 8; // authorized
        } elseif (null !== $payment->authorization && ($payment->authorization->expires_at - time()) <= 0) {
            $pay_status = 9; // authorization expired
        } elseif (null !== $payment->installment_plan_id && 1 == (int) $installment->is_active) {
            $pay_status = 6; // ongoing
        }
        if (1 == (int) $payment->is_refunded) {
            $pay_status = 5; // refunded
        } elseif ((int) $payment->amount_refunded > 0) {
            $pay_status = 4; // partially refunded
        }

        return $pay_status;
    }

    /**
     * @description update payment ressource
     *
     * @param $pay_id
     * @param $order_id
     */
    public function updatePayment($pay_id, $order_id)
    {
        $payment = $this->dependencies->apiClass->retrievePayment($pay_id);

        if (!$payment['result']) {
            exit(json_encode([
                'data' => $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('payplug.adminPayplugController.errorOccurred', 'paymentclass'),
                'status' => 'error',
            ]));
        }
        $payment = $payment['resource'];

        $state_addons = ($payment->is_live ? '' : '_test');
        if ((bool) $payment->is_paid) {
            $new_state = (int) $this->configuration->getValue('order_state_paid' . $state_addons);
        } elseif ((bool) $payment->is_live) {
            $new_state = (int) $this->configuration->getValue('order_state_error');
        } else {
            $new_state = (int) $this->configuration->getValue('order_state_error_test');
        }

        $order = $this->order->get((int) $order_id);

        if ($this->validate->validate('isLoadedObject', $order)) {
            $current_state = (int) $order->getCurrentState();
            if (0 != $current_state && $current_state != $new_state) {
                $history = $this->orderHistory->get();
                $history->id_order = (int) $order->id;
                $history->changeIdOrderState($new_state, (int) $order->id, true);
                $history->addWithemail();
                $this->logger->addLog('Change order state to ' . $new_state, 'notice');
            }
        }

        exit(json_encode([
            'message' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.adminPayplugController.orderUpdated', 'paymentclass'),
            'reload' => true,
        ]));
    }
}
