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

namespace PayPlug\src\models\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Configuration
{
    public $configurations = [
        'allow_save_card' => [
            'type' => 'integer',
            'name' => 'ALLOW_SAVE_CARD',
            'defaultValue' => 0,
            'setConf' => 1,
        ],
        'amounts' => [
            'type' => 'string',
            'name' => 'AMOUNTS',
            'defaultValue' => '{}',
            'setConf' => 1,
        ],
        'applepay_carriers' => [
            'type' => 'string',
            'name' => 'APPLEPAY_CARRIERS',
            'defaultValue' => '{}',
            'setConf' => 1,
        ],
        'applepay_display' => [
            'type' => 'string',
            'name' => 'APPLEPAY_DISPLAY',
            'defaultValue' => '{"cart":true,"checkout":true,"product":false}',
            'setConf' => 1,
        ],
        'bancontact_country' => [
            'type' => 'integer',
            'name' => 'BANCONTACT_COUNTRY',
            'defaultValue' => 0,
            'setConf' => 1,
        ],
        'client_data' => [
            'type' => 'string',
            'name' => 'CLIENT_DATA',
            'defaultValue' => '{}',
            'setConf' => 1,
        ],
        'company_id' => [
            'type' => 'string',
            'name' => 'COMPANY_ID',
            'defaultValue' => null,
            'setConf' => 1,
        ],
        'company_id_test' => [
            'type' => 'string',
            'name' => 'COMPANY_ID_TEST',
            'defaultValue' => null,
            'setConf' => 1,
        ],
        'company_iso' => [
            'type' => 'string',
            'name' => 'COMPANY_ISO',
            'defaultValue' => '',
            'setConf' => 1,
        ],
        'countries' => [
            'type' => 'string',
            'name' => 'COUNTRIES',
            'defaultValue' => '{}',
            'setConf' => 1,
        ],
        'currencies' => [
            'type' => 'string',
            'name' => 'CURRENCIES',
            'defaultValue' => 'EUR',
            'setConf' => 1,
        ],
        'deferred_state' => [
            'type' => 'integer',
            'name' => 'DEFERRED_STATE',
            'defaultValue' => 0,
            'setConf' => 1,
        ],
        'email' => [
            'type' => 'string',
            'name' => 'EMAIL',
            'defaultValue' => null,
            'setConf' => 1,
        ],
        'embedded_mode' => [
            'type' => 'string',
            'name' => 'EMBEDDED_MODE',
            'defaultValue' => 'redirect',
            'setConf' => 1,
        ],
        'enable' => [
            'type' => 'integer',
            'name' => 'ENABLE',
            'defaultValue' => 0,
            'setConf' => 1,
        ],
        'inst_min_amount' => [
            'type' => 'integer',
            'name' => 'INST_MIN_AMOUNT',
            'defaultValue' => 150,
            'setConf' => 1,
        ],
        'inst_mode' => [
            'type' => 'integer',
            'name' => 'INST_MODE',
            'defaultValue' => 3,
            'setConf' => 1,
        ],
        'jwt' => [
            'type' => 'string',
            'name' => 'JWT',
            'defaultValue' => '{}',
            'setConf' => 1,
        ],
        'keep_cards' => [
            'type' => 'integer',
            'name' => 'KEEP_CARDS',
            'defaultValue' => 0,
            'setConf' => 1,
        ],
        'live_api_key' => [
            'type' => 'string',
            'name' => 'LIVE_API_KEY',
            'defaultValue' => null,
            'setConf' => 1,
        ],
        'onboarding_states' => [
            'type' => 'string',
            'name' => 'ONBOARDING_STATES',
            'defaultValue' => '{}',
            'setConf' => 1,
        ],
        'oney_allowed_countries' => [
            'type' => 'string',
            'name' => 'ONEY_ALLOWED_COUNTRIES',
            'defaultValue' => '',
            'setConf' => 1,
        ],
        'oney_cart_cta' => [
            'type' => 'integer',
            'name' => 'ONEY_CART_CTA',
            'defaultValue' => 1,
            'setConf' => 1,
        ],
        'oney_custom_max_amounts' => [
            'type' => 'string',
            'name' => 'ONEY_CUSTOM_MAX_AMOUNTS',
            'defaultValue' => 'EUR:300000',
            'setConf' => 1,
        ],
        'oney_custom_min_amounts' => [
            'type' => 'string',
            'name' => 'ONEY_CUSTOM_MIN_AMOUNTS',
            'defaultValue' => 'EUR:10000',
            'setConf' => 1,
        ],
        'oney_fees' => [
            'type' => 'integer',
            'name' => 'ONEY_FEES',
            'defaultValue' => 1,
            'setConf' => 1,
        ],
        'oney_optimized' => [
            'type' => 'integer',
            'name' => 'ONEY_OPTIMIZED',
            'defaultValue' => 0,
            'setConf' => 1,
        ],
        'oney_product_cta' => [
            'type' => 'integer',
            'name' => 'ONEY_PRODUCT_CTA',
            'defaultValue' => 1,
            'setConf' => 1,
        ],
        'order_state_auth' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_AUTH',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_auth_test' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_AUTH_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_cancelled' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_CANCELLED',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_cancelled_test' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_CANCELLED_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_error' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_ERROR',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_error_test' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_ERROR_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_exp' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_EXP',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_exp_test' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_EXP_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_oney_pg' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_ONEY_PG',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_oney_pg_test' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_ONEY_PG_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_paid' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_PAID',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_paid_test' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_PAID_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_email_link' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_EMAIL_LINK',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_email_link_test' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_EMAIL_LINK_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_sms_link' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_SMS_LINK',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_sms_link_test' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_SMS_LINK_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_pending' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_PENDING',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_pending_test' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_PENDING_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_refund' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_REFUND',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'order_state_refund_test' => [
            'type' => 'integer',
            'name' => 'ORDER_STATE_REFUND_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'payment_methods' => [
            'type' => 'string',
            'name' => 'PAYMENT_METHODS',
            'defaultValue' => '{"amex":false,"applepay":false,"bancontact":false,"deferred":false,"installment":false,"ideal":false,"mybank":false,"one_click":false,"oney":false,"satispay":false,"standard":true}',
            'setConf' => 1,
        ],
        'sandbox_mode' => [
            'type' => 'integer',
            'name' => 'SANDBOX_MODE',
            'defaultValue' => 1,
            'setConf' => 1,
        ],
        'test_api_key' => [
            'type' => 'string',
            'name' => 'TEST_API_KEY',
            'defaultValue' => null,
            'setConf' => 1,
        ],
        'telemetry_hash' => [
            'type' => 'string',
            'name' => 'TELEMETRY_HASH',
            'defaultValue' => '',
            'setConf' => 0,
        ],
    ];
    public $order_states = [
        'paid' => [
            'cfg' => 'PS_OS_PAYMENT',
            'template' => 'payment',
            'logable' => true,
            'send_email' => true,
            'paid' => true,
            'hidden' => false,
            'delivery' => false,
            'invoice' => true,
            'color' => '#04b404',
            'name' => [
                'en' => 'Payment accepted',
                'fr' => 'Paiement effectué',
                'es' => 'Pago efectuado',
                'it' => 'Pagamento effettuato',
            ],
            'type' => 'paid',
        ],
        'refund' => [
            'cfg' => 'PS_OS_REFUND',
            'template' => 'refund',
            'logable' => false,
            'send_email' => true,
            'paid' => false,
            'hidden' => false,
            'delivery' => false,
            'invoice' => true,
            'color' => '#ea3737',
            'name' => [
                'en' => 'Refunded',
                'fr' => 'Remboursé',
                'es' => 'Reembolsado',
                'it' => 'Rimborsato',
            ],
            'type' => 'refund',
        ],
        'pending' => [
            'cfg' => 'PS_OS_PENDING',
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => false,
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#a1f8a1',
            'name' => [
                'en' => 'Payment in progress',
                'fr' => 'Paiement en cours',
                'es' => 'Pago en curso',
                'it' => 'Pagamento in corso',
            ],
            'type' => 'pending',
        ],
        'error' => [
            'cfg' => 'PS_OS_ERROR',
            'template' => 'payment_error',
            'logable' => false,
            'send_email' => true,
            'paid' => false,
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#8f0621',
            'name' => [
                'en' => 'Payment failed',
                'fr' => 'Paiement échoué',
                'es' => 'Payment failed',
                'it' => 'Payment failed',
            ],
            'type' => 'error',
        ],
        'cancelled' => [
            'cfg' => 'PS_OS_CANCELED',
            'template' => 'order_canceled',
            'logable' => false,
            'send_email' => true,
            'paid' => false,
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#2C3E50',
            'name' => [
                'en' => 'Payment cancelled',
                'fr' => 'Paiement annulé',
                'es' => 'Payment cancelled',
                'it' => 'Payment cancelled',
            ],
            'type' => 'cancelled',
        ],
        'auth' => [
            'cfg' => null,
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => true,
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#04b404',
            'name' => [
                'en' => 'Payment authorized',
                'fr' => 'Paiement autorisé',
                'es' => 'Pago',
                'it' => 'Pagamento autorizzato',
            ],
            'type' => 'pending',
        ],
        'exp' => [
            'cfg' => null,
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => false,
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#8f0621',
            'name' => [
                'en' => 'Autorization expired',
                'es' => 'Autorización vencida',
                'fr' => 'Autorisation expirée',
                'it' => 'Autorizzazione scaduta',
            ],
            'type' => 'expired',
        ],
        'oney_pg' => [
            'cfg' => null,
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => false,
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#a1f8a1',
            'name' => [
                'en' => 'Oney - Pending',
                'fr' => 'Oney - En attente',
                'es' => 'Oney - Pending',
                'it' => 'Oney - Pending',
            ],
            'type' => 'pending',
        ],
        'email_link' => [
            'cfg' => null,
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => false,
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#a1f8a1',
            'name' => [
                'en' => 'Payment Link Sent by Email',
                'fr' => 'Lien de Paiement Envoyé par Email',
                'es' => 'Payment Link Sent by Email',
                'it' => 'Link di Pagamento Inviato via Email',
            ],
            'type' => 'pending',
        ],
        'sms_link' => [
            'cfg' => null,
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => false,
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#a1f8a1',
            'name' => [
                'en' => 'Payment Link Sent by SMS',
                'fr' => 'Lien de Paiement Envoyé par SMS',
                'es' => 'Payment Link Sent by SMS',
                'it' => 'Link di Pagamento Inviato via SMS',
            ],
            'type' => 'pending',
        ],
    ];
    public $allowed_iso_lang = ['en', 'fr', 'es', 'it'];
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description Delete a given configuration from a key
     *
     * @param string $key
     *
     * @return false
     */
    public function delete($key = '')
    {
        if (!is_string($key) || !$key) {
            return false;
        }

        if (!array_key_exists($key, $this->configurations)) {
            return false;
        }

        return $this->dependencies
            ->getPlugin()
            ->getConfiguration()
            ->deleteByName($this->getName($key));
    }

