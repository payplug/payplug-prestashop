<?php


namespace PayPlug\src\repositories;


class InstallRepository extends \Payplug
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
        $report = $this->checkRequirements();
        if (!$report['php']['up2date']) {
            $this->_errors[] = Tools::displayError($this->l('Your server must run PHP 5.3 or greater'));
            $log->error('Install failed: PHP Requirement.');
        }
        if (!$report['curl']['up2date']) {
            $this->_errors[] = Tools::displayError($this->l('PHP cURL extension must be enabled on your server'));
            $log->error('Install failed: cURL Requirement.');
        }
        if (!$report['openssl']['up2date']) {
            $this->_errors[] = Tools::displayError($this->l('OpenSSL 1.0.1 or later'));
            $log->error('Install failed: OpenSSL Requirement.');
        }

        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install()) {
            $log->error('Install failed: parent.');
        } elseif (!$this->registerHook('paymentReturn') ||
            !$this->registerHook('header') ||
            !$this->registerHook('adminOrder') ||
            !$this->registerHook('actionOrderStatusUpdate') ||
            !$this->registerHook('customerAccount')
        ) {
            $log->error('Install failed: classics hooks.');
        } elseif (!$this->registerHook('paymentOptions')) {
            $log->error('Install failed: hook paymentOptions.');
        } elseif (!$this->registerHook('Payment')) {
            $log->error('Install failed: hook Payment.');
        } elseif (!$this->registerHook('moduleRoutes')) {
            $log->error('Install failed: hook moduleRoutes.');
        } elseif (!$this->registerHook('registerGDPRConsent') ||
            !$this->registerHook('actionDeleteGDPRCustomer') ||
            !$this->registerHook('actionExportGDPRData')
        ) {
            $log->error('Install failed: hooks GDPR.');
        } elseif (!$this->createConfig()) {
            $log->error('Install failed: configuration.');
        } elseif (!$this->createOrderStates()) {
            $log->error('Install failed: order states.');
        } elseif (!$this->installSQL()) {
            $log->error('Install failed: sql.');
        } elseif (!$this->installTab()) {
            $log->error('Install failed: tab.');
        } elseif (!$this->installOney()) {
            $log->error('Install failed: Oney.');
        } else {
            $log->info('Install succeeded.');
            return true;
        }

        //install hook 1.6
        $this->installHook();

        return false;
    }

    /**
     * Install the required hooks
     * @return bool
     */
    private function installHook()
    {
        $hooks = array(
            'adminOrder',
            'customerAccount',
            'header',
            'paymentReturn',
            'actionAdminPerformanceControllerAfter',
            'moduleRoutes'
        );

        $flag = true;
        foreach ($hooks as $hook) {
            $flag = $this->registerHook($hook) && $flag;
        }

        return $flag;
    }

    /**
     * Install SQL tables used by module
     *
     * @return bool
     */
    private function installSQL()
    {
        $log = new Payplug\classes\MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
        $log->info('Installation SQL Starting.');

        if (!defined('_MYSQL_ENGINE_')) {
            define('_MYSQL_ENGINE_', 'InnoDB');
        }

        $this->query
            ->create()
            ->table(_DB_PREFIX_.'payplug_lock')
            ->fields('`id_payplug_lock` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`id_cart` INT(11) UNSIGNED NOT NULL')
            ->fields('`id_order` VARCHAR(100)')
            ->fields('`date_add` DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\'')
            ->fields('`date_upd` DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\'')
            ->condition('CONSTRAINT lock_cart_unique UNIQUE (id_cart)')
        ;

        $req_payplug_lock = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_lock` (
            `id_payplug_lock` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_cart` INT(11) UNSIGNED NOT NULL,
            `id_order` VARCHAR(100),
            `date_add` DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\',
            `date_upd` DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\',
            CONSTRAINT lock_cart_unique UNIQUE (id_cart)
            ) ENGINE=' . _MYSQL_ENGINE_;
        $res_payplug_lock = DB::getInstance()->Execute($req_payplug_lock);

        if (!$res_payplug_lock) {
            $log->error('Installation SQL failed: PAYPLUG_LOCK.');
            return false;
        }

        $req_payplug_card = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_card` (
            `id_payplug_card` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_customer` int(11) UNSIGNED NOT NULL,
            `id_company` int(11) UNSIGNED NOT NULL,
            `is_sandbox` int(1) UNSIGNED NOT NULL,
            `id_card` varchar(255) NOT NULL,
            `last4` varchar(4) NOT NULL,
            `exp_month` varchar(4) NOT NULL,
            `exp_year` varchar(4) NOT NULL,
            `brand` varchar(255) DEFAULT NULL,
            `country` varchar(3) NOT NULL,
            `metadata` varchar(255) DEFAULT NULL
            ) ENGINE=' . _MYSQL_ENGINE_;
        $res_payplug_card = DB::getInstance()->Execute($req_payplug_card);

        if (!$res_payplug_card) {
            $log->error('Installation SQL failed: PAYPLUG_CARD.');
            return false;
        }

        $req_payplug_payment_cart = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_payment_cart` (
            `id_payplug_payment_cart` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_payment` VARCHAR(255) NOT NULL,
            `id_cart` INT(11) UNSIGNED NOT NULL,
            `is_pending` TINYINT(1) NOT NULL DEFAULT 0, 
            `date_upd` DATETIME NULL
            ) ENGINE=' . _MYSQL_ENGINE_;
        $res_payplug_payment_cart = DB::getInstance()->Execute($req_payplug_payment_cart);

        if (!$res_payplug_payment_cart) {
            $log->error('Installation SQL failed: PAYPLUG_PAYMENT_CART.');
            return false;
        }

        $req_payplug_installment_cart = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_installment_cart` (
            `id_payplug_installment_cart` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_installment` VARCHAR(255) NOT NULL,
            `id_cart` INT(11) UNSIGNED NOT NULL,
            `is_pending` TINYINT(1) NOT NULL DEFAULT 0, 
            `date_upd` DATETIME NULL
            ) ENGINE=' . _MYSQL_ENGINE_;
        $res_payplug_installment_cart = DB::getInstance()->Execute($req_payplug_installment_cart);

        if (!$res_payplug_installment_cart) {
            $log->error('Installation SQL failed: PAYPLUG_INSTALLMENT_CART.');
            return false;
        }

        $req_payplug_installment = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_installment` (
            `id_payplug_installment` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_installment` VARCHAR(255) NOT NULL,
            `id_payment` VARCHAR(255) NULL,
            `id_order` INT(11) UNSIGNED NOT NULL,
            `id_customer` INT(11) UNSIGNED NOT NULL,
            `order_total` INT(11) UNSIGNED NOT NULL,
            `step` VARCHAR(11) NOT NULL,
            `amount` INT(11) UNSIGNED NOT NULL,
            `status` INT(11) UNSIGNED NOT NULL,
            `scheduled_date` DATETIME NOT NULL
            ) ENGINE=' . _MYSQL_ENGINE_;
        $res_payplug_installment = DB::getInstance()->Execute($req_payplug_installment);

        if (!$res_payplug_installment) {
            $log->error('Installation SQL failed: PAYPLUG_INSTALLMENTS.');
            return false;
        }

        // install table `payplug_logger`
        $req_payplug_logger = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_logger` (
            `id_payplug_logger` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `process` VARCHAR(255) NOT NULL,
            `content` TEXT NOT NULL,
            `date_add` DATETIME NULL,
            `date_upd` DATETIME NULL
            ) ENGINE=' . _MYSQL_ENGINE_;

        $res_payplug_logger = Db::getInstance()->execute($req_payplug_logger);

        if (!$res_payplug_logger) {
            $log->error('Installation SQL failed: PAYPLUG_LOGGERS.');
            return false;
        }

        // install table `payplug_cache`
        $req_payplug_cache = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_cache` (
            `id_payplug_cache` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `cache_key` VARCHAR(255) NOT NULL,
            `cache_value` TEXT NOT NULL,
            `date_add` DATETIME NULL,
            `date_upd` DATETIME NULL
            ) ENGINE=' . _MYSQL_ENGINE_;

        $res_payplug_cache = Db::getInstance()->execute($req_payplug_cache);

        if (!$res_payplug_cache) {
            $log->error('Installation SQL failed: PAYPLUG_CACHE.');
            return false;
        }

        $req_payplug_order_payment = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_order_payment` (
            `id_payplug_order_payment` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_order` INT(11) UNSIGNED NOT NULL,
            `id_payment` VARCHAR(255) NOT NULL
            ) ENGINE=' . _MYSQL_ENGINE_;

        $res_payplug_order_payment = Db::getInstance()->execute($req_payplug_order_payment);

        if (!$res_payplug_order_payment) {
            $log->error('Installation SQL failed: PAYPLUG_ORDER_PAYMENT.');
            return false;
        }

        $log->info('Installation SQL ended.');
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

        $translationsAdminPayPlug = array(
            'en' => 'PayPlug',
            'gb' => 'PayPlug',
            'it' => 'PayPlug',
            'fr' => 'PayPlug'
        );
        $flag = $this->installModuleTab('AdminPayPlug', $translationsAdminPayPlug, 0);

        $translationsAdminPayPlugInstallment = array(
            'en' => 'Installment Plans',
            'gb' => 'Installment Plans',
            'it' => 'Pagamenti frazionati',
            'fr' => 'Paiements en plusieurs fois'
        );

        $adminPayPlugId = Tab::getIdFromClassName('AdminPayPlug');
        $flag = ($flag && $this->installModuleTab('AdminPayPlugInstallment', $translationsAdminPayPlugInstallment,
                $adminPayPlugId, $this->name));

        return $flag;
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

}