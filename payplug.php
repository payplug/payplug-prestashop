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
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/vendor/autoload.php';

require_once dirname(__FILE__) . '/../../app/AppKernel.php';

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
        $this->need_instance = 0;
        $this->tab = 'payments_gateways';
        $this->version = '4.19.0';

        if (version_compare(_PS_VERSION_, '8', '<')) {
            $this->ps_versions_compliancy = ['min' => '1.7', 'max' => '1.7'];
        }

        parent::__construct();

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
        if ($this->payplug_dependencies) {
            return parent::disable($force_all)
                && $this->payplug_dependencies
                    ->getPlugin()
                    ->getConfigurationAction()
                    ->disableAction();
        }
    }

    /**
     * Redirect to our Module configuration controller.
     *
     * @see Module::getContent()
     */
    public function getContent()
    {
        if (!$this->isValidInstallation()) {
            $this->payplug_dependencies
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
                    $tab->active = true;
                    $tab->save();
                }
            }
        }

        Tools::redirectAdmin($this->context->link->getAdminLink($controllerName));
    }

    public function getService($name = '')
    {
        if (!is_string($name) || '' == $name) {
            return null;
        }

        // Check if service exists to avoid exception
        if (method_exists($this, 'isSymfonyContext')
            && $this->isSymfonyContext()
            && method_exists($this, 'getContainer')
            && $this->getContainer()->has($name)) {
            return $this->getContainer()->get($name);
        }

        return $this->getServiceFromFileLoader($name);
    }

    public function getServiceFromFileLoader($name = '')
    {
        if (!is_string($name) || '' == $name) {
            return null;
        }

        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
        $loader->load('services.yml');
        $container->compile();

        return $container->has($name) ? $container->get($name) : null;
    }

    /**
     * Load asset on the back office.
     */
    public function hookActionAdminControllerSetMedia()
    {
        if ($this->payplug_dependencies) {
            return $this->payplug_dependencies->hookClass->actionAdminControllerSetMedia();
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
        if ($this->payplug_dependencies) {
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
        if ($this->payplug_dependencies) {
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
        if ($this->payplug_dependencies) {
            return $this->payplug_dependencies->hookClass->actionExportGDPRData($params);
        }
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function hookActionObjectOrderHistoryAddAfter($params)
    {
        if ($this->payplug_dependencies) {
            return $this
                ->getService('payplug.models.classes.hook')
                ->actionObjectOrderHistoryAddAfter($params);
        }
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function hookActionObjectOrderStateAddAfter($params)
    {
        if ($this->payplug_dependencies) {
            $type = $this->payplug_dependencies
                ->getPlugin()
                ->getTools()
                ->tool('getValue', 'order_state_type');

            return $this->payplug_dependencies
                ->getPlugin()
                ->getOrderStateAction()
                ->saveTypeAction((int) $params['object']->id, $type);
        }
    }

    public function hookActionAdminLanguagesControllerSaveAfter($params)
    {
        if ($this->payplug_dependencies) {
            return $this->payplug_dependencies->hookClass->actionAdminLanguagesControllerSaveAfter($params);
        }
    }

    public function hookActionObjectOrderStateUpdateAfter($params)
    {
        if ($this->payplug_dependencies) {
            $type = $this->payplug_dependencies
                ->getPlugin()
                ->getTools()
                ->tool('getValue', 'order_state_type');

            return $this->payplug_dependencies
                ->getPlugin()
                ->getOrderStateAction()
                ->saveTypeAction((int) $params['object']->id, $type);
        }
    }

    public function hookActionObjectOrderStateDeleteAfter($params)
    {
        if ($this->payplug_dependencies) {
            return $this->payplug_dependencies
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
        if ($this->payplug_dependencies) {
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
        if ($this->payplug_dependencies) {
            return $this->payplug_dependencies->hookClass->adminOrder($params);
        }
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function hookDisplayCustomerAccount($params)
    {
        if ($this->payplug_dependencies) {
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
        if ($this->payplug_dependencies && $this->active) {
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
        if ($this->payplug_dependencies) {
            return $this->payplug_dependencies
                ->getPlugin()
                ->getOrderStateAction()
                ->renderOption();
        }
    }

    /**
     * @description  display applepay button on product page
     *
     * @return mixed
     */
    public function hookDisplayProductAdditionalInfo()
    {
        if ($this->payplug_dependencies) {
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
        if ($this->payplug_dependencies) {
            $configuration = $this->payplug_dependencies
                ->getPlugin()
                ->getConfigurationClass();
            if ((bool) $configuration->getValue('oney_cart_cta')) {
                // todo: this function should be splitted renderCartCTA and renderProductCTA
                $oneyCTA = $dependencies
                    ->getPlugin()
                    ->getOneyAction()
                    ->renderCTA();
            }
            $paymentCTA = $this->payplug_dependencies
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
        if ($this->payplug_dependencies) {
            $configuration = $this->payplug_dependencies
                ->getPlugin()
                ->getConfigurationClass();
            if ((bool) $configuration->getValue('oney_product_cta')) {
                return $this->payplug_dependencies
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
        if ($this->payplug_dependencies) {
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
        if ($this->payplug_dependencies) {
            $context = $this->payplug_dependencies
                ->getPlugin()
                ->getContext()
                ->get();

            if (!$this->payplug_dependencies->configClass->isAllowed()) {
                return false;
            }

            $context->smarty->assign([
                'api_url' => $this
                    ->getService('payplug.utilities.service.api')
                    ->getApiUrl(),
            ]);

            // Données sous forme de tableau
            $payment_options = $this->payplug_dependencies
                ->getPlugin()
                ->getPaymentMethodClass()
                ->getPaymentOptionCollection();

            // Transforme tableau en object
            return $this->payplug_dependencies->loadAdapterPresta()->displayPaymentOption($payment_options);
        }
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function hookDisplayPaymentReturn($params)
    {
        if ($this->payplug_dependencies) {
            return $this->payplug_dependencies->hookClass->paymentReturn($params);
        }
    }

    /**
     * @description Install plugin
     *
     * @return bool
     *
     * @see Module::install()
     */
    public function install()
    {
        $flag = parent::install();

        if ($this->payplug_dependencies && $flag) {
            $installation = $this->payplug_dependencies
                ->getPlugin()
                ->getConfigurationAction()
                ->installAction();

            if (!$installation['result']) {
                $this->errors[] = $this->payplug_dependencies
                    ->getPlugin()
                    ->getTools()
                    ->tool('displayError', $installation['message']);
                $this->uninstall();
            }
            $flag = $installation['result'];
        }

        // Clear symf cache to ensure the service are correctly load
        \Tools::clearSf2Cache();

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
     * @return bool
     */
    public function isValidPHPVersion()
    {
        $php_min_version = 50600;

        if (!defined('PHP_VERSION_ID')) {
            $php_version = explode('.', PHP_VERSION);
            define('PHP_VERSION_ID', (int) $php_version[0] * 10000 + (int) $php_version[1] * 100 + (int) $php_version[2]);
        }

        return PHP_VERSION_ID >= $php_min_version;
    }

    /**
     * Run update module.
     */
    public function runUpgradeModule()
    {
        if ($this->payplug_dependencies) {
            $this->payplug_dependencies
                ->getPlugin()
                ->getInstall()
                ->checkOrderStates();
            $helpers = $this->payplug_dependencies->getHelpers();
            $helpers['files']::clean();

            // Call getAccount method to update countries and amounts configurations from merchant account
            $permissions = $this
                ->getService('payplug.utilities.service.api')
                ->getAccount();
        }

        return parent::runUpgradeModule();
    }

    public function setDependencies()
    {
        $page_name = Context::getContext()->controller;
        $excluded_controllers = [
            'index',
            'category',
            'manufacturer',
            'new-products',
            'prices-drop',
        ];
        if (!in_array($page_name, $excluded_controllers)) {
            $this->payplug_dependencies = new DependenciesClass();
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
        if ($this->payplug_dependencies) {
            $uninstall = $this->payplug_dependencies
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
        $hooks_list = [
            'actionAdminControllerSetMedia',
            'actionAdminLanguagesControllerSaveAfter',
            'actionClearCompileCache',
            'actionDeleteGDPRCustomer',
            'actionExportGDPRData',
            'actionObjectOrderHistoryAddAfter',
            'actionObjectOrderStateAddAfter',
            'actionObjectOrderStateUpdateAfter',
            'actionObjectOrderStateDeleteAfter',
            'actionUpdateLangAfter',
            'displayCustomerAccount',
            'displayAdminStatusesForm',
            'displayExpressCheckout',
            'displayPaymentReturn',
            'displayProductAdditionalInfo',
            'displayProductPriceBlock',
            'displayHeader',
            'paymentOptions',
        ];

        if (\version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
            array_push($hooks_list, 'adminOrder');
        } else {
            array_push($hooks_list, 'displayAdminOrderMain');
        }

        return $hooks_list;
    }
}
