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
 * Do not edit or add to this file if you wish to upgrade Payplug module to newer
 * versions in the future.
 *
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */
// Check if prestashop Context
use PayPlug\classes\DependenciesClass;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/vendor/autoload.php';

class Payplug extends PaymentModule
{
    public $payplug_dependencies;

    /** @var array */
    public $adminControllers;
    public $module;

    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->name = 'payplug';
        $this->author = 'Payplug';
        $this->bootstrap = true;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->description = $this->l('The online payment solution combining simplicity and first-rate support to boost your sales.');
        $this->displayName = 'Payplug';
        $this->module_key = '1ee28a8fb5e555e274bd8c2e1c45e31a';
        $this->need_instance = true;
        $this->tab = 'payments_gateways';
        $this->version = '4.16.0';

        if (version_compare(_PS_VERSION_, '8', '<')) {
            $this->ps_versions_compliancy = ['min' => '1.7', 'max' => '1.7'];
        }

        parent::__construct();

        $this->module = false;
        $this->controllers = [
            'AdminPayplug',
            'AdminPayPlugInstallment',
        ];
        $this->adminControllers = [
            [
                'className' => 'AdminPayplug',
            ],
            [
                'className' => 'AdminPayPlugInstallment',
                'parent' => 'AdminPayplug',
                'name' => [
                    'en' => 'Installment Plans',
                    'gb' => 'Installment Plans',
                    'it' => 'Pagamenti frazionati',
                    'fr' => 'Paiements en plusieurs fois',
                ],
            ],
        ];

