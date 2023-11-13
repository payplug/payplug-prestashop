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
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

use PayPlug\classes\DependenciesClass;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PayplugCardsModuleFrontController extends ModuleFrontController
{
    private $dependencies;
    private $card_action;
    private $context_adapter;
    private $media;
    private $tools;

    public function __construct()
    {
        $this->auth = true;

        parent::__construct();

        $this->dependencies = new DependenciesClass();
        $this->card_action = $this->dependencies->getPlugin()->getCardAction();
        $this->context_adapter = $this->dependencies->getPlugin()->getContext();
        $this->media = $this->dependencies->getPlugin()->getMedia();
        $this->tools = $this->dependencies->getPlugin()->getTools();
    }

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        $this->display_column_left = false;
        parent::initContent();

        if ('cardlist' == $this->tools->tool('getValue', 'process')) {
            $this->renderCardList();
        }
    }

    private function renderCardList()
    {
        $cards = $this->card_action->renderList();
        $payplug_delete_card_url = $this->context_adapter->getContext()->link->getModuleLink(
            'payplug',
            'ajax',
            ['_ajax' => 1],
            true
        );
        $this->context_adapter->getContext()->smarty->assign([
            'payplug_cards' => $cards,
            'payplug_delete_card_url' => $payplug_delete_card_url,
        ]);
        $translations = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getCardTranslation();

        $card_confirm_deleted_msg = $this->dependencies->mediaClass->displayMessages(
            [$translations['delete']['confirm']],
            false,
            true
        );
        $card_deleted_msg = $this->dependencies->mediaClass->displayMessages(
            [$translations['delete']['success']],
            true,
            false
        );
        $this->media->addJsDef(
            [
                $this->dependencies->name . '_delete_card_url' => $payplug_delete_card_url,
            ]
        );
        $this->media->addJsDef(
            [
                'card_confirm_deleted_msg' => $card_confirm_deleted_msg,
                'card_deleted_msg' => $card_deleted_msg,
            ],
            true
        );

        $this->setTemplate('module:' . $this->dependencies->name . '/views/templates/front/customer/cards_list.tpl');
    }
}
