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
 */ require_once dirname(__FILE__) . '/../../vendor/autoload.php';

use PayLaterModule\classes\DependenciesClass;

class AdminPsPayLaterController extends ModuleAdminController
{
    public $module;
    private $constant;
    private $dependencies;
    private $logger;

    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();

        $this->dependencies = new DependenciesClass();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->logger = $this->dependencies->getPlugin()->getLogger();
        $this->module = $this->dependencies->getPlugin()->getModule()->getInstanceByName($this->dependencies->name);
    }

    /**
     * Initialize the content by adding Boostrap and loading the TPL
     */
    public function initContent()
    {
        if (Tools::getValue('_ajax')) {
            $this->dependencies->adminClass->adminAjaxController();
        }
        if ($this->module->name == 'pspaylater') {
            $this->setPsAccount();
        }

        $this->dependencies->configClass->postProcess();
        $this->dependencies->configClass->assignContentVar();

        $views_path = $this->constant->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/';
        $this->context->controller->addJS($views_path . '/js/admin-v' . $this->dependencies->version . '.js');
        $this->context->controller->addJS($views_path . '/js/utilities-v' . $this->dependencies->version . '.js');
        $this->context->controller->addCSS($views_path . '/css/admin-v' . $this->dependencies->version . '.css');
        $this->context->controller->addJS($views_path . '/js/components-v' . $this->dependencies->version . '.js');

        $this->context->smarty->assign([
            'module_name' => $this->dependencies->name,
        ]);

        $this->content = $this->context->smarty->fetch($this->module->getLocalPath() . '/views/templates/admin/admin.tpl');

        parent::initContent();
    }

    public function setPsAccount()
    {
        try {
            // Install service if not done
            $this->module->getService('ps_accounts.installer')->install();

            // Account
            $accountsFacade = $this->module->getService('ps_accounts.facade');
            $accountsService = $accountsFacade->getPsAccountsService();
            $contextPsAccounts = $accountsFacade->getPsAccountsPresenter()->present($this->module->name);

            // update modal language
            $languages = ['es', 'de', 'pt', 'nl'];
            $isoCode = $this->context->language->iso_code;
            $isoCode = !in_array($isoCode, $languages) ? $isoCode : 'en';
            $contextPsAccounts['accountsUiUrl'] = $contextPsAccounts['accountsUiUrl'] . '/' . $isoCode . '/link-shop';

            $this->context->smarty->assign(['iso_user' => $isoCode]);
            Media::addJsDef(['contextPsAccounts' => $contextPsAccounts]);

            // Retrieve Account CDN
            $this->context->smarty->assign('urlAccountsCdn', $accountsService->getAccountsCdn());
        } catch (ModuleNotInstalledException $e) {
            $this->logger->addLog($e->getMessage(), 'error');
        } catch (ModuleVersionException $e) {
            $this->logger->addLog($e->getMessage(), 'error');
        }
    }
}
