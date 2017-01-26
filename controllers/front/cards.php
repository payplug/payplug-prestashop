<?php
/**
 * 2013 - 2017 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2017 PayPlug SAS
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

class PayplugCardsModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        $this->auth = true;
        parent::__construct();

        $this->context = Context::getContext();

        include_once($this->module->getLocalPath().'payplug.php');
        include_once($this->module->getLocalPath().'lib/init.php');
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
        $valid_key = Payplug::setAPIKey();
        \Payplug\Payplug::setSecretKey($valid_key);

        $payplug = Module::getInstanceByName('payplug');

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
