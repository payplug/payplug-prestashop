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

require_once(dirname(__FILE__) . '/../../vendor/autoload.php');

use PayPlugModule\classes\DependenciesClass;

class AdminPayPlugController extends ModuleAdminController
{
    private $dependencies;

    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();

        $this->dependencies = new DependenciesClass();
    }

    /**
     * Initialize the content by adding Boostrap and loading the TPL
     *
     * @return void
     */
    public function initContent()
    {
        if (Tools::getValue('_ajax')) {
            $this->dependencies->adminClass->adminAjaxController();
        }

        $this->dependencies->configClass->postProcess();
        $this->dependencies->configClass->assignContentVar();

        $this->context->smarty->assign([
            'module_name' => $this->dependencies->name
        ]);

        $this->setTemplate('admin.tpl');
    }
}
