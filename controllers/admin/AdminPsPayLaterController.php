<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS
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
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */ require_once dirname(__FILE__) . '/../../vendor/autoload.php';

use PayLaterModule\classes\DependenciesClass;

class AdminPsPayLaterController extends ModuleAdminController
{
    public $module;

    private $api_rest;
    private $constant;
    private $dependencies;
    private $logger;
    private $media;
    private $tools;

    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();

        $this->dependencies = new DependenciesClass();
        $this->api_rest = $this->dependencies->getPlugin()->getApiRest();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->logger = $this->dependencies->getPlugin()->getLogger();
        $this->media = $this->dependencies->getPlugin()->getMedia();
        $this->module = $this->dependencies->getPlugin()->getModule()->getInstanceByName($this->dependencies->name);
        $this->tools = $this->dependencies->getPlugin()->getTools();

        // If referer is from development server, trigger api rest renderer
        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'localhost') != null) {
            $this->renderApiRest();
        }
    }

    /**
     * Initialize the content by adding Boostrap and loading the TPL
     */
    public function initContent()
    {
        if ($this->module->name == 'pspaylater') {
            $this->setPsAccount();
        }

        $this->renderApiRest();

        $this->context->smarty->assign([
            'module_name' => $this->dependencies->name,
            'ps_account_isActivated' => $this->dependencies->configClass->isValidFeature('feature_ps_account'),
            'pp_version' => $this->dependencies->version,
        ]);

        $this->media->addJsDef([
            'payplug_admin_config' => [
                'ajax_url' => $this->dependencies->adminClass->getAdminAjaxUrl() . '&_ajax=1',
                'img_path' => $this->constant->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/dist/',
            ],
        ]);

        $this->context->smarty->assign([
            'lib_url' => $this->context->shop->getBaseURL(true) . 'modules/' . $this->dependencies->name . '/dist/',
        ]);

        $this->content = $this->context->smarty->fetch($this->module->getLocalPath() . '/views/templates/admin/admin_lib.tpl');

        parent::initContent();
    }

    /**
     * @description Render Api Rest Json
     */
    public function renderApiRest()
    {
        if ($rest_route = $this->tools->tool('getValue', 'rest_route')) {
            $json = $this->api_rest->dispatch($rest_route);
            exit(json_encode($json));
        }
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
            $this->logger->addLog(($e->getMessage() ? $e->getMessage() : 'ModuleNotInstalledException'), 'error');
        } catch (ModuleVersionException $e) {
            $this->logger->addLog(($e->getMessage() ? $e->getMessage() : 'ModuleVersionException'), 'error');
        }
    }
}
