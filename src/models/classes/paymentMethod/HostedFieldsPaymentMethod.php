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

class HostedFieldsPaymentMethod extends PaymentMethod
{
    private $api_secret;
    private $api_key;
    private $identifier;
    private $api_key_secret;

    public function __construct($dependencies, $api_secret, $api_key, $identifier, $api_key_secret)
    {
        parent::__construct($dependencies);
        $this->name = 'hosted_fields';
        $this->order_name = 'hosted_fields';

        $this->set_api_key($api_key);
        $this->set_api_key_secret($api_key_secret);
        $this->set_api_secret($api_secret);
        $this->set_identifier($identifier);
    }

    public function refundTransaction($resource_id, $amount, $id_order)
    {
        $multi_account = json_decode($this->dependencies->getPlugin()->getConfigurationClass()->getValue('multi_account'), true);
        $context = $this->dependencies->getPlugin()->getContext()->get();
        $currency = $context->currency;
        $data = [
            'method' => 'refund',
            'params' => [
                'IDENTIFIER' => $multi_account['identifier_' . strtolower($currency->iso_code)],
                'OPERATIONTYPE' => 'refund',
                'AMOUNT' => (string) $amount,
                'ORDERID' => (string) $id_order,
                'DESCRIPTION' => 'Refund for order #' . $id_order,
                'TRANSACTIONID' => $resource_id,
                'VERSION' => 3.0,
            ],
            'id' => $resource_id,
        ];
        $data['params']['HASH'] = $this->buildHashContent($data['params'], true);

        return $data;
    }

    /**
     * @description calculate hash for hosted field resource
     *
     * @param array $params
     * @param bool $getTransaction
     *
     * @return string
     */
    public function buildHashContent($params, $getTransaction = false)
    {
        $this->setParameters();
        if (empty($params) || !is_array($params)) {
            $this->logger->addLog('PaymentMethod::buildHashContent() - Invalid prams $params must be a non empty array.');
        }
        $multi_account = json_decode($this->configuration->getValue('multi_account'), true);
        $key = $getTransaction ? $multi_account['account_key'] : $multi_account['api_key'];
        ksort($params);
        $string = '';
        foreach ($params as $k => $v) {
            $string .= $k . '=' . $v . $key;
        }

        return hash('sha256', $key . $string);
    }

    /**
     * @return mixed
     */
    public function get_api_key()
    {
        return $this->api_key;
    }

    /**
     * @return mixed
     */
    public function get_api_key_secret()
    {
        return $this->api_key_secret;
    }

    /**
     * @return self
     */
    public function get_api_secret()
    {
        return $this->api_secret;
    }

    /**
     * @return mixed
     */
    public function get_identifier()
    {
        return $this->identifier;
    }

    /**
     * @param mixed $api_key
     */
    private function set_api_key($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
     * @param mixed $api_key_secret
     */
    private function set_api_key_secret($api_key_secret)
    {
        $this->api_key_secret = $api_key_secret;
    }

    /**
     * @param mixed $api_secret
     */
    private function set_api_secret($api_secret)
    {
        $this->api_secret = $api_secret;
    }

    /**
     * @param mixed $identifier
     */
    private function set_identifier($identifier)
    {
        $this->identifier = $identifier;
    }
}
