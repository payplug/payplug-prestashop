{*
* 2019 PayPlug
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
*  @author PayPlug SAS
*  @copyright 2019 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
<p><span class="ppbold">{l s='Refund your customer on his card directly with Payplug' mod='payplug'}</p>
<form method="post" action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" class="pp-refund">
    <div class="pp_list">
        {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/admin_order_refund_data.tpl' amount_refunded_payplug=$amount_refunded_payplug amount_available=$amount_available}
    </div>
    <div class="form-group">
        <label class="control-label" for="pp_amount2refund">{l s='Amount to be refunded' mod='payplug'} ({$currency->name|escape:'htmlall':'UTF-8'}) :</label>
        <input type="text" name="pp_amount2refund" value="{$amount_suggested|escape:'htmlall':'UTF-8'}" /><br /><br />
        <label for="change_order_state">{l s='Change Prestashop order state to "Refunded"' mod='payplug'}</label>
        <input class="control-label" type="checkbox" value="{$id_new_order_state|escape:'htmlall':'UTF-8'}" name="change_order_state" >
    </div>
    <br>
    <div class="form-group">
        {if $refund_delay_oney}
            <input class="btn green-button button" type="submit" name="submitPPRefund" value="{l s='Refund' mod='payplug'}" disabled />
            <p class="oney_delay">{l s='The refund will be possible 48h after the last payment or refund transaction.' mod='payplug'}</p>
        {else}
            {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/button_with_loader.tpl' extraClass='green-button' submitName='submitPPRefund' submitValue={l s='Refund' mod='payplug'}}
        {/if}
    </div>
</form>
