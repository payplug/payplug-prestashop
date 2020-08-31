<?php
/**
 * 2013 - 2020 PayPlug SAS
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
 *  @copyright 2013 - 2020 PayPlug SAS
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
/*
    public function initContent()
    {
        return $this->getContent();
    }
*/
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
            $this->context->smarty->assign(array(
                'validationErrors' => $payplug->validationErrors,
            ));
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
            $this->context->smarty->assign(array(
                'p_error' => $p_error,
            ));
        } else {
            $this->context->smarty->assign(array(
                'PAYPLUG_EMAIL' => $PAYPLUG_EMAIL,
            ));
        }

        $payplug->addJsRC(__PS_BASE_URI__.'modules/payplug/views/js/admin.js');
        $payplug->addCSSRC(__PS_BASE_URI__.'modules/payplug/views/css/admin.css');

        //$admin_ajax_url = $this->context->link->getAdminLink('PayplugAjaxModuleAdminController', true, array());
        $admin_ajax_url = $payplug->getAdminAjaxUrl();

        $login_infos = array(
            //'p_error'	=> $p_error,
        );

        $this->context->smarty->assign(array(
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
        ));

        $this->html .= $payplug->fetchTemplateRC('/views/templates/admin/admin.tpl');

        return $this->html;
    }

    public function renderForm()
    {
        return $this->getContent();
    }
/*
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';

        parent::__construct();
        //$this->meta_title = $this->module->getTranslator()->trans('Settings', array(), 'Modules.Payplug.Admin');

        $this->name = 'AdminPayplug';
    }

    public function init()
    {
        /*
        if (Tools::isSubmit('edit'.$this->className)) {
            $this->display = 'edit';
        } elseif (Tools::isSubmit('addLinkBlock')) {
            $this->display = 'add';
        }

        parent::init();
        */
/*
        if (Tools::getValue('_ajax') == 1) {
            $this->adminAjaxController();
        }

        $this->postProcess();

        if (Tools::getValue('uninstall_config') == 1) {
            return $this->getUninstallContent();
        }

        $this->html = '';

        $this->checkConfiguration();

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

        if (count($this->validationErrors && !$connected)) {
            $this->context->smarty->assign(array(
                'validationErrors'	=> $this->validationErrors,
            ));
        }

        $valid_key = self::setAPIKey();
        if (!empty($valid_key)) {
            $permissions = $this->getAccount($valid_key);
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

        $this->site_url;

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
            $this->context->smarty->assign(array(
                'p_error'	=> $p_error,
            ));
        } else {
            $this->context->smarty->assign(array(
                'PAYPLUG_EMAIL'	=> $PAYPLUG_EMAIL,
            ));
        }

        $this->addJsRC(__PS_BASE_URI__.'modules/payplug/views/js/admin.js');
        $this->addCSSRC(__PS_BASE_URI__.'modules/payplug/views/css/admin.css');

        $admin_ajax_url = $this->context->link->getAdminLink('PayplugAjaxModuleAdminController', true, array());
        //$admin_ajax_url = $this->getAdminAjaxUrl();

        $login_infos = array(
            //'p_error'	=> $p_error,
        );

        $this->context->smarty->assign(array(
            'form_action' => (string)($_SERVER['REQUEST_URI']),
            'url_logo' => __PS_BASE_URI__.'modules/payplug/views/img/logo_payplug.png',
            'admin_ajax_url' => $admin_ajax_url,
            'check_configuration' => $this->check_configuration,
            'connected'	=> $connected,
            'verified'	=> $verified,
            'premium'	=> $premium,
            'is_active'	=> $is_active,
            'site_url' => $this->site_url,
            'PAYPLUG_SANDBOX_MODE' => $PAYPLUG_SANDBOX_MODE,
            'PAYPLUG_EMBEDDED_MODE' => $PAYPLUG_EMBEDDED_MODE,
            'PAYPLUG_ONE_CLICK' => $PAYPLUG_ONE_CLICK,
            'PAYPLUG_SHOW' => $PAYPLUG_SHOW,
            'PAYPLUG_DEBUG_MODE' => $PAYPLUG_DEBUG_MODE,
            'login_infos' => $login_infos,
        ));

        $this->html .= $this->fetchTemplateRC('/views/templates/admin/admin.tpl');

        return $this->html;
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submit'.$this->className)) {
            $this->addNameArrayToPost();

            if (!$this->processSave()) {
                return false;
            }

            $hook_name = Hook::getNameById(Tools::getValue('id_hook'));
            if (!Hook::isModuleRegisteredOnHook($this->module, $hook_name, $this->context->shop->id)) {
                Hook::registerHook($this->module, $hook_name);
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('Admin'.$this->name));
        } elseif (Tools::isSubmit('delete'.$this->className)) {
            $block = new LinkBlock(Tools::getValue('id_link_block'));
            $block->delete();

            if (!$this->repository->getCountByIdHook((int)$block->id_hook)) {
                Hook::unregisterHook($this->module, Hook::getNameById((int)$block->id_hook));
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('Admin'.$this->name));
        }

        return parent::postProcess();
    }

    public function renderView()
    {
        $title = $this->module->getTranslator()->trans('Link block configuration', array(), 'Modules.LinkList');

        $this->fields_form[]['form'] = array(
            'legend' => array(
                'title' => $title,
                'icon' => 'icon-list-alt'
            ),

        );

        $this->getLanguages();


        $helper = $this->buildHelper();
        $helper->submit_action = '';
        $helper->title = $title;

        $helper->fields_value = $this->fields_value;

        return $helper->generateForm($this->fields_form);
    }

    protected function buildHelper()
    {
        $helper = new HelperForm();

        $helper->module = $this->module;
        $helper->override_folder = 'linkwidget/';
        $helper->identifier = $this->className;
        $helper->token = Tools::getAdminTokenLite('Admin'.$this->name);
        $helper->languages = $this->_languages;
        $helper->currentIndex = $this->context->link->getAdminLink('Admin'.$this->name);
        $helper->default_form_language = $this->default_form_language;
        $helper->allow_employee_form_lang = $this->allow_employee_form_lang;
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn = $this->initToolbar();

        return $helper;
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title[] = $this->module->getTranslator()->trans('Themes', array(), 'Modules.LinkList');
        $this->toolbar_title[] = $this->module->getTranslator()->trans('Link Widget', array(), 'Modules.LinkList');
    }

    public function setMedia()
    {
        $this->addJqueryPlugin('tablednd');
        $this->addJS(_PS_JS_DIR_.'admin/dnd.js');

        return parent::setMedia();
    }

    private function addNameArrayToPost()
    {
        $languages = Language::getLanguages();
        $names = array();
        foreach ($languages as $lang) {
            if ($name = Tools::getValue('name_'.(int)$lang['id_lang'])) {
                $names[(int)$lang['id_lang']] = $name;
            }
        }
        $_POST['name_link_block'] = $names;
    }
*/
}
