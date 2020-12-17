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
    private $card;
    private $contextSpecific;
    private $payplug;
    private $plugin;

    public function __construct()
    {
        $this->auth = true;
        parent::__construct();

        $this->payplug = new \Payplug();
        $this->plugin = $this->payplug->getPlugin();
        $this->card = $this->plugin->getCard();
        $this->contextSpecific = $this->plugin->getContext();

        include_once($this->module->getLocalPath() . 'payplug.php');
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
        \Payplug\Payplug::init([
            'secretKey' => $this->payplug->current_api_key,
            'apiVersion' => $this->plugin->getApiVersion()
        ]);

        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $payplug_cards = $this->card->getByCustomer($this->contextSpecific->getContext()->customer);
        } else {
            $customer = $this->contextSpecific->getContext()->customer;
            $payplug_cards = $this->card->getCardsByCustomer($customer->id);
        }

        $payplug_delete_card_url = $this->contextSpecific->getContext()->link->getModuleLink(
            'payplug',
            'ajax',
            ['_ajax' => 1],
            true
        );
        $this->contextSpecific->getContext()->smarty->assign([
            'payplug_cards' => $payplug_cards,
            'payplug_delete_card_url' => $payplug_delete_card_url
        ]);

        $card_deleted_msg = $this->payplug->displayPaymentErrors([$this->card->deleteCardMessage()]);
        Media::addJsDef(['card_deleted_msg' => $card_deleted_msg]);

        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $this->contextSpecific->getContext()->smarty->assign([
                'version' => 1.6,
            ]);
            $this->setTemplate('customer/cards_1_6.tpl');
        } else {
            $this->setTemplate('module:payplug/views/templates/front/customer/cards_list.tpl');
        }
    }
}
