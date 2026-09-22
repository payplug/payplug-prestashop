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

namespace PayPlug\src\actions;

use PayPlug\classes\DependenciesClass;
use PayPlug\src\application\adapter\PrestashopAdapter17;
use PayPlug\src\utilities\services\Props;
use PayPlug\src\utilities\traits\ServiceGetter;
use PayplugUnifiedCore\DataValues\PaymentOutcome;
use PayplugUnifiedCore\Dto\BrowserDto;
use PayplugUnifiedCore\Dto\CommonFieldsDto;
use PayplugUnifiedCore\Dto\CustomerDto;
use PayplugUnifiedCore\Dto\HostedFieldDto;
use PayplugUnifiedCore\Utilities\Helpers\ExecCodeMapper;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OperationAction
{
    use ServiceGetter;

    private const FACTORY_SERVICE = 'payplug.utilities.service.unified_api_payment_service_factory';

    public $dependencies;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    public function dispatchAction($method = '', $parameters = [])
    {
        if (!is_string($method) || !$method) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $method must be a non empty string.',
            ];
        }

        if (!is_array($parameters)) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $parameters must be a valid array.',
            ];
        }

        $method_name = $method . 'Action';
        if (!is_callable([$this, $method_name])) {
            return [
                'result' => false,
                'message' => 'Method not found in object OperationAction',
            ];
        }

        try {
            return $this->{$method_name}($parameters);
        } catch (\Exception $exception) {
            $this->dependencies->getPlugin()->getLogger()->addLog(
                'OperationAction::dispatchAction - Exception thrown: ' . $exception->getMessage(),
                'error'
            );

            return [
                'result' => false,
                'message' => 'Exception thrown: ' . $exception->getMessage(),
            ];
        }
    }

    public function createAction($params = [])
    {
        $hf_token = isset($params['hfToken']) && is_string($params['hfToken']) ? $params['hfToken'] : '';
        $selected_brand = isset($params['selectedBrand']) && is_string($params['selectedBrand']) ? $params['selectedBrand'] : '';

        if (!$hf_token || !$selected_brand) {
            return ['result' => false, 'message' => 'Missing hfToken or selectedBrand'];
        }

        if (!in_array(strtolower($selected_brand), PrestashopAdapter17::HOSTED_FIELDS_ACCEPTED_BRANDS, true)) {
            return [
                'result' => false,
                'message' => $this->dependencies->l('This card brand is not supported.', 'uhf'),
            ];
        }

        if (!$this->dependencies->configClass->isValidFeature('feature_hosted_fields')) {
            return ['result' => false, 'message' => 'This payment method is not available'];
        }

        $id_cart = isset($params['id_cart']) ? (int) $params['id_cart'] : 0;
        if ($id_cart <= 0) {
            return ['result' => false, 'message' => 'Missing or invalid id_cart'];
        }

        $plugin = $this->dependencies->getPlugin();
        $context = $this->getContext();
        if (!$context || !isset($context->cart)) {
            return $this->errorResult('Invalid cart');
        }

        if ($id_cart !== (int) $context->cart->id) {
            return ['result' => false, 'message' => 'Invalid cart'];
        }

        $cart = $plugin->getCart()->get($id_cart);
        if (!$cart || !(int) $cart->id) {
            return ['result' => false, 'message' => 'Invalid cart'];
        }

        $currency = $plugin->getCurrency()->getCurrency((int) $cart->id_currency);
        $prestashop_adapter = $this->dependencies->loadAdapterPresta();
        if (!$currency
            || !isset($currency->iso_code)
            || 'EUR' === strtoupper((string) $currency->iso_code)
            || !$prestashop_adapter
            || !$prestashop_adapter->isHostedFieldsIdentifierConfigured($currency->iso_code)) {
            return ['result' => false, 'message' => 'This payment method is not available'];
        }

        $order_adapter = $plugin->getOrder();
        $order = $order_adapter->get((int) $order_adapter->getIdByCartId((int) $cart->id));
        $order_exists = $this->dependencies->getValidators()['order']->isCreated($order, (int) $cart->id);
        if ($order_exists['result']) {
            return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
        }

        $factory = $this->getService(self::FACTORY_SERVICE);
        $logger = $factory->createLogger();
        $lock = $factory->createLock();
        $lock_key = 'uhf_cart:' . $cart->id;

        if (!$lock->acquire($lock_key, 30)) {
            return [
                'result' => false,
                'message' => $this->dependencies->l('Please wait, your previous request is still being processed.', 'uhf'),
            ];
        }

        try {
            $amount_cents = (int) $this->dependencies->getHelpers()['amount']->convertAmount((float) $cart->getOrderTotal(true, \Cart::BOTH));
            $return_url = $context->link->getModuleLink(
                $this->dependencies->name,
                'unified',
                ['action' => 'return', 'id_cart' => (int) $cart->id],
                true
            );

            $common = new CommonFieldsDto(
                $prestashop_adapter->getHostedFieldsIdentifier($currency->iso_code),
                $amount_cents,
                (string) $currency->iso_code,
                (string) $cart->id,
                null
            );
            $common->successUrl = $return_url;
            $common->cancelUrl = $return_url;
            $common->description = 'Payment with unified hosted fields for cart ' . (string) $cart->id;

            $props = new Props();
            $browser = new BrowserDto(
                (string) $plugin->getTools()->tool('getRemoteAddr'),
                (string) $props->getServerProp('HTTP_REFERER'),
                (string) $props->getServerProp('HTTP_USER_AGENT')
            );

            $customer_object = $plugin->getCustomer()->get((int) $cart->id_customer);
            $customer = new CustomerDto(
                (string) $cart->id_customer,
                isset($customer_object->email) ? (string) $customer_object->email : ''
            );

            $payment_method_details = ['selectedBrand' => strtolower($selected_brand)];
            $payment_method = ['details' => $payment_method_details];
            $recurring_mode = null;
            $save_card = !empty($params['save_card']);
            $cardholder = $this->getCardholder($params);

            if ($save_card && '' !== $cardholder) {
                $payment_method_details['fullName'] = $cardholder;
                $payment_method = [
                    'details' => $payment_method_details,
                    'saveFutureUsage' => true,
                ];
                $recurring_mode = 'SUBSCRIPTION';
            }

            $output = $factory->create()->createPayment(new HostedFieldDto(
                $common,
                $hf_token,
                $recurring_mode,
                $browser,
                $customer,
                $payment_method
            ));
        } catch (\Exception $exception) {
            $logger->error('OperationAction::createAction - createPayment failed: ' . $exception->getMessage());
            $lock->release($lock_key);

            return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
        }

        $decoded_body = json_decode($output->body, true);
        $operation_id = $this->extractOperationId($decoded_body);

        if ('' === $operation_id) {
            $logger->error('OperationAction::createAction - missing operation identifier in payment creation response');
            $lock->release($lock_key);

            return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
        }

        if ($output->redirectHtml || $output->redirectUrl) {
            $token_cache = $factory->createTokenCache();
            $token_cache->set('uhf_pending_operation:' . $cart->id, $operation_id, 600);

            if ($output->redirectHtml) {
                $token_cache->set('uhf_challenge_html:' . $cart->id, (string) $output->redirectHtml, 600);
                $redirect_url = $context->link->getModuleLink(
                    $this->dependencies->name,
                    'unified',
                    ['action' => 'challenge', 'id_cart' => (int) $cart->id],
                    true
                );
            } else {
                $redirect_url = $output->redirectUrl;
            }

            $lock->release($lock_key);

            return $this->withReturnUrl([
                'result' => true,
                'redirect_url' => $redirect_url,
            ]);
        }

        $exec_code = is_array($decoded_body) && isset($decoded_body['execCode'])
            ? (string) $decoded_body['execCode']
            : '0000_MISSING';
        $outcome = ExecCodeMapper::toPaymentOutcome($exec_code);
        $result = UnifiedOrderCreator::createFromOutcome(
            $this->dependencies,
            (int) $cart->id,
            $operation_id,
            $exec_code,
            $outcome,
            $amount_cents
        );

        $lock->release($lock_key);

        if (!$result['result']) {
            $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([
                $this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'),
            ]);
        }

        return $this->withReturnUrl($result);
    }

    public function challengeAction($params = [])
    {
        $id_cart = isset($params['id_cart']) ? (int) $params['id_cart'] : 0;
        if ($id_cart <= 0) {
            return ['result' => false];
        }

        $factory = $this->getService(self::FACTORY_SERVICE);
        $html = $factory->createTokenCache()->get('uhf_challenge_html:' . $id_cart);

        if (null === $html || '' === $html) {
            $factory->createLogger()->error('OperationAction::challengeAction - No pending 3DS challenge for cart ' . $id_cart);

            return ['result' => false];
        }

        return ['result' => true, 'html' => $html];
    }

    public function returnAction($params = [])
    {
        $id_cart = isset($params['id_cart']) ? (int) $params['id_cart'] : 0;
        if ($id_cart <= 0) {
            return $this->withReturnUrl([
                'result' => false,
                'redirect_url' => UnifiedOrderCreator::errorUrl($this->dependencies),
            ]);
        }

        $factory = $this->getService(self::FACTORY_SERVICE);
        $logger = $factory->createLogger();
        $token_cache = $factory->createTokenCache();
        $operation_id = $token_cache->get('uhf_pending_operation:' . $id_cart);

        if (!$operation_id) {
            $logger->error('OperationAction::returnAction - No pending UPC operation for cart ' . $id_cart);

            return $this->withReturnUrl([
                'result' => false,
                'redirect_url' => UnifiedOrderCreator::errorUrl($this->dependencies),
            ]);
        }

        try {
            $response = $factory->create()->getOperation($operation_id);
        } catch (\Exception $exception) {
            $logger->error('OperationAction::returnAction - getOperation failed: ' . $exception->getMessage());

            return $this->withReturnUrl([
                'result' => false,
                'redirect_url' => UnifiedOrderCreator::errorUrl($this->dependencies),
            ]);
        }

        $decoded = json_decode($response['body'], true);
        $exec_code = is_array($decoded) && isset($decoded['execCode']) ? (string) $decoded['execCode'] : '0000_MISSING';
        $outcome = ExecCodeMapper::toPaymentOutcome($exec_code);
        $amount = is_array($decoded) && isset($decoded['amount']) ? (int) $decoded['amount'] : null;

        if (null === $amount) {
            $outcome = PaymentOutcome::FAILED;
            $logger->error('OperationAction::returnAction - missing amount field in getOperation response for operation ' . $operation_id);
            $amount = 0;
        }

        $result = UnifiedOrderCreator::createFromOutcome(
            $this->dependencies,
            $id_cart,
            $operation_id,
            $exec_code,
            $outcome,
            $amount
        );

        if (!$result['result']) {
            $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([
                $this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'),
            ]);
        }

        return $this->withReturnUrl($result);
    }

    private function errorResult($message)
    {
        return [
            'result' => false,
            'message' => $message,
        ];
    }

    private function extractOperationId($decoded_body)
    {
        if (!is_array($decoded_body)) {
            return '';
        }

        if (isset($decoded_body['operationIds'][0]) && is_string($decoded_body['operationIds'][0])) {
            return $decoded_body['operationIds'][0];
        }

        if (isset($decoded_body['id']) && is_string($decoded_body['id'])) {
            return $decoded_body['id'];
        }

        return '';
    }

    private function getCardholder(array $params)
    {
        $cardholder = '';
        if (isset($params['cardholder']) && is_string($params['cardholder'])) {
            $cardholder = $params['cardholder'];
        } elseif (isset($params['cardholderName']) && is_string($params['cardholderName'])) {
            $cardholder = $params['cardholderName'];
        }

        return trim((string) preg_replace('/[\r\n]+/', ' ', $cardholder));
    }

    private function getContext()
    {
        $context_adapter = $this->dependencies->getPlugin()->getContext();

        return is_object($context_adapter) && method_exists($context_adapter, 'get')
            ? $context_adapter->get()
            : $context_adapter;
    }

    private function withReturnUrl(array $result)
    {
        if (isset($result['redirect_url']) && !isset($result['return_url'])) {
            $result['return_url'] = $result['redirect_url'];
        }

        return $result;
    }
}
