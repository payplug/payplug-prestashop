<?php

namespace PayPlug\src\repositories;

class InstallRepository extends Repository
{
    /**
     * @return bool
     * @throws Exception
     * @see Module::install()
     *
     */
    public function install()
    {
        $log = new Payplug\classes\MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
        $log->info('Starting to install.');
        $install = [
            'flag' => true,
            'error' => false
        ];

        $report = $this->checkRequirements();
        if (!$report['php']['up2date'] && $install['flag']) {
            $this->_errors[] = Tools::displayError($this->l('Your server must run PHP 5.3 or greater'));
            $log->error('Install failed: PHP Requirement.');
            $install['flag'] = false;
            $install['error'] = 'Configuration PHP inf. version 5.3';
        } else {
            $log->info('Install success: PHP Requirement.');
        }

        if (!$report['curl']['up2date'] && $install['flag']) {
            $this->_errors[] = Tools::displayError($this->l('PHP cURL extension must be enabled on your server'));
            $log->error('Install failed: cURL Requirement.');
            $install['flag'] = false;
            $install['error'] = 'cURL Requirement';
        } else {
            $log->info('Install success: cURL Requirement.');
        }

        if (!$report['openssl']['up2date'] && $install['flag']) {
            $this->_errors[] = Tools::displayError($this->l('OpenSSL 1.0.1 or later'));
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
            if (!$this->registerHook($hookToRegister) && $install['flag']) {
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
            $installHook16 = $this->installHook();
            $install['flag'] = $installHook16['flag'];
            $install['error'] = $installHook16['error'];
        }
        $log->info('----------------> Install hooks: ' . ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        $log->info('----------------> Install configuration. <----------------');
        if (!$this->createConfig() && $install['flag']) {
            $log->error('Install failed: configuration.');
            $install['flag'] = false;
            $install['error'] = 'Création des éléments de configuration  ($this->createConfig)';
        }
        $log->info('----------------> Install configuration: ' .
            ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        $log->info('----------------> Install order states. <----------------');

        if (!$this->createOrderStates() && $install['flag']) {
            $log->error('Install failed: order states.');
            $install['flag'] = false;
        }
        $log->info('----------------> Install order states: ' .
            ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        $log->info('----------------> Install SQL. <----------------');
        if (!(new PayPlug\src\repositories\SQLtableRepository())->installSQL() /*&& $install['flag']*/) {
            $log->error('Install failed: SQL.');
            $install['flag'] = false;
            $install['error'] = 'Création des tables SQL';
        }
        $log->info('----------------> Install SQL: ' . ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        $log->info('----------------> Install tab. <----------------');
        if (!$this->installTab() && $install['flag']) {
            $log->error('Install failed: tab.');
            $install['flag'] = false;
            $install['error'] = 'Onglet comprenant les détails des échéances des Paiements Fractionnés (back office)';
        }
        $log->info('----------------> Install tab: ' . ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        $log->info('----------------> Install Oney. <----------------');
        if (!$this->oney->installOney() && $install['flag']) {
            $log->error('Install failed: Oney.');
            $install['flag'] = false;
            $install['error'] = 'Oney ($this->installOney)';
        }
        $log->info('----------------> Install Oney: ' . ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        if ($install['flag']) {
            $log->info('Install succeeded.');
            return true;
        }

        $log->info('Install failed.');
        $log->info('Install error: ' . $install['error']);

        // revert installation
        $this->uninstall();
        $install['error'] = (isset($install['error'])) ? 'Élément en cause : ' . $install['error'] : '';
        $this->context->controller->errors[] = $this->l('Le module PayPlug n\'a pas été installé 
        en raison d\'une erreur. Les modifications apportées ont bien été annulées. ' . $install['error']);
        return false;
    }

    /**
     * @param $tabClass
     * @param $translations
     * @param $idTabParent
     * @param null $moduleName
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installModuleTab($tabClass, $translations, $idTabParent, $moduleName = null)
    {
        $tab = new Tab();

        foreach (Language::getLanguages(false) as $language) {
            if (isset($translations[Tools::strtolower($language['iso_code'])])) {
                $tab->name[(int)$language['id_lang']] = $translations[Tools::strtolower($language['iso_code'])];
            } else {
                $tab->name[(int)$language['id_lang']] = $translations['en'];
            }
        }

        $tab->class_name = $tabClass;
        if (is_null($moduleName)) {
            $moduleName = $this->name;
        }

        $tab->module = $moduleName;
        $tab->id_parent = $idTabParent;

        if (!$tab->save()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installTab()
    {
        if ((class_exists($this->PrestashopSpecificClass))
            && (method_exists($this->PrestashopSpecificObject, 'installTab'))) {
            return $this->PrestashopSpecificObject->installTab();
        }

        $install = [];

        $translationsAdminPayPlug = [
            'en' => 'PayPlug',
            'gb' => 'PayPlug',
            'it' => 'PayPlug',
            'fr' => 'PayPlug'
        ];
        $install['flag'] = $this->installModuleTab('AdminPayPlug', $translationsAdminPayPlug, 0);

        $translationsAdminPayPlugInstallment = [
            'en' => 'Installment Plans',
            'gb' => 'Installment Plans',
            'it' => 'Pagamenti frazionati',
            'fr' => 'Paiements en plusieurs fois'
        ];

        $adminPayPlugId = Tab::getIdFromClassName('AdminPayPlug');
        $install['flag'] = $install['flag']
            && $this->installModuleTab(
                'AdminPayPlugInstallment',
                $translationsAdminPayPlugInstallment,
                $adminPayPlugId,
                $this->name
            );

        return $install['flag'];
    }

    /**
     * @description Uninstall plugin
     *
     * @return bool
     * @throws Exception
     * @see Module::uninstall()
     */
    public function uninstall()
    {
        $log = new Payplug\classes\MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
        $log->info('Starting to uninstall.');

        $keep_cards = (bool)Configuration::get('PAYPLUG_KEEP_CARDS');
        if (!$keep_cards) {
            $log->info('Saved cards will be deleted.');
            if (!$this->uninstallCards()) {
                $log->error('Unable to delete saved cards.');
            } else {
                $log->info('Saved cards successfully deleted.');
            }
        } else {
            $log->info('Cards will be kept.');
        }

        if (!parent::uninstall()) {
            $log->error('Uninstall failed: parent.');
        } elseif (!$this->deleteConfig()) {
            $log->error('Uninstall failed: configuration.');
        } elseif (!(new PayPlug\src\repositories\SQLtableRepository())->uninstallSQL($keep_cards)) {
            $log->error('Uninstall failed: sql.');
        } elseif (!$this->uninstallTab()) {
            $log->error('Uninstall failed: tab.');
        } elseif (!$this->oney->uninstallOney()) {
            $log->error('Uninstall failed: Oney.');
        } else {
            $log->info('Uninstall succeeded.');
            return true;
        }
        return false;
    }

    /**
     * @description Delete saved cards when uninstalling module
     *
     * @return bool
     * @throws Exception
     */
    private function uninstallCards()
    {
        $test_api_key = Configuration::get('PAYPLUG_TEST_API_KEY');
        $live_api_key = Configuration::get('PAYPLUG_LIVE_API_KEY');

        $req_all_cards = new DbQuery();
        $req_all_cards->select('pc.*');
        $req_all_cards->from('payplug_card', 'pc');
        $res_all_cards = Db::getInstance()->executeS($req_all_cards);

        if (!empty($res_all_cards)) {
            foreach ($res_all_cards as $card) {
                $id_customer = $card['id_customer'];
                $id_payplug_card = $card['id_payplug_card'];
                $api_key = $card['is_sandbox'] == 1 ? $test_api_key : $live_api_key;
                if (!$this->card->deleteCard($id_customer, $id_payplug_card, $api_key)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @description Uninstall module installment tab
     *
     * @param $tabClass
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstallModuleTab($tabClass)
    {
        //$tabRepository = $this->get('prestashop.core.admin.tab.repository');
        //$idTab = $tabRepository->findOneIdByClassName($tabClass);
        //deprecated but without any retro-compatibility solution... thx Prestashop
        $idTab = Tab::getIdFromClassName($tabClass);
        if ($idTab != 0) {
            $tab = new Tab($idTab);
            $tab->delete();
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstallTab()
    {
        if ((class_exists($this->PrestashopSpecificClass))
            && (method_exists($this->PrestashopSpecificObject, 'uninstallTab'))) {
            return $this->PrestashopSpecificObject->uninstallTab();
        }
        return ($this->uninstallModuleTab('AdminPayPlug')
            && $this->uninstallModuleTab('AdminPayPlugInstallment'));
    }

    /**
     * @return string
     */
    private function getUninstallContent()
    {
        $this->postProcess();
        $this->html = '';

        $PAYPLUG_KEEP_CARDS = (int)Configuration::get('PAYPLUG_KEEP_CARDS');

        $this->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/admin.js');
        $this->addCSSRC(__PS_BASE_URI__ . 'modules/payplug/views/css/admin.css');

        $this->context->smarty->assign([
            'form_action' => (string)($_SERVER['REQUEST_URI']),
            'url_logo' => __PS_BASE_URI__ . 'modules/payplug/views/img/logo_payplug.png',
            'site_url' => $this->site_url,
            'PAYPLUG_KEEP_CARDS' => $PAYPLUG_KEEP_CARDS,
        ]);

        $this->html .= $this->fetchTemplateRC('/views/templates/admin/admin_uninstall_configuration.tpl');

        return $this->html;
    }
}
