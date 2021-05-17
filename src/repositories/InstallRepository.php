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

    /** @var object */
    protected $context;

    /** @var object */
    public $log;

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

    public function __construct($config, $constant, $context, $order_state, $shop, $sql, $tools, $payplug)
    {
        $this->config = $config;
        $this->constant = $constant;
        $this->context = $context;
        $this->order_state = $order_state;
        $this->shop = $shop;
        $this->sql = $sql;
        $this->tools = $tools;

        $this->payplug = $payplug;

        $this->log = new MyLogPHP($this->constant->get('_PS_MODULE_DIR_') . 'payplug/log/install-log.csv');
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
            && $this->config->updateValue('PAYPLUG_DEFERRED', 0)
            && $this->config->updateValue('PAYPLUG_DEFERRED_AUTO', 0)
            && $this->config->updateValue('PAYPLUG_DEFERRED_STATE', 0)
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
            && $this->config->updateValue('PAYPLUG_ONE_CLICK', null)
            && $this->config->updateValue('PAYPLUG_ONEY', null)
            && $this->config->updateValue('PAYPLUG_ONEY_ALLOWED_COUNTRIES', '')
            && $this->config->updateValue('PAYPLUG_ONEY_MAX_AMOUNTS', 'EUR:2000')
            && $this->config->updateValue('PAYPLUG_ONEY_MIN_AMOUNTS', 'EUR:150')
            && $this->config->updateValue('PAYPLUG_SANDBOX_MODE', 1)
            && $this->config->updateValue('PAYPLUG_SHOW', 0)
            && $this->config->updateValue('PAYPLUG_STANDARD', 1)
            && $this->config->updateValue('PAYPLUG_TEST_API_KEY', null)
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
     * @description Delete basic configuration
     * @return bool
     */
    private function deleteConfig()
    {
        return ($this->config->deleteByName('PAYPLUG_ALLOW_SAVE_CARD')
            && $this->config->deleteByName('PAYPLUG_COMPANY_ID')
            && $this->config->deleteByName('PAYPLUG_COMPANY_ID_TEST')
            && $this->config->deleteByName('PAYPLUG_COMPANY_STATUS')
            && $this->config->deleteByName('PAYPLUG_CONFIGURATION_OK')
            && $this->config->deleteByName('PAYPLUG_CURRENCIES')
            && $this->config->deleteByName('PAYPLUG_DEBUG_MODE')
            && $this->config->deleteByName('PAYPLUG_DEFERRED')
            && $this->config->deleteByName('PAYPLUG_DEFERRED_AUTO')
            && $this->config->deleteByName('PAYPLUG_DEFERRED_STATE')
            && $this->config->deleteByName('PAYPLUG_EMAIL')
            && $this->config->deleteByName('PAYPLUG_EMBEDDED_MODE')
            && $this->config->deleteByName('PAYPLUG_INST')
            && $this->config->deleteByName('PAYPLUG_INST_MIN_AMOUNT')
            && $this->config->deleteByName('PAYPLUG_INST_MODE')
            && $this->config->deleteByName('PAYPLUG_KEEP_CARDS')
            && $this->config->deleteByName('PAYPLUG_LIVE_API_KEY')
            && $this->config->deleteByName('PAYPLUG_MAX_AMOUNTS')
            && $this->config->deleteByName('PAYPLUG_MIN_AMOUNTS')
            && $this->config->deleteByName('PAYPLUG_OFFER')
            && $this->config->deleteByName('PAYPLUG_ONE_CLICK')
            && $this->config->deleteByName('PAYPLUG_ONEY')
            && $this->config->deleteByName('PAYPLUG_ONEY_ALLOWED_COUNTRIES')
            && $this->config->deleteByName('PAYPLUG_ONEY_MAX_AMOUNTS')
            && $this->config->deleteByName('PAYPLUG_ONEY_MIN_AMOUNTS')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_AUTH')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_AUTH_TEST')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_ERROR')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_ERROR_TEST')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_EXP')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_EXP_TEST')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_ONEY_PG')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_ONEY_PG_TEST')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_PAID')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_PAID_TEST')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_PENDING')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_PENDING_TEST')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_REFUND')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_REFUND_TEST')
            && $this->config->deleteByName('PAYPLUG_SANDBOX_MODE')
            && $this->config->deleteByName('PAYPLUG_SHOW')
            && $this->config->deleteByName('PAYPLUG_STANDARD')
            && $this->config->deleteByName('PAYPLUG_TEST_API_KEY')
        );
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
        $this->log->info('Starting to install again.');

        // check requirement
        $report = $this->checkRequirements();
        if (!$report['php']['up2date']) {
            return $this->setInstallError($this->l('Install failed: PHP Requirement.'));
        }
        if (!$report['curl']['up2date']) {
            return $this->setInstallError($this->l('Install failed: cURL Requirement.'));
        }
        if (!$report['openssl']['up2date']) {
            return $this->setInstallError($this->l('Install failed: OpenSSL Requirement.'));
        }

        //
        if ($this->shop->isFeatureActive()) {
            $this->shop->setContext();
        }

        // Set payplug config
        if (!$this->createConfig()) {
            return $this->setInstallError($this->l('Install failed: createConfig()'));
        }

        // Install order state
        if (!$this->createOrderStates()) {
            return $this->setInstallError($this->l('Install failed: Create order states.'));
        }

        // Install SQL
        if (!$this->sql->installSQL()) {
            return $this->setInstallError($this->l('Install failed: Install SQL tables.'));
        }

        // Install tab
        if (!$this->payplug->PrestashopSpecificObject->installTab()) {
            return $this->setInstallError($this->l('Install failed: Install Tab'));
        }

        $this->log->info('Install successful.');
        return true;
    }

    /**
     * @description Set error on module installation
     * @param $error
     */
    public function setInstallError($error = '')
    {
        $this->myLogPHP->error($error);
        $this->payplug->_errors[] = $this->tools->displayError($error);

        $this->log->info('Install failed.');
        $this->log->info('Install error: ' . $error);

        // revert installation
        $this->uninstall();

        return false;
    }

    public function setUninstallError($error = '')
    {
        $this->log->error($error);
        return false;
    }

    /**
     * @description Uninstall PayPlug Module
     * @return bool
     */
    public function uninstall()
    {
        $this->log->info('Starting to uninstall.');

        $keep_cards = (bool)$this->config->get('PAYPLUG_KEEP_CARDS');
        if (!$keep_cards) {
            $this->log->info('Saved cards will be deleted.');

            if (!$this->payplug->uninstallCards()) {
                return $this->setUninstallError('Unable to delete saved cards.');
            }

            $this->log->info('Saved cards successfully deleted.');
        } else {
            $this->log->info('Cards will be kept.');
        }

        if (!$this->deleteConfig()) {
            return $this->setUninstallError('Uninstall failed: configuration.');
        }

        if (!$this->sql->uninstallSQL($keep_cards)) {
            return $this->setUninstallError('Uninstall failed: sql.');
        }

        if (!$this->payplug->PrestashopSpecificObject->uninstallTab()) {
            return $this->setUninstallError('Uninstall failed: tab.');
        }

        $this->log->info('Uninstall succeeded.');
        return true;
    }
}
