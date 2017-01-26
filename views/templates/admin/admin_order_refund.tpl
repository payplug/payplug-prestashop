{*
* 2017 PayPlug
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
*  @author PayPlug SAS
*  @copyright 2017 PayPlug SAS
*  @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}

<p><span class="ppbold">{l s='Refund your customer on his card directly with Payplug' mod='payplug'}</p>
{*<p><span class="ppbold">{l s='Refund your customer on his card directly with Payplug' d='Modules.Payplug.Admin'}</p>*}
<form method="post" action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="admin_ajax_url" value="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="pay_id" value="{$pay_id|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="id_customer" value="{$order->id_customer|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="id_order" value="{$order->id|escape:'htmlall':'UTF-8'}" />
    <div class="pp_list">
        {include file='./admin_order_refund_data.tpl' amount_refunded_payplug=$amount_refunded_payplug amount_available=$amount_available}
    </div>
    <div class="form-group">
        <label class="control-label" for="pp_amount2refund">{l s='Amount to be refunded' mod='payplug'} ({$currency->name|escape:'htmlall':'UTF-8'}) :</label>
        {*<label class="control-label" for="pp_amount2refund">{l s='Amount to be refunded' d='Modules.Payplug.Admin'} ({$currency->name|escape:'htmlall':'UTF-8'}) :</label>*}
        <input type="text" name="pp_amount2refund" value="{$amount_suggested|escape:'htmlall':'UTF-8'}" />
        <label for="change_order_state">{l s='Change Prestashop order state to "Refunded"' mod='payplug'}</label>
        {*<label for="change_order_state">{l s='Change Prestashop order state to "Refunded"' d='Modules.Payplug.Admin'}</label>*}
        <input class="control-label" type="checkbox" value="{$id_new_order_state|escape:'htmlall':'UTF-8'}" name="change_order_state" >
    </div>
    <input class="btn green-button" type="submit" name="submitPPRefund" value="{l s='Refund' mod='payplug'}" >
    {*<input class="btn green-button" type="submit" name="submitPPRefund" value="{l s='Refund' d='Modules.Payplug.Admin'}" >*}
    <p class="hide pperror"></p>
    <p class="hide ppsuccess"></p>
    <img class="loader" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/admin/spinner.gif" />
</form>