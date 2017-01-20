<?php
/**
 * 2013 - 2016 PayPlug SAS
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
 *  @copyright 2013 - 2016 PayPlug SAS
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

require_once(dirname(__FILE__).'./../../../../config/config.inc.php');
require_once(_PS_MODULE_DIR_.'../init.php');
require_once(_PS_MODULE_DIR_.'/payplug/classes/PayplugTools.php');
require_once(_PS_MODULE_DIR_.'/payplug/classes/PayplugBackward.php');
require_once(_PS_MODULE_DIR_.'/payplug/payplug.php');
require_once(_PS_MODULE_DIR_.'/payplug/lib/init.php');

$valid_key = Payplug::setAPIKey();
\Payplug\Payplug::setSecretKey($valid_key);

include(_PS_MODULE_DIR_.'../header.php');

$payplug = Module::getInstanceByName('payplug');

if (version_compare(_PS_VERSION_, '1.5', '<')) {
    $context = $payplug->context;
} else {
    $context = Context::getContext();
}
$customer = $context->customer;
$payplug_cards = $payplug->getCardsByCustomer($customer->id);
$payplug_delete_card_url = PayplugBackward::getHttpHost(true).__PS_BASE_URI__
    .'modules/payplug/controllers/front/FrontAjaxPayplug.php?_ajax=1';

$context->smarty->assign(array(
    'payplug_cards' => $payplug_cards,
    'payplug_delete_card_url' => $payplug_delete_card_url
));
echo $payplug->display('payplug', '/views/templates/front/cards_list.tpl');

include(_PS_MODULE_DIR_.'../footer.php');
