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

class AdminPayplugController extends ModuleAdminController
{
    public function initProcess()
    {
        parent::initProcess();
        if ($this->display == null) {
            $this->display = 'edit';
        }
    }

    public function getContent()
    {
        $payplug = new \PayPlug\classes\PayPlugClass();
        if (Tools::getValue('_ajax') == 1) {
            (new \PayPlug\classes\AdminClass())->adminAjaxController();
        }

        $this->postProcess();

        if (Tools::getValue('uninstall_config') == 1) {
            return $this->getUninstallContent();
        }

        $this->html = '';

        $payplug->configClass->checkConfiguration();

        $PAYPLUG_EMAIL = Configuration::get('PAYPLUG_EMAIL');
        $PAYPLUG_TEST_API_KEY = Configuration::get('PAYPLUG_TEST_API_KEY');
        $PAYPLUG_LIVE_API_KEY = Configuration::get('PAYPLUG_LIVE_API_KEY');

        if (!empty($PAYPLUG_EMAIL) && (!empty($PAYPLUG_TEST_API_KEY) || !empty($PAYPLUG_LIVE_API_KEY))) {
            $connected = true;
        } else {
            $connected = false;
        }

        if (count($payplug->validationErrors && !$connected)) {
            $this->context->smarty->assign([
                'validationErrors' => $payplug->validationErrors,
            ]);
        }

        $p_error = '';
        if (!$connected) {
            if (isset($this->validationErrors['username_password'])) {
                $p_error .= $this->validationErrors['username_password'];
            } elseif (isset($this->validationErrors['login'])) {
                if (isset($this->validationErrors['username_password'])) {
                    $p_error .= ' ';
                }
                $p_error .= $this->validationErrors['login'];
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

        $payplug->configClass->assignContentVar();

        $this->html .= $payplug->mediaClass->fetchTemplateRC('/views/templates/admin/admin.tpl');

        return $this->html;
    }

    public function renderForm()
    {
        return $this->getContent();
    }
}
