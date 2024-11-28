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

namespace PayPlug\src\models\classes\paymentMethod;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OneClickPaymentMethod extends PaymentMethod
{
    public function __construct($dependencies)
    {
        parent::__construct($dependencies);
        $this->name = 'one_click';
        $this->cancellable = false;
    }

    /**
     * @description Get option for given configuration
     *
     * @param array $current_configuration
     *
     * @return array
     */
    public function getOption($current_configuration = [])
    {
        return [];
    }

    /**
     * @description Get order tab for given resource to create the order
     * @todo: add coverage to this method
     *
     * @param array $retrieve
     *
     * @return array
     */
    public function getOrderTab($retrieve = null)
    {
        $this->setParameters();

        $resource = $retrieve['resource'];
        if (!is_object($resource) || !$resource) {
            $this->logger->addLog('OneClickPaymentMethod::getOrderTab() - Invalid argument given, $resource must be a non null object.');

            return [];
        }

        $order_tab = parent::getOrderTab($retrieve);

        if ($this->dependencies->getValidators()['payment']->isDeferred($resource)['result']) {
            $state_addons = $resource->is_live ? '' : '_test';
            $order_tab['order_state'] = $this->configuration->getValue('order_state_auth' . $state_addons);
        }

        return $order_tab;
    }

    // todo: add coverage to this method
    public function getPaymentTab()
    {
        $payment_tab = parent::getPaymentTab();

        if (empty($payment_tab)) {
            return $payment_tab;
        }

        $payment_tab['initiator'] = 'PAYER';
        $id_cart = $this->tools->tool('getValue', 'pc', 'new_card');

        // Check if getted card correspond to the current customer
        $card = $this->dependencies
            ->getPlugin()
            ->getCardRepository()
            ->getEntity((int) $id_cart);

        if (empty($card)) {
            return [];
        }

        if ((int) $card['id_customer'] != (int) $this->context->customer->id) {
            return [];
        }

        $payment_methods = $this->configuration->getValue('payment_methods');
        $payment_methods = json_decode($payment_methods, true);

        // Update if deferred payment is enable
        if (isset($payment_methods['deferred']) && $payment_methods['deferred']) {
            $payment_tab['authorized_amount'] = $payment_tab['amount'];
            unset($payment_tab['amount']);
        }

        $payment_tab['payment_method'] = $card['id_card'];

        return $payment_tab;
    }

    // todo: add coverage to this method
    public function getReturnUrl()
    {
        $this->setParameters();

        $return = parent::getReturnUrl();

        if (empty($return)) {
            return $return;
        }

        $retrieve = $this->retrieve($return['resource_stored']['resource_id']);
        $redirect = $retrieve['resource']->is_paid;

        $payment_methods = $this->configuration->getValue('payment_methods');
        $payment_methods = json_decode($payment_methods, true);

        // Update if deferred payment is enable
        if (!$redirect && isset($payment_methods['deferred']) && $payment_methods['deferred']) {
            $redirect = (bool) isset($retrieve['resource']->authorization)
                && $retrieve['resource']->authorization
                && $retrieve['resource']->authorization->authorized_at;
        }

        $return['embedded'] = !$redirect;
        $return['return_url'] = $redirect
            ? $retrieve['resource']->hosted_payment->return_url
            : $retrieve['resource']->hosted_payment->payment_url;

        unset($return['resource_stored']);

        return $return;
    }