    /**
     * @description Delete all module configuration
     *
     * @return bool
     */
    public function deleteAll()
    {
        $flag = true;

        if (empty($this->configurations)) {
            return false;
        }

        foreach ($this->configurations as $key => $config) {
            if (!$flag) {
                continue;
            }
            $flag = $flag && $this->delete($key);
        }

        return $flag;
    }

    /**
     * @description get a given configuration
     *
     * @param string $key
     *
     * @return array
     */
    public function get($key = '')
    {
        if (!is_string($key) || !$key) {
            return [];
        }

        if (!array_key_exists($key, $this->configurations)) {
            return [];
        }

        return $this->configurations[$key];
    }

    /**
     * @description get the current module configuration
     *
     * @param string $key
     *
     * @return false|mixed
     */
    public function getCurrentConfigurations()
    {
        $current_configurations = [];

        foreach ($this->configurations as $name => $config) {
            $value = $this->getValue($name);
            $current_configurations[$name] = null !== $value ? $value : $this->getDefault($name);
        }

        return $current_configurations;
    }

    /**
     * @description get the default value of a given configuration
     *
     * @param string $key
     *
     * @return false|mixed
     */
    public function getDefault($key = '')
    {
        if (!is_string($key) || !$key) {
            return false;
        }

        if (!array_key_exists($key, $this->configurations)) {
            return false;
        }

        return $this->configurations[$key]['defaultValue'];
    }

