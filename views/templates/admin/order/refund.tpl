{*
* 2023 Payplug
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
*  @author Payplug SAS
*  @copyright 2023 Payplug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of Payplug SAS
*}

<p><span class="ppbold">{l s='Refund your customer on his card directly with Payplug' mod='payplug'}</p>
<form method="post" action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="admin_ajax_url" value="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" />
    {if isset($pay_id)}
        <input type="hidden" name="pay_id" value="{$pay_id|escape:'htmlall':'UTF-8'}" />
    {elseif isset($inst_id)}
        <input type="hidden" name="inst_id" value="{$inst_id|escape:'htmlall':'UTF-8'}" />
    {/if}
    <input type="hidden" name="id_customer" value="{$order->id_customer|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="id_order" value="{$order->id|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="pay_mode" value="{$pay_mode|escape:'htmlall':'UTF-8'}" />

    <div class="pp_list">
        <ul>
            <li>
                {l s='Amount already refunded with Payplug : ' mod='payplug'}
                <span id="amount_refunded_payplug">{displayPrice price=$amount_refunded_payplug}</span>
            </li>
            <li>
                {l s='Amount still refundable with Payplug : ' mod='payplug'}
                <span id="amount_available">{displayPrice price=$amount_available}</span>
            </li>
        </ul>
    </div>

    <div class="form-group">
        <label class="control-label" for="pp_amount2refund">{l s='Amount to be refunded' mod='payplug'} ({$currency->name|escape:'htmlall':'UTF-8'}) :</label>
        <input type="text" name="pp_amount2refund" value="{$amount_suggested|escape:'htmlall':'UTF-8'}" />
        <label for="change_order_state">{l s='Change Prestashop order state to Refunded' mod='payplug'}</label>
        <input class="control-label" type="checkbox" value="{$id_new_order_state|escape:'htmlall':'UTF-8'}" name="change_order_state" >
    </div>

    <div class="form-group">
        {assign var='submitRefundButton' value=$module_name|cat:'SubmitRefund'}
        {include file='./button.tpl' button_disable=$refund_delay_oney || $refund_disable e2e_action='refund' submitName=$submitRefundButton submitValue={l s='Refund' mod='payplug'}}
        {if $refund_delay_oney}
            <div class="{$module_name|escape:'htmlall':'UTF-8'}Order_delay">{l s='The refund will be possible 48h after the last payment or refund transaction.' mod='payplug'}</div>
        {/if}
        {if $refund_disable}
            {capture assign="mailto_link"}{l s='admin.order.refund.mailto_link' mod='payplug'}{/capture}
            {assign var="contact_link" value="<a href='mailto:"|cat:$mailto_link|cat:"' target='_blank'>"}
            <div class="{$module_name|escape:'htmlall':'UTF-8'}Order_delay">{l s='admin.order.refund.disabled' tags=[$contact_link]  mod='payplug'}</div>
        {/if}
    </div>
</form>
