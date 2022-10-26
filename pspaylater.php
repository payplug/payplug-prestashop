<?php
/**
 * 2013 - 2022 PayPlug SAS.
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
 * Check if prestashop Context.
 */
if (!\defined('_PS_VERSION_')) {
    exit;
}

require_once \dirname(__FILE__) . '/vendor/autoload.php';

class PsPaylater extends PaymentModule
{
    public $payplug_dependencies;
    private $container;

    /** @var array */
    public $adminControllers;

    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->name = 'pspaylater';
        $this->author = 'PayPlug';
        $this->bootstrap = true;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->description = $this->l('pspaylater.construct.description');
        $this->displayName = 'PrestaShop Paylater with PayPlug and Oney';
        $this->module_key = '5c403c88b9f9097fca7fd50c4f25b86d';
        $this->need_instance = true;
        $this->tab = 'payments_gateways';
        $this->version = '0.4.0';

        if (version_compare(_PS_VERSION_, '8', '<')) {
            $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.7'];
        }

        parent::__construct();

        $this->module = false;
        $this->controllers = [
            'AdminPsPayLater'
        ];
        $this->adminControllers = [
            [
                'className' => 'AdminPsPayLater',
                'parent' => -1
            ]
        ];

        if ($this->isValidPHPVersion()) {
            $this->setDependencies();
            $this->setModule();
        }

        if ($this->payplug_dependencies->getDependency('configClass')->isValidFeature('feature_ps_account')
            && $this->container === null) {
            $this->container = new \PrestaShop\ModuleLibServiceContainer\DependencyInjection\ServiceContainer(
                $this->name,
                $this->getLocalPath()
            );
        }
    }

    /**
     * @return string
     *
     * @see Module::getContent()
     */
    public function getContent()
    {
        if (!$this->isValidInstallation()) {
            $this->install(true);
        }

        $controllerName = 'AdminPsPayLater';

        // Check if controller name exist
        if (!Tab::getIdFromClassName($controllerName)) {
            $this->payplug_dependencies->getDependency('install')->installTab();
        }

        Tools::redirectAdmin($this->context->link->getAdminLink($controllerName));
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
            'displayAdminOrderMain',
            'displayBackOfficeFooter',
            'displayBeforeShoppingCartBlock',
            'displayExpressCheckout',
            'displayProductPriceBlock',
            'displayAdminStatusesForm',
            'header',
            'moduleRoutes',
            'paymentReturn',
            'paymentOptions',
            'registerGDPRConsent',
        ];
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
            return parent::disable($force_all) && $this->payplug_dependencies->getDependency('configClass')->disable();
        }
    }

    /**
     * Load asset on the back office
     */
    public function hookActionAdminControllerSetMedia()
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->actionAdminControllerSetMedia();
        }
    }

    /**
     * @description Flush PayPlugCache (PS 1.6), when PrestaShop cache cleared
     *
     * @param $params   $this->setDependencies();
     *
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
     *
     * @param $params
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
     * @param $params
     *
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
     *
     * @return mixed
     */
    public function hookDisplayAdminOrderMain($params)
    {
        if ($this->module && $this->active) {
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
     *
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
     *
     * @param $params
     *
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
        if ($this->module && Configuration::get($this->payplug_dependencies->dependencies->getConfigurationKey('oneyCartCta'))) {
            return $this->payplug_dependencies->hookClass->displayExpressCheckout();
        }
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function hookDisplayProductPriceBlock($params)
    {
        if ($this->module && Configuration::get($this->payplug_dependencies->dependencies->getConfigurationKey('oneyProductCta'))) {
            return $this->payplug_dependencies->hookClass->displayProductPriceBlock($params);
        }
    }

    /**
     * @param $params
     *
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
     *
     * @return mixed
     */
    public function hookPaymentOptions()
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->paymentOptions();
        }
    }

    /**
     * @param $params
     *
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
     *
     * @param bool $soft_install
     *
     * @return bool
     *
     * @see Module::install()
     */
    public function install($soft_install = false)
    {
        if ($this->module) {
            $flag = true;

            \PayPlug\src\utilities\helpers\FilesHelper::clean();

            // Use for update module is not fully installed
            if (!$soft_install) {
                $this->payplug_dependencies = null;
                $flag = $flag && parent::install();
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

            if ($this->payplug_dependencies->getDependency('configClass')->isValidFeature('feature_ps_account')
                && $flag) {
                $this->getService('ps_accounts.installer')->install();
            }

            return $flag;
        }

        return parent::install();
    }

    /**
     * Retrieve service.
     *
     * todo: report service installation to bnpl.php
     *
     * @param string $serviceName
     *
     * @return mixed
     */
    public function getService($serviceName)
    {
        return $this->container->getService($serviceName);
    }

    /**
     * @description Check if mobile is validated installation
     *
     * @return bool
     */
    public function isValidInstallation()
    {
        if (Validate::isLoadedObject($this)) {
            return Configuration::hasKey(Tools::strtoupper($this->name) . '_COMPANY_ID');
        }

        return true;
    }

    /**
     * @description test if php requiremnt is valid
     *
     * @return array
     */
    public function isValidPHPVersion()
    {
        $php_min_version = 50600;

        if (!\defined('PHP_VERSION_ID')) {
            $php_version = \explode('.', PHP_VERSION);
            \define('PHP_VERSION_ID', ($php_version[0] * 10000 + $php_version[1] * 100 + $php_version[2]));
        }

        return PHP_VERSION_ID >= $php_min_version;
    }

    /**
     * Run update module.
     */
    public function runUpgradeModule()
    {
        if ($this->module) {
            $this->payplug_dependencies->getDependency('install')->checkOrderStates();
            \PayPlug\src\utilities\helpers\FilesHelper::clean();
        }

        return parent::runUpgradeModule();
    }

    public function setDependencies()
    {
        $this->payplug_dependencies = new \PayPlug\classes\PayPlugDependencies();
    }

    private function setModule()
    {
        $this->module = $this->payplug_dependencies->dependencies;
    }

    /**
     * @description Uninstall plugin
     *
     * @return bool|mixed
     *
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
