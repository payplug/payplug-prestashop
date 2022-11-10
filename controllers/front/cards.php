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
 * @author    PayPlug SAS
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */
class PayplugCardsModuleFrontController extends ModuleFrontController
{
    private $card;
    private $contextAdapter;
    private $dependencies;
    private $plugin;

    public function __construct()
    {
        $this->auth = true;
        parent::__construct();

        include_once _PS_MODULE_DIR_ . 'payplug/classes/DependenciesClass.php';

        $this->dependencies = new \PayPlug\classes\DependenciesClass();

        $this->plugin = $this->dependencies->getPlugin();
        $this->card = $this->plugin->getCard();
        $this->contextAdapter = $this->plugin->getContext();

        include_once _PS_MODULE_DIR_ . 'payplug/payplug.php';
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
            'secretKey' => $this->dependencies->apiClass->current_api_key,
            'apiVersion' => $this->plugin->getApiVersion(),
        ]);

        $customer = $this->contextAdapter->getContext()->customer;
        $payplug_cards = $this->card->getByCustomer((int) $customer->id);
        $payplug_delete_card_url = $this->contextAdapter->getContext()->link->getModuleLink(
            'payplug',
            'ajax',
            ['_ajax' => 1],
            true
        );
        $this->contextAdapter->getContext()->smarty->assign([
            'payplug_cards' => $payplug_cards,
            'payplug_delete_card_url' => $payplug_delete_card_url,
        ]);
        $confirm_delete_message = $this->card->confirmDeleteCardMessage();
        $popup_confirm_delete_message = $this->dependencies->mediaClass->displayMessages(
            [$confirm_delete_message],
            false,
            true
        );
        $msg = $this->card->deleteCardMessage();
        $card_deleted_msg = $this->dependencies->mediaClass->displayMessages([$msg], true, false);
        Media::addJsDef(
            [
                $this->dependencies->name . '_delete_card_url' => $payplug_delete_card_url,
            ]
        );

        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $this->contextAdapter->getContext()->smarty->assign([
                'version' => 1.6,
            ]);
            $this->setTemplate('customer/cards_1_6.tpl');
        } else {
            Media::addJsDef(
                [
                    'card_confirm_deleted_msg' => $popup_confirm_delete_message,
                    'card_deleted_msg' => $card_deleted_msg,
                ]
            );

            $tpl = 'module:' . $this->dependencies->name . '/views/templates/front/customer/cards_list.tpl';
            $this->setTemplate($tpl);
        }
    }
}