        if ($this->isValidPHPVersion()) {
            $this->setDependencies();
            $this->setModule();
        }
    }

    /**
     * @param bool $force_all
     *
     * @return bool
     *
     * @see Module::disable()
     */
    public function disable($force_all = false)
    {
        if ($this->module) {
            $dependencies = new DependenciesClass();

            return parent::disable($force_all)
                && $dependencies
                    ->getPlugin()
                    ->getConfigurationAction()
                    ->disableAction();
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
            $dependencies = new DependenciesClass();
            $dependencies
                ->getPlugin()
                ->getConfigurationAction()
                ->installAction();
        }
        $controllerName = 'AdminPayplug';

        // Check if controller name exist then if linked to the right module
        $idtab = Tab::getIdFromClassName($controllerName);
        if (!$idtab) {
            $this->payplug_dependencies->dependencies
                ->getPlugin()
                ->getConfigurationAction()
                ->installTabAction();
        } else {
            $tab = new Tab($idtab);
            if ('payplug' != $tab->module) {
                foreach ($this->adminControllers as $adminControllers) {
                    $idtab = Tab::getIdFromClassName($adminControllers['className']);
                    $tab = new Tab($idtab);
                    $tab->module = 'payplug';
                    $tab->active = 1;
                    $tab->save();
                }
            }
        }

        Tools::redirectAdmin($this->context->link->getAdminLink($controllerName));
    }

    /**
     * Load asset on the back office.
     */
    public function hookActionAdminControllerSetMedia()
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->actionAdminControllerSetMedia();
        }
    }

    /**
     * @description Flush PayPlugCache (PS 1.6), when PrestaShop cache cleared.
     *
     * @param $params
     *
     * return bool
     */
    public function hookActionAdminPerformanceControllerAfter($params)
    {
        // todo: Rajouter le test de la table payplug cache avant d'executer ce code*/
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
        // todo: Rajouter le test de la table payplug cache avant d'executer ce code
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
            $dependencies = new DependenciesClass();

            $type = $dependencies
                ->getPlugin()
                ->getTools()
                ->tool('getValue', 'order_state_type');

            return $dependencies
                ->getPlugin()
                ->getOrderStateAction()
                ->saveTypeAction((int) $params['object']->id, $type);
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
            $dependencies = new DependenciesClass();

            $type = $dependencies
                ->getPlugin()
                ->getTools()
                ->tool('getValue', 'order_state_type');

            return $dependencies
                ->getPlugin()
                ->getOrderStateAction()
                ->saveTypeAction((int) $params['object']->id, $type);
        }
    }

    public function hookActionObjectOrderStateDeleteAfter($params)
    {
        if ($this->module) {
            $dependencies = new DependenciesClass();

            return $dependencies
                ->getPlugin()
                ->getOrderStateAction()
                ->deleteTypeAction($params);
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
    public function hookCustomerAccount($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->customerAccount($params);
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
     * @description This hook is used to display
     * a select box in the order state page (BO)
     * in order to create/update a type
     *
     * @param $param
     *
     * @return mixed
     */
    public function hookDisplayAdminStatusesForm()
    {
        if ($this->module) {
            $dependencies = new DependenciesClass();

            return $dependencies
                ->getPlugin()
                ->getOrderStateAction()
                ->renderOption();
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
            $configuration = $this->payplug_dependencies->dependencies->getPlugin()->getConfigurationClass();
            if ((bool) $configuration->getValue('oney_cart_cta')) {
                return $this->payplug_dependencies->hookClass->displayBeforeShoppingCartBlock($params);
            }
        }
    }

    /**
     * @description  display applepay button on product page
     *
     * @return mixed
     */
    public function hookDisplayProductAdditionalInfo()
    {
        if ($this->module) {
            return $this->payplug_dependencies->dependencies
                ->getPlugin()
                ->getCartAction()
                ->renderPaymentCTA();
        }
    }

    /**
     * @description hook applepay and oney on cart page
     *
     * @return mixed
     */
    public function hookDisplayExpressCheckout()
    {
        $oneyCTA = '';
        if ($this->module) {
            $dependencies = new DependenciesClass();
            $configuration = $this->payplug_dependencies->dependencies->getPlugin()->getConfigurationClass();
            if ((bool) $configuration->getValue('oney_cart_cta')) {
                // todo: this function should be splitted renderCartCTA and renderProductCTA
                $oneyCTA = $dependencies
                    ->getPlugin()
                    ->getOneyAction()
                    ->renderCTA();
            }
            $paymentCTA = $dependencies
                ->getPlugin()
                ->getCartAction()
                ->renderPaymentCTA();

            return $paymentCTA . $oneyCTA;
        }
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function hookDisplayProductPriceBlock($params)
    {
        if ($this->module) {
            $configuration = $this->payplug_dependencies->dependencies->getPlugin()->getConfigurationClass();
            if ((bool) $configuration->getValue('oney_product_cta')) {
                $dependencies = new DependenciesClass();

                return $dependencies
                    ->getPlugin()
                    ->getOneyAction()
                    ->renderCTA($params);
            }
        }
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function hookDisplayHeader($params)
    {
        if ($this->module) {
            return $this->payplug_dependencies->hookClass->displayHeader($params);
        }
    }

    /**
     * @param $params
     *
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
     *
     * @return mixed
     */
    public function hookPaymentOptions()
    {
        if ($this->module) {
            $dependencies = new DependenciesClass();
            $context = $dependencies->getPlugin()->getContext()->get();

            if (!$dependencies->configClass->isAllowed()) {
                return false;
            }

            $context->smarty->assign([
                'api_url' => $dependencies
                    ->getPlugin()
                    ->getApiService()
                    ->getApiUrl(),
            ]);

            // Données sous forme de tableau
            $payment_options = $dependencies
                ->getPlugin()
                ->getPaymentMethodClass()
                ->getPaymentOptionCollection();

            // Transforme tableau en object
            return $dependencies->loadAdapterPresta()->displayPaymentOption($payment_options);
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
    public function install()
    {
        $flag = parent::install();

        if ($this->module && $flag) {
            $dependencies = new DependenciesClass();
            $installation = $dependencies
                ->getPlugin()
                ->getConfigurationAction()
                ->installAction();

            if (!$installation['result']) {
                $this->errors[] = $dependencies
                    ->getPlugin()
                    ->getTools()
                    ->tool('displayError', $installation['message']);
                $this->uninstall();
            }
            $flag = $flag && $installation['result'];
        }

        return $flag;
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

        if (!defined('PHP_VERSION_ID')) {
            $php_version = explode('.', PHP_VERSION);
            define('PHP_VERSION_ID', $php_version[0] * 10000 + $php_version[1] * 100 + $php_version[2]);
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
            $helpers = $this->module->getHelpers();
            $helpers['files']::clean();

            // Call getAccount method to update countries and amounts configurations from merchant account
            $api_key = $this->module
                ->getPlugin()
                ->getApiService()
                ->getCurrentApiKey();
            $permissions = $this->module
                ->getPlugin()
                ->getApiService()
                ->getAccount($api_key, false);
        }

        return parent::runUpgradeModule();
    }

    public function setDependencies()
    {
        $page_name = Context::getContext()->controller ? Context::getContext()->controller->php_self : '';
        $excluded_controllers = [
            'index',
            'category',
            'manufacturer',
            'new-products',
            'prices-drop',
        ];
        if (!in_array($page_name, $excluded_controllers)) {
            $this->payplug_dependencies = new PayPlug\classes\PayPlugDependencies();
        }
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
            $dependencies = new DependenciesClass();
            $uninstall = $dependencies
                ->getPlugin()
                ->getConfigurationAction()
                ->uninstallAction();
            if (!$uninstall['result']) {
                return false;
            }
        }

        return parent::uninstall();
    }

    /**
     * @description Get the module hook list from current Prestashop version
     */
    public function getHookList()
    {
        return [
            'actionAdminControllerSetMedia',
            'actionAdminLanguagesControllerSaveAfter',
            'actionAdminPerformanceControllerAfter',
            'actionClearCompileCache',
            'actionDeleteGDPRCustomer',
            'actionExportGDPRData',
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
            'displayProductAdditionalInfo',
            'displayProductPriceBlock',
            'displayAdminStatusesForm',
            'displayHeader',
            'payment',
            'paymentReturn',
            'paymentOptions',
        ];
    }

    private function setModule()
    {
        if ($this->payplug_dependencies) {
            $this->module = $this->payplug_dependencies->dependencies;
        }
    }
}
