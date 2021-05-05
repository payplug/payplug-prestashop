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
use \Shop;
use \Tools;

class InstallRepository extends Repository
{
    protected $payplug;

    public function __construct($payplug)
    {
        $this->payplug = $payplug;
    }

    /**
     * @return bool
     * @throws Exception
     * @see Module::install()
     *
     */
    public function install()
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
        $log->info('Starting to install.');
        $install = [
            'flag' => true,
            'error' => false
        ];

        $report = $this->payplug->checkRequirements();
        if (!$report['php']['up2date'] && $install['flag']) {
            $this->payplug->_errors[] = Tools::displayError(
                $this->payplug->l('Your server must run PHP 5.3 or greater')
            );
            $log->error('Install failed: PHP Requirement.');
            $install['flag'] = false;
            $install['error'] = 'Configuration PHP inf. version 5.3';
        } else {
            $log->info('Install success: PHP Requirement.');
        }

        if (!$report['curl']['up2date'] && $install['flag']) {
            $this->payplug->_errors[] = Tools::displayError(
                $this->payplug->l('PHP cURL extension must be enabled on your server')
            );
            $log->error('Install failed: cURL Requirement.');
            $install['flag'] = false;
            $install['error'] = 'cURL Requirement';
        } else {
            $log->info('Install success: cURL Requirement.');
        }

        if (!$report['openssl']['up2date'] && $install['flag']) {
            $this->payplug->_errors[] = Tools::displayError($this->payplug->l('OpenSSL 1.0.1 or later'));
            $log->error('Install failed: OpenSSL Requirement.');
            $install['flag'] = false;
            $install['error'] = 'OpenSSL Requirement';
        } else {
            $log->info('Install success: OpenSSL Requirement.');
        }

        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $log->info('Starting to install parent::install().');
        if (!parent::install() && $install['flag']) {
            $log->error('Install failed: parent::install().');
            $install['flag'] = false;
            $install['error'] = 'parent::install()';
            return false;
        } else {
            $log->info('Install success: parent::install().');
        }

        $log->info('----------------> Install hooks. <----------------');
        $hooksToRegister = [
            'paymentReturn',
            'Header',
            'adminOrder',
            'displayAdminOrderMain',
            'actionOrderStatusUpdate',
            'customerAccount',
            'paymentOptions',
            'Payment',
            'moduleRoutes',
            'registerGDPRConsent',
            'actionDeleteGDPRCustomer',
            'actionExportGDPRData',
            'actionObjectCarrierAddAfter',
            'actionCarrierUpdate',
            'displayProductPriceBlock',
            'displayExpressCheckout',
            'actionClearCompileCache',
            'displayBeforeShoppingCartBlock',
            'actionAdminControllerSetMedia',
        ];

        foreach ($hooksToRegister as $hookToRegister) {
            $log->info('Try to install Hook ' . $hookToRegister . '.');
            if (!$this->payplug->registerHook($hookToRegister) && $install['flag']) {
                $log->error('Install failed: Hook ' . $hookToRegister . '.');
                $install['flag'] = false;
                $install['error'] = 'Hook ' . $hookToRegister . ' non greffé';
                break;
            } else {
                $log->info('Install success: Hook ' . $hookToRegister . '.');
            }
        }

        //install hook 1.6
        $log->info('----------------> Install hooks 1.6. <----------------');
        if ($install['flag']) {
            $installHook16 = $this->payplug->installHook();
            $install['flag'] = $installHook16['flag'];
            $install['error'] = $installHook16['error'];
        }
        $log->info('----------------> Install hooks: ' . ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        $log->info('----------------> Install configuration. <----------------');
        if (!$this->payplug->createConfig() && $install['flag']) {
            $log->error('Install failed: configuration.');
            $install['flag'] = false;
            $install['error'] = 'Création des éléments de configuration  ($this->payplug->createConfig)';
        }
        $log->info('----------------> Install configuration: ' .
            ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        $log->info('----------------> Install order states. <----------------');

        if (!$this->payplug->createOrderStates() && $install['flag']) {
            $log->error('Install failed: order states.');
            $install['flag'] = false;
        }
        $log->info('----------------> Install order states: ' .
            ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        $log->info('----------------> Install SQL. <----------------');
        if (!(new SQLtableRepository())->installSQL() /*&& $install['flag']*/) {
            $log->error('Install failed: SQL.');
            $install['flag'] = false;
            $install['error'] = 'Création des tables SQL';
        }
        $log->info('----------------> Install SQL: ' . ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        $log->info('----------------> Install tab. <----------------');
        if (!$this->payplug->installTab() && $install['flag']) {
            $log->error('Install failed: tab.');
            $install['flag'] = false;
            $install['error'] = 'Onglet comprenant les détails des échéances des Paiements Fractionnés (back office)';
        }
        $log->info('----------------> Install tab: ' . ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        $log->info('----------------> Install Oney. <----------------');
        if (!$this->payplug->oney->installOney() && $install['flag']) {
            $log->error('Install failed: Oney.');
            $install['flag'] = false;
            $install['error'] = 'Oney ($this->payplug->installOney)';
        }
        $log->info('----------------> Install Oney: ' . ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

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
}
