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
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2022 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

include_once(_PS_MODULE_DIR_.'payplug/classes/DependenciesClass.php');

class AdminPayplugController extends ModuleAdminController
{
    private $dependencies;

    public function __construct()
    {
        $this->dependencies = new \PayPlug\classes\DependenciesClass();
    }

    public function initProcess()
    {
        parent::initProcess();
        if ($this->display == null) {
            $this->display = 'edit';
        }
    }

    public function getContent()
    {
        if (Tools::getValue('_ajax') == 1) {
            $this->dependencies->adminClass->adminAjaxController();
        }

        $this->postProcess();

        if (Tools::getValue('uninstall_config') == 1) {
            return $this->dependencies->configClass->getUninstallContent();
        }

        $this->html = '';

        $this->dependencies->configClass->checkConfiguration();

        $PAYPLUG_EMAIL = Configuration::get('PAYPLUG_EMAIL');
        $PAYPLUG_TEST_API_KEY = Configuration::get('PAYPLUG_TEST_API_KEY');
        $PAYPLUG_LIVE_API_KEY = Configuration::get('PAYPLUG_LIVE_API_KEY');

        if (!empty($PAYPLUG_EMAIL) && (!empty($PAYPLUG_TEST_API_KEY) || !empty($PAYPLUG_LIVE_API_KEY))) {
            $connected = true;
        } else {
            $connected = false;
        }

        if (count($this->dependencies->configClass->validationErrors && !$connected)) {
            $this->context->smarty->assign([
                'validationErrors' => $this->dependencies->configClass->validationErrors,
            ]);
        }

        $p_error = '';
        if (!$connected) {
            if (isset($this->dependencies->configClass->validationErrors['username_password'])) {
                $p_error .= $this->dependencies->configClass->validationErrors['username_password'];
            } elseif (isset($this->dependencies->configClass->validationErrors['login'])) {
                if (isset($this->dependencies->configClass->validationErrors['username_password'])) {
                    $p_error .= ' ';
                }
                $p_error .= $this->dependencies->configClass->validationErrors['login'];
            }
            $this->context->smarty->assign([
                'p_error' => $p_error,
            ]);
        } else {
            $this->context->smarty->assign([
                'PAYPLUG_EMAIL' => $PAYPLUG_EMAIL,
            ]);
        }

        $this->context->controller->addJS(__PS_BASE_URI__.'modules/payplug/views/js/admin.js');
        $this->context->controller->addCSS(__PS_BASE_URI__.'modules/payplug/views/css/admin.css');

        $this->dependencies->configClass->assignContentVar();

        $this->html .= $this->dependencies->configClass->fetchTemplate('/views/templates/admin/admin.tpl');

        return $this->html;
    }

    public function renderForm()
    {
        return $this->getContent();
    }
}
