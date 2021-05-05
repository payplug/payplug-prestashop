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

use Exception;
use PayPlug\classes\MyLogPHP;
use Shop;
use Tools;

class InstallRepository extends \PaymentModule
{
    protected $payplug;
    private $myLogPHP;

    public function __construct($payplug)
    {
        parent::__construct();
        $this->payplug = $payplug;
        $this->myLogPHP = new MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
    }

    /**
     * @return bool
     * @throws Exception
     * @see Module::install()
     *
     */
    public function install()
    {
        $log = $this->myLogPHP;
        $log->info('Starting to install.');
        $install = [
            'flag' => true,
            'error' => false
        ];

        $report = $this->payplug->checkRequirements();
        if (!$report['php']['up2date'] && $install['flag']) {
            $this->installError('Your server must run PHP 5.3 or greater');
        } else {
            $log->info('Install success: PHP Requirement.');
        }

        if (!$report['curl']['up2date'] && $install['flag']) {
            $this->installError('PHP cURL extension must be enabled on your server');
        } else {
            $log->info('Install success: cURL Requirement.');
        }

        if (!$report['openssl']['up2date'] && $install['flag']) {
            $this->installError('Install failed: OpenSSL Requirement.');
        } else {
            $log->info('Install success: OpenSSL Requirement.');
        }

        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $log->info('----------------> Install configuration. <----------------');
        if (!$this->payplug->createConfig() && $install['flag']) {
            $this->installError('Install failed: create config');
        }
        $log->info('----------------> Install configuration: ' .
            ($install['flag'] ? 'success' : 'failed') . ' <----------------');

        $log->info('----------------> Install order states. <----------------');

        if (!$this->payplug->createOrderStates() && $install['flag']) {
            $this->installError('Install failed: create order states.');
        }
        $log->info('----------------> Install order states: ' .
            ($install['flag'] ? 'success' : 'failed') . ' <----------------');

        $log->info('----------------> Install SQL. <----------------');
        if (!(new SQLtableRepository())->installSQL() /*&& $install['flag']*/) {
            $this->installError('Install failed: SQL.');
        }
        $log->info('----------------> Install SQL: ' . ($install['flag'] ? 'success' : 'failed') . ' <---------------');

        $log->info('----------------> Install tab. <----------------');
        if (!$this->payplug->installTab() && $install['flag']) {
            $this->installError('Install failed: Tab.');
        }
        $log->info('---------------> Install tab: ' . ($install['flag'] ? 'success' : 'failed') . ' <----------------');

        $log->info('----------------> Install Oney. <----------------');
        if (!(new PluginRepository())->getEntity()->getOney()->installOney() && $install['flag']) {
            $this->installError('Install failed: Oney.');
        }
        $log->info('---------------> Install Oney: ' . ($install['flag'] ? 'success' : 'failed') . ' <---------------');

        if ($install['flag']) {
            $log->info('Install succeeded.');
            return true;
        }

        $log->info('Install failed.');
        $log->info('Install error: ' . $install['error']);

        // revert installation
        $this->payplug->uninstall();
        $install['error'] = (isset($install['error'])) ? 'Élément en cause : ' . $install['error'] : '';
        $this->payplug->context->controller->errors[] = $this->payplug->l('Le module PayPlug n\'a pas été installé 
        en raison d\'une erreur. Les modifications apportées ont bien été annulées.');
        $this->payplug->context->controller->errors[] = $install['error'];
        return false;
    }

    public function installError($error)
    {
        $this->myLogPHP->error($error);
        $this->payplug->_errors[] = Tools::displayError($this->payplug->l($error));

        return [
            'flag' => false,
            'error' => $error
        ];
    }
}
