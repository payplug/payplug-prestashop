<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 *  @copyright 2013 - 2021 PayPlug SAS
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
        $payplug = new Payplug();
        if (Tools::getValue('_ajax') == 1) {
            $payplug->adminAjaxController();
        }

        $this->postProcess();

        if (Tools::getValue('uninstall_config') == 1) {
            return $this->getUninstallContent();
        }

        $this->html = '';

        $payplug->checkConfiguration();

        $PAYPLUG_SHOW = Configuration::get('PAYPLUG_SHOW');
        $PAYPLUG_EMAIL = Configuration::get('PAYPLUG_EMAIL');
        $PAYPLUG_SANDBOX_MODE = Configuration::get('PAYPLUG_SANDBOX_MODE');
        $PAYPLUG_EMBEDDED_MODE = Configuration::get('PAYPLUG_EMBEDDED_MODE');
        $PAYPLUG_ONE_CLICK = Configuration::get('PAYPLUG_ONE_CLICK');
        $PAYPLUG_TEST_API_KEY = Configuration::get('PAYPLUG_TEST_API_KEY');
        $PAYPLUG_LIVE_API_KEY = Configuration::get('PAYPLUG_LIVE_API_KEY');
        $PAYPLUG_DEBUG_MODE = Configuration::get('PAYPLUG_DEBUG_MODE');

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

        $valid_key = Payplug::setAPIKey();
        if (!empty($valid_key)) {
            $permissions = $payplug->getAccount($valid_key);
            $premium = $permissions['can_save_cards'];
        } else {
            $verified = false;
            $premium = false;
        }
        if (!empty($PAYPLUG_LIVE_API_KEY)) {
            $verified = true;
        } else {
            $verified = false;
        }

        $is_active = (!empty($PAYPLUG_SHOW) && $PAYPLUG_SHOW == 1) ? true : false;

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

        $payplug->addJsRC(__PS_BASE_URI__.'modules/payplug/views/js/admin-v3.1.2.js');
        $payplug->addCSSRC(__PS_BASE_URI__.'modules/payplug/views/css/admin-v3.1.2.css');

        $admin_ajax_url = $payplug->getAdminAjaxUrl();

        $login_infos = [];

        $this->context->smarty->assign([
            'form_action' => (string)($_SERVER['REQUEST_URI']),
            'url_logo' => __PS_BASE_URI__.'modules/payplug/views/img/logo_payplug.png',
            'admin_ajax_url' => $admin_ajax_url,
            'check_configuration' => $payplug->check_configuration,
            'pp_version' => $payplug->version,
            'connected' => $connected,
            'verified' => $verified,
            'premium' => $premium,
            'is_active' => $is_active,
            'site_url' => $payplug->site_url,
            'PAYPLUG_SANDBOX_MODE' => $PAYPLUG_SANDBOX_MODE,
            'PAYPLUG_EMBEDDED_MODE' => $PAYPLUG_EMBEDDED_MODE,
            'PAYPLUG_ONE_CLICK' => $PAYPLUG_ONE_CLICK,
            'PAYPLUG_SHOW' => $PAYPLUG_SHOW,
            'PAYPLUG_DEBUG_MODE' => $PAYPLUG_DEBUG_MODE,
            'login_infos' => $login_infos,
        ]);

        $this->html .= $payplug->fetchTemplateRC('/views/templates/admin/admin.tpl');

        return $this->html;
    }

    public function renderForm()
    {
        return $this->getContent();
    }
}
