<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\repositories;

use PayPlug\classes\MyLogPHP;

class InstallRepository extends Repository
{
    /** @var object */
    protected $config;

    /** @var object */
    protected $constant;

    /** @var object OrderStateRepository */
    protected $order_state;

    /** @var array */
    private $order_states_list = [
        'paid' => [
            'cfg' => 'PS_OS_PAYMENT',
            'template' => 'payment',
            'logable' => true,
            'send_email' => true,
            'paid' => true,
            'module_name' => 'payplug',
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
        ],
        'refund' => [
            'cfg' => 'PS_OS_REFUND',
            'template' => 'refund',
            'logable' => false,
            'send_email' => true,
            'paid' => false,
            'module_name' => 'payplug',
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
        ],
        'pending' => [
            'cfg' => 'PS_OS_PENDING',
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => false,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => true,
            'color' => '#a1f8a1',
            'name' => [
                'en' => 'Payment in progress',
                'fr' => 'Paiement en cours',
                'es' => 'Pago en curso',
                'it' => 'Pagamento in corso',
            ],
        ],
        'error' => [
            'cfg' => 'PS_OS_ERROR',
            'template' => 'payment_error',
            'logable' => false,
            'send_email' => true,
            'paid' => false,
            'module_name' => 'payplug',
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
        ],
        'auth' => [
            'cfg' => null,
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => true,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#04b404',
            'name' => [
                'en' => 'Payment authorized',
                'fr' => 'Paiement autorisé',
                'es' => 'Pago',
                'it' => 'Pagamento',
            ],
        ],
        'exp' => [
            'cfg' => null,
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => false,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#8f0621',
            'name' => [
                'en' => 'Authoriation expired',
                'es' => 'Autorización vencida',
                'fr' => 'Autorisation expirée',
                'it' => 'Autorizzazione scaduta',
            ],
        ],
        'oney_pg' => [
            'cfg' => null,
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => false,
            'module_name' => 'payplug',
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
        ]
    ];

    /** @var object */
    protected $shop;

    /** @var object */
    protected $tools;

    /** @var object */
    protected $payplug;

    public function __construct($config, $constant, $order_state, $shop, $tools, $payplug)
    {
        $this->config = $config;
        $this->constant = $constant;
        $this->order_state = $order_state;
        $this->shop = $shop;
        $this->tools = $tools;

        $this->payplug = $payplug;
    }

    /**
     * @description Check if current configuration requirements are respected
     * @return array
     */
    public function checkRequirements()
    {
        $php_min_version = 50600;
        $curl_min_version = '7.21';
        $openssl_min_version = 0x1000100f;
        $report = [
            'php' => [
                'version' => 0,
                'installed' => true,
                'up2date' => false,
            ],
            'curl' => [
                'version' => 0,
                'installed' => false,
                'up2date' => false,
            ],
            'openssl' => [
                'version' => 0,
                'installed' => false,
                'up2date' => false,
            ],
        ];

        //PHP
        if (!defined('PHP_VERSION_ID')) {
            $report['php']['version'] = PHP_VERSION;
            $php_version = explode('.', PHP_VERSION);
            define('PHP_VERSION_ID', ($php_version[0] * 10000 + $php_version[1] * 100 + $php_version[2]));
        }
        $report['php']['up2date'] = PHP_VERSION_ID >= $php_min_version ? true : false;

        //cURL
        $curl_exists = extension_loaded('curl');
        if ($curl_exists) {
            $curl_version = curl_version();
            $report['curl']['version'] = $curl_version['version'];
            $report['curl']['installed'] = true;
            $report['curl']['up2date'] = version_compare(
                $curl_version['version'],
                $curl_min_version,
                '>='
            ) ? true : false;
        }

        //OpenSSl
        $openssl_exists = extension_loaded('openssl');
        if ($openssl_exists) {
            $report['openssl']['version'] = OPENSSL_VERSION_NUMBER;
            $report['openssl']['installed'] = true;
            $report['openssl']['up2date'] = OPENSSL_VERSION_NUMBER >= $openssl_min_version ? true : false;
        }

        return $report;
    }

