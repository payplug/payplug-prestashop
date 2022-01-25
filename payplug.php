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

/**
 * Check if prestashop Context
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_ . 'payplug/vendor/autoload.php');
require_once(_PS_MODULE_DIR_ . 'payplug/constants.php');

require 'vendor/autoload.php';

use PrestaShop\PsAccountsInstaller\Installer\Exception\ModuleVersionException;
use PrestaShop\PsAccountsInstaller\Installer\Exception\ModuleNotInstalledException;
use ContextCore as Context;

class Payplug extends PaymentModule
{
    public $payplug_dependencies;

    /**
     * @var ContainerInterface
     */
    private $container;

    private $emailSupport;

    /**
     * Constructor
     *
     * @return void
     * @throws Exception
     */
    public function __construct()
    {
        $this->name = 'payplug';
        $this->author = 'PayPlug';
        $this->bootstrap = true;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->description = $this->l('payplug.construct.description');
        $this->displayName = 'PayPlug';
        $this->module_key = '1ee28a8fb5e555e274bd8c2e1c45e31a';
        $this->need_instance = true;
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.8'];
        $this->tab = 'payments_gateways';
        // todo: automate module version in CI with constant value
        $this->version = '3.8.0';

        // todo: check if needed for psAccount
        $this->emailSupport = 'support@payplug.com';

        parent::__construct();

        $this->module = false;

        if ($this->isValidPHPVersion()) {
            $this->setDependencies();
            $this->setModule();
        }
    }

    /**
     * @param bool $force_all
     * @return bool
     * @see Module::disable()
     *
     */
    public function disable($force_all = false)
    {
        if ($this->module) {
            return $this->module->disable($force_all);
        }
    }

    /**
     * @return string
     * @see Module::getContent()
     */
    public function getContent()
    {
        if ($this->module) {
            if (!$this->isValidInstallation()) {
                $this->install(true);
            }

            // Load context for PsAccount
            // todo: move to another file for php 5.3 compliance
            $facade = $this->getService('ps_accounts.facade');
            Media::addJsDef([
                'contextPsAccounts' => $facade->getPsAccountsPresenter()
                    ->present($this->name),
            ]);

            //
            $package = json_decode(Tools::file_get_contents(dirname(__FILE__)."/_dev/js/back/package.json"));
            $this->context->smarty->assign('pathVendor', $this->getPathUri() . 'views/js/chunk-vendors.' . $package->version . '.js');
            $this->context->smarty->assign('pathApp', $this->getPathUri() . 'views/js/app.' . $package->version . '.js');

            try {
                $psAccountsService = $facade->getPsAccountsService();
                Media::addJsDef([
                    'psBillingContext' => [
                        'context' => [
                            'versionPs' => _PS_VERSION_,
                            'versionModule' => $this->version,
                            'moduleName' => $this->name,
                            'refreshToken' => $psAccountsService->getRefreshToken(),
                            'emailSupport' => $this->emailSupport,
                            'shop' => [
                                'uuid' => $psAccountsService->getShopUuidV4()
                            ],
                            'i18n' => [
                                'isoCode' => $this->getLanguageIsoCode()
                            ],
                            'user' => [
                                'createdFromIp' => (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '',
                                'email' => $psAccountsService->getEmail()
                            ],
                            'moduleTosUrl' => $this->getTosLink()
                        ]
                    ]
                ]);
            } catch (ModuleNotInstalledException $e) {
                // You handle exception here
                die(dump($e->getMessage()));
            } catch (ModuleVersionException $e) {
                // You handle exception here
                die(dump($e->getMessage()));
            }

            return (new \PayPlug\classes\AdminClass())->getContent();
        } else {
            $iso_code = Context::getContext()->language->iso_code;
            if ($iso_code == 'en' || $iso_code == 'gb') {
                $iso_code = 'en-gb';
            }
            $faq_url = 'https://support.payplug.com/hc/' . $iso_code . '/articles/360021267140';
            $this->context->smarty->assign('faq_url', $faq_url);

            $logo_url = __PS_BASE_URI__ . 'modules/payplug/views/img/logo_payplug.png';
            $this->context->smarty->assign('url_logo', $logo_url);

            $this->context->controller->addCSS(__PS_BASE_URI__ . 'modules/payplug/views/css/admin.css');

            return $this->display(__FILE__, '/views/templates/admin/php_version.tpl');
        }
    }

    /**
     * @description Get the module hook list from current Prestashop version
     */
    private function getHookList()
    {
        return [
            'actionAdminControllerSetMedia',
            'actionAdminLanguagesControllerSaveAfter',
            'actionAdminPerformanceControllerAfter',
            'actionCarrierUpdate',
            'actionClearCompileCache',
            'actionDeleteGDPRCustomer',
            'actionExportGDPRData',
            'actionObjectCarrierAddAfter',
            'actionOrderStatusUpdate',
            'actionObjectOrderStateAddAfter',
            'actionObjectOrderStateUpdateAfter',
            'actionObjectOrderStateDeleteAfter',
            'actionUpdateLangAfter',
            'adminOrder',
            'customerAccount',
            'displayAdminOrderMain',
            'displayBackOfficeFooter',
            'displayBeforeShoppingCartBlock',
            'displayExpressCheckout',
            'displayProductPriceBlock',
            'displayAdminStatusesForm',
            'header',
            'moduleRoutes',
            'payment',
            'paymentReturn',
            'paymentOptions',
            'registerGDPRConsent',
        ];
    }

    /**
     * @description Get the isoCode from the context language, if null, send 'en' as default value
     * todo: check if needed for psAccount
     *
     * @return string
     */
    public function getLanguageIsoCode()
    {
        return $this->context->language !== null ? $this->context->language->iso_code : 'en';
    }

    /**
     * @description Retrieve service
     *
     * @param string $serviceName
     *
     * @return mixed
     */
    public function getService($serviceName)
    {
        if ($this->container === null) {
            $this->container = new \PrestaShop\ModuleLibServiceContainer\DependencyInjection\ServiceContainer(
                $this->name,
                $this->getLocalPath()
            );
        }

        return $this->container->getService($serviceName);
    }

    /**
     * @description Get the TosLink
     * todo: check if needed for psAccount
     *
     * @return string
     */
    public function getTosLink()
    {
        $iso_lang = $this->getLanguageIsoCode();
        switch ($iso_lang) {
            case 'fr':
                $url = 'https://yoururl.ltd/mentions-legales';
                break;
            default:
                $url = 'https://yoururl.ltd/legal-notice';
                break;
        }

        return $url;
    }

    /**
     * @description To load admin and admin_order (js and css) in order details in PS 1.7.7.0
     */
    public function hookActionAdminControllerSetMedia()
    {
        if ($this->module) {
            return $this->payplug_dependencies->getDependency('hook')->exe('actionAdminControllerSetMedia');
        }
    }

    /**
     * @description Flush PayPlugCache (PS 1.6), when PrestaShop cache cleared
     * @param $params
     * @return mixed
     */
    public function hookActionAdminPerformanceControllerAfter($params)
    {
        //todo: Rajouter le test de la table payplug cache avant d'executer ce code*/
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->actionAdminPerformanceControllerAfter($params);
        }
    }

    /**
     * @description Flush PayPlugCache (PS 1.7), when PrestaShop cache cleared
     * @param $params
     * @return mixed
     */
    public function hookActionClearCompileCache($params)
    {
        //todo: Rajouter le test de la table payplug cache avant d'executer ce code
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->actionClearCompileCache($params);
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookActionDeleteGDPRCustomer($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->actionDeleteGDPRCustomer($params);
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookActionExportGDPRData($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->actionExportGDPRData($params);
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookActionOrderStatusUpdate($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->actionOrderStatusUpdate($params);
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookActionObjectOrderStateAddAfter($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->getDependency('hook')->exe('actionObjectOrderStateAddAfter', $params);
        }
    }

    public function hookActionAdminLanguagesControllerSaveAfter($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->actionAdminLanguagesControllerSaveAfter($params);
        }
    }

    public function hookActionObjectOrderStateUpdateAfter($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->getDependency('hook')->exe(
                'actionObjectOrderStateUpdateAfter',
                $params
            );
        }
    }

    public function hookActionObjectOrderStateDeleteAfter($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->getDependency('hook')->exe(
                'actionObjectOrderStateDeleteAfter',
                $params
            );
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookActionUpdateLangAfter($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->actionUpdateLangAfter($params);
        }
    }

    /**
     * @description retrocompatibility of hookDisplayAdminOrderMain for version before 1.7.7.0
     * @param $params
     * @return mixed
     */
    public function hookAdminOrder($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->adminOrder($params);
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookCustomerAccount($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->customerAccount($params);
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookDisplayAdminOrderMain($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->displayAdminOrderMain($params);
        }
    }

    /**
     * @return mixed
     */
    public function hookDisplayAdminStatusesForm()
    {
        if ($this->module) {
            return $this->payplug_dependencies->getDependency('hook')->exe('displayAdminStatusesForm');
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookDisplayBackOfficeFooter($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->displayBackOfficeFooter($params);
        }
    }

    /**
     * @description Display Oney CTA on Shopping cart page
     * @param $params
     * @return mixed
     */
    public function hookDisplayBeforeShoppingCartBlock($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->displayBeforeShoppingCartBlock($params);
        }
    }

    /**
     * @return mixed
     */
    public function hookDisplayExpressCheckout()
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->displayExpressCheckout();
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookDisplayProductPriceBlock($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->displayProductPriceBlock($params);
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookHeader($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->displayHeader($params);
        }
    }

    /**
     * @param $params
     * @return mixed
     *
     * This hook is not used anymore in PS 1.7 but we have to keep it for retro-compatibility
     */
    public function hookPayment($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->payment($params);
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookPaymentOptions($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->paymentOptions($params);
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookPaymentReturn($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->paymentReturn($params);
        }
    }

    /**
     * @description Install plugin
     * @param bool $soft_install
     * @return bool
     * @see Module::install()
     */
    public function install($soft_install = false)
    {
        if ($this->module) {
            $flag = true;

            // Use for update module is not fully installed
            if (!$soft_install) {
                $this->payplug_dependencies = null;
                $flag = $flag && parent::install() &&
                    $this->getService('ps_accounts.installer')->install();
                $this->setDependencies();
            }

            // Install configuration
            if ($flag) {
                $flag = $flag && $this->payplug_dependencies->getDependency('install')->install();
            }

            // Install hook
            if ($flag) {
                $hook_list = $this->getHookList();
                foreach ($hook_list as $hook) {
                    $flag = $flag && $this->registerHook($hook);
                }
            }

            return $flag;
        }

        return parent::install();
    }

    /**
     * @description Check if mobile is validated installation
     * @return bool
     */
    public function isValidInstallation()
    {
        if (Validate::isLoadedObject($this)) {
            return Configuration::hasKey('PAYPLUG_COMPANY_ID');
        }
        return true;
    }

    /**
     * @description test if php requiremnt is valid
     * @return array
     */
    public function isValidPHPVersion()
    {
        $php_min_version = 50600;

        if (!defined('PHP_VERSION_ID')) {
            $php_version = explode('.', PHP_VERSION);
            define('PHP_VERSION_ID', ($php_version[0] * 10000 + $php_version[1] * 100 + $php_version[2]));
        }

        return PHP_VERSION_ID >= $php_min_version;
    }

    /**
     * Run update module
     */
    public function runUpgradeModule()
    {
        if ($this->module) {
            $this->payplug_dependencies->getDependency('install')->checkOrderStates();
        }

        return parent::runUpgradeModule();
    }

    public function setDependencies()
    {
        $this->payplug_dependencies = new \PayPlug\classes\PayPlugDependencies();
    }

    private function setModule()
    {
        $this->module = $this->payplug_dependencies->payplug;
    }

    /**
     * @description Uninstall plugin
     * @return bool|mixed
     * @see Module::uninstall()
     */
    public function uninstall()
    {
        if ($this->module) {
            return parent::uninstall() && $this->payplug_dependencies->getDependency('install')->uninstall();
        }

        return parent::uninstall();
    }
}