    /**
     * @description get the name of a given configuration
     *
     * @param string $key
     *
     * @return string
     */
    public function getName($key = '')
    {
        if (!is_string($key) || !$key) {
            return '';
        }

        if (!array_key_exists($key, $this->configurations)) {
            return '';
        }

        return strtoupper($this->dependencies->name) . '_' . $this->configurations[$key]['name'];
    }

    /**
     * @description get the type of a given configuration
     *
     * @param string $key
     *
     * @return string
     */
    public function getType($key = '')
    {
        if (!is_string($key) || !$key) {
            return '';
        }

        if (!array_key_exists($key, $this->configurations)) {
            return '';
        }

        return $this->configurations[$key]['type'];
    }

    /**
     * @description get the current value of a given configuration
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getValue($key = '')
    {
        if (!is_string($key) || !$key) {
            return false;
        }

        // If the configuration is not from module configuration, use adapter method
        $configuration_key = !array_key_exists($key, $this->configurations) ? $key : $this->getName($key);

        return $this->dependencies
            ->getPlugin()
            ->getConfiguration()
            ->get($configuration_key);
    }

    /**
     * @description Set the default configuration for module in the database
     *
     * @return bool
     */
    public function initialize()
    {
        $flag = true;

        if (empty($this->configurations)) {
            return false;
        }

        foreach ($this->configurations as $key => $config) {
            if (!$flag) {
                continue;
            }
            if ($config['setConf']) {
                $flag = $flag && $this->set($key, $config['defaultValue']);
            }
        }

        return $flag;
    }

    /**
     * @description set a configuration
     *
     * @param string $key
     * @param string $value
     *
     * @return bool
     */
    public function set($key = '', $value = '')
    {
        if (!is_string($key) || !$key) {
            return false;
        }

        if (!array_key_exists($key, $this->configurations)) {
            return false;
        }

        if (!$type = $this->getType($key)) {
            return false;
        }

        switch ($type) {
            case 'integer':
                if (!is_int($value)) {
                    return false;
                }

                break;
            default:
            case 'string':
                if (!is_string($value) && null != $value) {
                    return false;
                }

                break;
        }

        return $this->dependencies
            ->getPlugin()
            ->getConfiguration()
            ->updateValue($this->getName($key), $value);
    }
}
