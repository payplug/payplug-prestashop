<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\repositories;

use PayPlug\classes\ConfigClass;
use Db;

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

    /** @var object OrderStateRepository */
    protected $order_state_entity;

    /** @var object */
    protected $order_state_specific;

    /** @var object */
    protected $shop;

    /** @var object */
    protected $sql;

    /** @var object */
    protected $tools;

    /** @var object */
    protected $validate;

    /** @var object */
    protected $payplug;

    public function __construct(
        $config,
        $constant,
        $context,
        $order_state,
        $order_state_entity,
        $order_state_specific,
        $shop,
        $sql,
        $tools,
        $validate,
        $payplug,
        $mylogphp
    ) {
        $this->config = $config;
        $this->constant = $constant;
        $this->context = $context;
        $this->order_state = $order_state;
        $this->order_state_entity = $order_state_entity;
        $this->order_state_specific = $order_state_specific;
        $this->shop = $shop;
        $this->sql = $sql;
        $this->tools = $tools;
        $this->validate = $validate;
        $this->payplug = $payplug;
        $this->log = $mylogphp;

        $this->setParams();
    }

    /**
     * @description Check if payplug order state are well installed
     */
    public function checkOrderStates()
    {
        $order_states_list = $this->order_state_entity->getList();

        foreach ($order_states_list as $key => $state) {
            // Check live OrderState
            $key_config_live = 'PAYPLUG_ORDER_STATE_' . $this->tools->tool('strtoupper', $key);
            $id_order_state_live = (int)$this->config->get($key_config_live);
            $order_state_live = $this->order_state_specific->get($id_order_state_live);
            if (!$this->validate->validate('isLoadedObject', $order_state_live)
                || (isset($order_state_live->deleted) && $order_state_live->deleted)) {
                $this->order_state->create($key, $state, false, true);
            }

            // Check sandbox OrderState
            $key_config_sandbox = $key_config_live . '_TEST';
            $id_order_state_sandbox = (int)$this->config->get($key_config_sandbox);
            $order_state_sandbox = $this->order_state_specific->get($id_order_state_sandbox);
            if (!$this->validate->validate('isLoadedObject', $order_state_sandbox)
                || (isset($order_state_sandbox->deleted) && $order_state_sandbox->deleted)) {
                $this->order_state->create($key, $state, true, true);
            }
        }

        $this->order_state->removeIdsUnusedByPayPlug();
    }

    /**
     * @description Create usual status
     * @return bool
     */
    public function createOrderStates()
    {
        $order_states_list = $this->order_state_entity->getList();
        foreach ($order_states_list as $key => $state) {
            $this->order_state->create($key, $state, true);
            $this->order_state->create($key, $state, false);
        }

        $this->order_state->removeIdsUnusedByPayPlug();
        return true;
    }

    /**
     * @description Create usual status
     * @return bool
     */
    public function createOrderStatesType()
    {
        $this->log->info('Execute createOrderStatesType');
        $order_states_list = $this->order_state_entity->getList();
        foreach ($order_states_list as $key => $state) {
            // live status
            $live_key = $this->order_state->getConfigKey($key, false);
            $id_order_state_live = $this->config->get($live_key);
            $this->log->info('Live key : ' . $live_key . ' / Id Order State: ' . $id_order_state_live);
            if ($id_order_state_live) {
                $res = $this->order_state->saveType((int)$id_order_state_live, $state['type']);
                $this->log->info('Save type: ' . $state['type'] . ' - result: ' . ($res ? 'ok' : 'ko'));
            }

            // sandbox status
            $sandbox_key = $this->order_state->getConfigKey($key, true);
            $id_order_state_sandbox = $this->config->get($sandbox_key);
            $this->log->info('Sandbox key : ' . $sandbox_key . ' / Id Order State: ' . $id_order_state_sandbox);
            if ($id_order_state_sandbox) {
                $res = $this->order_state->setType((int)$id_order_state_sandbox, $state['type']);
                $this->log->info('Save type: ' . $state['type'] . ' - result: ' . ($res ? 'ok' : 'ko'));
            }
        }
        // mapping of the native prestashop statuses
        $prestashop_order_states = [
            'PS_OS_PAYMENT' => 'paid',
            'PS_OS_WS_PAYMENT' => 'nothing',
            'PS_OS_OUTOFSTOCK_PAID' => 'paid',
            'PS_OS_CANCELED' => 'cancelled',
            'PS_OS_REFUND' => 'refund',
            'PS_OS_ERROR' => 'error',
            'PS_OS_OUTOFSTOCK_UNPAID' => 'pending',
            'PS_OS_CHEQUE' => 'nothing',
            'PS_OS_BANKWIRE' => 'nothing',
            'PS_OS_COD_VALIDATION' =>'nothing',
            'PS_OS_PREPARATION' =>'nothing',
            'PS_OS_SHIPPING' =>'nothing',
            'PS_OS_DELIVERED'=>'nothing',
        ];
        $date = date('Y-m-d');
        $payplug_order_states_sql = [];
        foreach ($prestashop_order_states as $key => $type) {
            $id_order_state = $this->config->get($key);
            $getTypeQuery = ' SELECT `type` FROM `' . _DB_PREFIX_ . 'payplug_order_state` WHERE  `id_order_state` = ' . $id_order_state;
            $sqlGetType = Db::getInstance()->executeS($getTypeQuery);
            if ($sqlGetType  && $sqlGetType  != $type) {
                $payplug_order_states_sql[] = ' UPDATE `' . _DB_PREFIX_ . 'payplug_order_state` SET `type` = ' . "'$type'" . ' WHERE  `id_order_state` = ' . $id_order_state;
            } else {
                $payplug_order_states_sql[] = '
            INSERT INTO `' . _DB_PREFIX_ . 'payplug_order_state` (`id_order_state`, `type`, `date_add`, `date_upd`)
            VALUES (' . $id_order_state . ', "' . $type . '", "' . $date . '", "' . $date . '")';
            }
        }
        if ($payplug_order_states_sql) {
            foreach ($payplug_order_states_sql as $sql) {
                $db = Db::getInstance()->execute($sql);
                unset($sql);
            }
        }

        return true;
    }

    /**
     * @description Delete basic configuration
     * @return bool
     */
    private function deleteConfig()
    {
        return ($this->config->deleteByName('PAYPLUG_ALLOW_SAVE_CARD')
            && $this->config->deleteByName('PAYPLUG_BANCONTACT')
            && $this->config->deleteByName('PAYPLUG_COMPANY_ID')
            && $this->config->deleteByName('PAYPLUG_COMPANY_ID_TEST')
            && $this->config->deleteByName('PAYPLUG_COMPANY_STATUS')
            && $this->config->deleteByName('PAYPLUG_COMPANY_ISO')
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
            && $this->config->deleteByName('PAYPLUG_ONEY_CUSTOM_MAX_AMOUNTS')
            && $this->config->deleteByName('PAYPLUG_ONEY_CUSTOM_MIN_AMOUNTS')
            && $this->config->deleteByName('PAYPLUG_ONEY_FEES')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_AUTH')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_AUTH_TEST')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_CANCELLED')
            && $this->config->deleteByName('PAYPLUG_ORDER_STATE_CANCELLED_TEST')
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
            && $this->config->deleteByName('PAYPLUG_PUBLISHABLE_KEY')
            && $this->config->deleteByName('PAYPLUG_PUBLISHABLE_KEY_TEST')
            && $this->config->deleteByName('PAYPLUG_SANDBOX_MODE')
            && $this->config->deleteByName('PAYPLUG_SHOW')
            && $this->config->deleteByName('PAYPLUG_STANDARD')
            && $this->config->deleteByName('PAYPLUG_TEST_API_KEY')
        );
    }

    /**
     * @description Install PayPlug Module
     * @param bool $soft_install
     * @return bool
     * @see Module::install()
     */
    public function install()
    {
        $this->log->info('Starting to install again.');

        // check requirement
        $report = ConfigClass::checkRequirements();
        if (!$report['php']['up2date']) {
            return $this->setInstallError('Install failed: PHP Requirement.');
        }
        if (!$report['curl']['up2date']) {
            return $this->setInstallError('Install failed: cURL Requirement.');
        }
        if (!$report['openssl']['up2date']) {
            return $this->setInstallError('Install failed: OpenSSL Requirement.');
        }

        // Check if multishop feature is active then set the context
        if ($this->shop->isFeatureActive()) {
            $this->shop->setContext();
        }

        // Set payplug config
        if (!$this->setConfig()) {
            return $this->setInstallError('Install failed:setConfig()');
        }

        // Install SQL
        if (!$this->sql->installSQL()) {
            return $this->setInstallError('Install failed: Install SQL tables.');
        }

        // Install order state
        if (!$this->createOrderStates()) {
            return $this->setInstallError('Install failed: Create order states.');
        }

        // Install order state type
        if (!$this->createOrderStatesType()) {
            return $this->setInstallError('Install failed: Create order states type.');
        }

        // Install tab
        if (!$this->payplug->PrestashopSpecificObject->installTab()) {
            return $this->setInstallError('Install failed: Install Tab');
        }

        $this->log->info('Install successful.');
        return true;
    }

    /**
     * @description Create basic configuration
     * @return bool
     */
    public function setConfig()
    {
        return ($this->config->updateValue('PAYPLUG_ALLOW_SAVE_CARD', 0)
            && $this->config->updateValue('PAYPLUG_BANCONTACT', null)
            && $this->config->updateValue('PAYPLUG_COMPANY_ID', null)
            && $this->config->updateValue('PAYPLUG_COMPANY_STATUS', '')
            && $this->config->updateValue('PAYPLUG_COMPANY_ISO', '')
            && $this->config->updateValue('PAYPLUG_CURRENCIES', 'EUR')
            && $this->config->updateValue('PAYPLUG_DEBUG_MODE', 0)
            && $this->config->updateValue('PAYPLUG_DEFERRED', 0)
            && $this->config->updateValue('PAYPLUG_DEFERRED_AUTO', 0)
            && $this->config->updateValue('PAYPLUG_DEFERRED_STATE', 0)
            && $this->config->updateValue('PAYPLUG_EMAIL', null)
            && $this->config->updateValue('PAYPLUG_EMBEDDED_MODE', 'redirected')
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
            && $this->config->updateValue('PAYPLUG_ONEY_MAX_AMOUNTS', 'EUR:300000')
            && $this->config->updateValue('PAYPLUG_ONEY_MIN_AMOUNTS', 'EUR:10000')
            && $this->config->updateValue('PAYPLUG_ONEY_CUSTOM_MAX_AMOUNTS', 'EUR:3000')
            && $this->config->updateValue('PAYPLUG_ONEY_CUSTOM_MIN_AMOUNTS', 'EUR:100')
            && $this->config->updateValue('PAYPLUG_ONEY_FEES', 1)
            && $this->config->updateValue('PAYPLUG_SANDBOX_MODE', 1)
            && $this->config->updateValue('PAYPLUG_SHOW', 0)
            && $this->config->updateValue('PAYPLUG_STANDARD', 1)
            && $this->config->updateValue('PAYPLUG_TEST_API_KEY', null)
        );
    }

    /**
     * @description Set error on module install
     * @param $error
     * @return bool
     */
    public function setInstallError($error = '')
    {
        $this->log->error($error);
        $this->payplug->_errors[] = $this->tools->tool('displayError', $error);

        $this->log->info('Install failed.');
        $this->log->info('Install error: ' . $error);

        // revert installation
        $this->uninstall();

        return false;
    }

    /**
     * @description Set module order state
     */
    protected function setParams()
    {
        $this->order_state_entity->setList([
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
                'type' => 'paid',
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
                'type' => 'refund',
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
                'type' => 'pending',
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
                'type' => 'error',
            ],
            'cancelled' => [
                'cfg' => 'PS_OS_CANCELED',
                'template' => 'order_canceled',
                'logable' => false,
                'send_email' => true,
                'paid' => false,
                'module_name' => 'payplug',
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
                'type' => 'pending',
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
                'type' => 'pending',
            ]
        ]);
    }

    /**
     * @description Set error on module uninstall
     * @param $error
     * @return bool
     */
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

            if (!$this->dependencies->cardClass->uninstallCards()) {
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