    /**
     * @description Get the resource detail
     *
     * todo: add coverage to this method
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function getResourceDetail($resource_id = '')
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('OneClickPaymentMethod::getResourceDetail() - Invalid argument given, $resource_id must be a non empty string.');

            return [];
        }

        $retrieve = $this->retrieve($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('OneClickPaymentMethod::getResourceDetail() - Payment resource can\'t be retrieved for given resource id.');

            return [];
        }
        $resource = $retrieve['resource'];

        $resource_details = parent::getResourceDetail($resource_id);

        if (!$this->dependencies->getValidators()['payment']->isDeferred($resource)['result']) {
            return $resource_details;
        }

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getOrderTranslations();

        $can_be_captured = !$this->dependencies->getValidators()['payment']->isFailed($resource)['result']
            && !(bool) $resource->is_paid
            && !$this->dependencies->getValidators()['payment']->isExpired($resource)['result'];

        $resource_details['can_be_captured'] = $can_be_captured;
        $resource_details['authorization'] = true;

        if ((bool) $resource->is_paid) {
            $resource_details['date'] = date('d/m/Y', $resource->paid_at);
            $resource_details['status_message'] = '(' . $translation['detail']['capture']['deferred'] . ')';
        } else {
            $expiration = date('d/m/Y', $resource->authorization->expires_at);
            if ($can_be_captured) {
                $resource_details['status_message'] = sprintf(
                    '(' . $translation['detail']['capture']['expiration'] . ')',
                    $expiration
                );
                $resource_details['date'] = date('d/m/Y', $resource->authorization->authorized_at);
                $resource_details['date_expiration'] = $expiration;
                $resource_details['expiration_display'] = sprintf(
                    $translation['detail']['capture']['warning'],
                    $expiration
                );
            } elseif (isset($resource->authorization->authorized_at) && (bool) $resource->authorization->authorized_at) {
                $resource_details['date'] = date('d/m/Y', $resource->authorization->authorized_at);
            }
        }

        return $resource_details;
    }

    // todo: add coverage to this method
    public function getPaymentStatus($resource = null)
    {
        $this->setParameters();

        if (!is_object($resource) || !$resource) {
            $this->logger->addLog('OneClickPaymentMethod::getPaymentStatus() - Invalid argument given, $resource must be a non null object.');

            return [];
        }

        $status = parent::getPaymentStatus($resource);
        if (in_array($status['code'], ['paid', 'abandoned', 'failed', 'refunded', 'partially_refunded'])) {
            return $status;
        }

        if ((bool) $resource->authorization && ($resource->authorization->expires_at - time()) > 0) {
            return [
                'id_status' => 8,
                'code' => 'authorized',
            ];
        }

        if ((bool) $resource->authorization && ($resource->authorization->expires_at - time()) <= 0) {
            return [
                'id_status' => 9,
                'code' => 'authorization_expired',
            ];
        }

        return $status;
    }

    /**
     * @description Get payment option
     *
     * @param array $payment_options
     *
     * @return array
     */
    protected function getPaymentOption($payment_options = [])
    {
        if (!is_array($payment_options)) {
            return [];
        }

        $this->setParameters();

        $cards = $this->dependencies
            ->getPlugin()
            ->getCardAction()
            ->renderList(true);

        if (empty($cards)) {
            return $payment_options;
        }

        $payment_options = parent::getPaymentOption($payment_options);

        if (!isset($payment_options[$this->name])) {
            return $payment_options;
        }

        $default_one_click_option = $payment_options[$this->name];

        foreach ($cards as $card) {
            $payment_key = 'one_click_' . $card['id_payplug_card'];
            $brand = $this->getCardBrand($card);

            $payment_options[$payment_key] = $default_one_click_option;
            $payment_options[$payment_key]['inputs']['pc']['value'] = (int) $card['id_payplug_card'];
            $payment_options[$payment_key]['logo'] = 'none' != $card['brand']
                ? $this->img_path . 'svg/checkout/standard/'
                . $this->dependencies->getPlugin()->getTools()->tool('strtolower', $card['brand']) . '.svg'
                : '';

            $payment_options[$payment_key]['callToActionText'] = sprintf(
                $payment_options[$payment_key]['callToActionText'],
                $brand,
                $card['last4'],
                $card['expiry_date']
            );
            $payment_options[$payment_key]['action'] = $this->context->link->getModuleLink($this->dependencies->name, 'dispatcher', ['def' => isset($options['deferred']) ? (int) $options['deferred'] : 0], true);
        }

        unset($payment_options[$this->name]);

        return $payment_options;
    }

    /**
     * @description Get card brand from given array
     *
     * @param array $card
     *
     * @return mixed
     */
    protected function getCardBrand($card = [])
    {
        $default = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->l('payplug.getPaymentOptions.card', 'oneclickpaymentmethod');

        if (!is_array($card) || empty($card)) {
            return $default;
        }

        return 'none' != $card['brand']
            ? $this->dependencies->getPlugin()->getTools()->tool('ucfirst', $card['brand'])
            : $default;
    }
}
