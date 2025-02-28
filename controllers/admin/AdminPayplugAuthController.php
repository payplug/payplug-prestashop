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
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 *  @author    Payplug SAS
 *  @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */
require_once dirname(__FILE__) . '/../../vendor/autoload.php';

use PayPlug\classes\DependenciesClass;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminPayplugAuthController extends ModuleAdminController
{
    private $dependencies;
    private $constant;
    private $media;

    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();

        $this->dependencies = new DependenciesClass();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->media = $this->dependencies->getPlugin()->getMedia();
    }

    /**
     * Initialize the content by adding Boostrap and loading the TPL.
     */
    public function initContent()
    {
        $this->context->smarty->assign([
            'module_name' => $this->dependencies->name,
        ]);

        $lib_path = $this->constant->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/';
        $this->media->addJsDef([
            'payplug_admin_config' => [
                'ajax_url' => $this->dependencies->adminClass->getAdminAjaxUrl() . '&_ajax=1&oauth2=true',
                'img_path' => $lib_path,
            ],
        ]);

        $this->context->smarty->assign([
            'lib_url' => $this->context->shop->getBaseURL(true) . 'modules/' . $this->dependencies->name . '/views/',
        ]);

        $this->context->controller->addCSS($lib_path . '/css/app.css');

        $this->content = $this->context->smarty->fetch($this->module->getLocalPath() . '/views/templates/admin/admin.tpl');

        parent::initContent();
    }
}