    /**
     * @description Create basic configuration
     * @return bool
     */
    protected function createConfig()
    {
        return ($this->config->updateValue('PAYPLUG_ALLOW_SAVE_CARD', 0)
            && $this->config->updateValue('PAYPLUG_COMPANY_ID', null)
            && $this->config->updateValue('PAYPLUG_COMPANY_STATUS', '')
            && $this->config->updateValue('PAYPLUG_CURRENCIES', 'EUR')
            && $this->config->updateValue('PAYPLUG_DEBUG_MODE', 0)
            && $this->config->updateValue('PAYPLUG_EMAIL', null)
            && $this->config->updateValue('PAYPLUG_EMBEDDED_MODE', 0)
            && $this->config->updateValue('PAYPLUG_INST', null)
            && $this->config->updateValue('PAYPLUG_INST_MIN_AMOUNT', 150)
            && $this->config->updateValue('PAYPLUG_INST_MODE', 3)
            && $this->config->updateValue('PAYPLUG_KEEP_CARDS', 0)
            && $this->config->updateValue('PAYPLUG_LIVE_API_KEY', null)
            && $this->config->updateValue('PAYPLUG_MAX_AMOUNTS', 'EUR:1000000')
            && $this->config->updateValue('PAYPLUG_MIN_AMOUNTS', 'EUR:1')
            && $this->config->updateValue('PAYPLUG_OFFER', '')
            && $this->config->updateValue('PAYPLUG_ONEY', null)
            && $this->config->updateValue('PAYPLUG_ONE_CLICK', null)
            && $this->config->updateValue('PAYPLUG_SANDBOX_MODE', 1)
            && $this->config->updateValue('PAYPLUG_SHOW', 0)
            && $this->config->updateValue('PAYPLUG_TEST_API_KEY', null)
            && $this->config->updateValue('PAYPLUG_DEFERRED', 0)
            && $this->config->updateValue('PAYPLUG_DEFERRED_AUTO', 0)
            && $this->config->updateValue('PAYPLUG_DEFERRED_STATE', 0)
            && $this->config->updateValue('PAYPLUG_ONEY', 0)
            && $this->config->updateValue('PAYPLUG_ONEY_ALLOWED_COUNTRIES', '')
            && $this->config->updateValue('PAYPLUG_ONEY_MAX_AMOUNTS', 'EUR:2000')
            && $this->config->updateValue('PAYPLUG_ONEY_MIN_AMOUNTS', 'EUR:150')
        );
    }

    /**
     * @description Create usual status
     * @return bool
     */
    public function createOrderStates()
    {
        foreach ($this->order_states_list as $key => $state) {
            $this->order_state->create($key, $state, true);
            $this->order_state->create($key, $state, false);
        }

        $this->order_state->removeIdsUnusedByPayPlug();
        return true;
    }

    /**
     * @todo: repatriate uninstall code
     * @description Install PayPlug Module
     * @param bool $soft_install
     * @return bool
     * @see Module::install()
     */
    public function install()
    {
        $log = new MyLogPHP($this->constant->get('_PS_MODULE_DIR_') . 'payplug/log/install-log.csv');
        $log->info('Starting to install again.');
        $install = [
            'flag' => true,
            'error' => false
        ];

        // check requirement
        $report = $this->checkRequirements();
        if (!$report['php']['up2date'] && $install['flag']) {
            $this->_errors[] = $this->tools->displayError($this->l('Your server must run PHP 5.3 or greater'));
            $log->error('Install failed: PHP Requirement.');
            $install['flag'] = false;
            $install['error'] = 'Configuration PHP inf. version 5.3';
        }
        if (!$report['curl']['up2date'] && $install['flag']) {
            $this->_errors[] = $this->tools->displayError($this->l('PHP cURL extension must be enabled on your server'));
            $log->error('Install failed: cURL Requirement.');
            $install['flag'] = false;
            $install['error'] = 'cURL Requirement';
        }
        if (!$report['openssl']['up2date'] && $install['flag']) {
            $this->_errors[] = $this->tools->displayError($this->l('OpenSSL 1.0.1 or later'));
            $log->error('Install failed: OpenSSL Requirement.');
            $install['flag'] = false;
            $install['error'] = 'OpenSSL Requirement';
        }

        if ($this->shop->isFeatureActive()) {
            $this->shop->setContext();
        }

        // Set payplug config
        if (!$this->createConfig() && $install['flag']) {
            $log->error('Install failed: configuration.');
            $install['flag'] = false;
            $install['error'] = 'Création des éléments de configuration  ($this->createConfig)';
        }

        // Install order state
        if (!$this->createOrderStates() && $install['flag']) {
            $log->error('Install failed: order states.');
            $install['flag'] = false;
        }

        // Install SQL
        if (!(new SQLtableRepository())->installSQL()) {
            $log->error('Install failed: SQL.');
            $install['flag'] = false;
            $install['error'] = 'Création des tables SQL';
        }

        // Install tab
        if (!$this->payplug->PrestashopSpecificObject->installTab() && $install['flag']) {
            $log->error('Install failed: tab.');
            $install['flag'] = false;
            $install['error'] = 'Onglet comprenant les détails des échéances des Paiements Fractionnés (back office)';
        }

        if ($install['flag']) {
            $log->info('Install succeeded.');
            return true;
        }

        $log->info('Install failed.');
        $log->info('Install error: ' . $install['error']);

        // revert installation
        $this->uninstall();
        $install['error'] = (isset($install['error'])) ? 'Élément en cause : ' . $install['error'] : '';
        $this->context->controller->errors[] = $this->l('Le module PayPlug n\'a pas été installé 
        en raison d\'une erreur. Les modifications apportées ont bien été annulées.');
        $this->context->controller->errors[] = $install['error'];
        return false;
    }

    /**
     * @description Uninstall plugin
     * @return bool
     */
    public function uninstall()
    {
        return $this->payplug->uninstall();
    }
}
