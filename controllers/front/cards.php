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
 * @author    PayPlug SAS
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

class PayplugCardsModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        $this->auth = true;
        parent::__construct();

        $this->context = Context::getContext();

        include_once($this->module->getLocalPath() . 'payplug.php');
        include_once($this->module->getLocalPath() . 'lib/init.php');
    }

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        $this->display_column_left = false;
        parent::initContent();

        if (Tools::getValue('process') == 'cardlist') {
            $this->renderCardList();
        }
    }

    public function renderCardList()
    {
        $payplug = Module::getInstanceByName('payplug');
        \Payplug\Payplug::init([
            'secretKey' => $payplug->current_api_key,
            'apiVersion' => $payplug->api_version
        ]);

        $context = Context::getContext();

        $customer = $context->customer;
        $payplug_cards = $payplug->getCardsByCustomer($customer->id);

        $payplug_delete_card_url = $this->context->link->getModuleLink('payplug', 'ajax', array('_ajax' => 1), true);
        $this->context->smarty->assign(array(
            'payplug_cards' => $payplug_cards,
            'payplug_delete_card_url' => $payplug_delete_card_url
        ));

        $this->setTemplate('module:payplug/views/templates/front/cards_list.tpl');
    }
}
