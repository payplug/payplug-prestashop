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

<div>
    <p class="{if $installment.is_paid}ppinstsucces{else}{if !$installment.is_active}ppinsterror{else}ppwarning{/if}{/if}">
        {l s='This order is subjected to an installment plan, whose status is' mod='payplug'}
        <span class="pp_inst_status" data-e2e-payment-details="inst_status" data-e2e-payment-details-inst-state="{$installment.status_code|escape:'htmlall':'UTF-8'}">{$installment.status|escape:'htmlall':'UTF-8'}</span></p>
    <p>{l s='Payment schedule ID' mod='payplug'} : <span data-e2e-payment-details="inst_id">{$installment.id|escape:'htmlall':'UTF-8'}</span></p>
</div>
<div class="table-responsive half-width">
    <table class="table">
        <thead>
        <tr>
            <th><span class="title_box ">{l s='Date' mod='payplug'}</span></th>
            <th><span class="title_box ">{l s='Amount' mod='payplug'}</span></th>
            <th><span class="title_box ">{l s='Status' mod='payplug'}</span></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        {foreach $installment.payment_list as $k=>$payment}
            <tr class="pp_fixed_height">
                <td data-e2e-payment-details-inst-{$k|escape:'htmlall':'UTF-8'}="date">{$payment['date']|escape:'htmlall':'UTF-8'}</td>
                <td data-e2e-payment-details-inst-{$k|escape:'htmlall':'UTF-8'}="amount">{displayPrice price=$payment['amount']}</td>
                <td data-e2e-payment-details-inst-{$k|escape:'htmlall':'UTF-8'}="state"
                    data-e2e-payment-details-inst-{$k|escape:'htmlall':'UTF-8'}-state="{$payment['status_code']|escape:'htmlall':'UTF-8'}"
                    class="{$payment['status_class']|escape:'htmlall':'UTF-8'}">
                    {$payment['status']|escape:'htmlall':'UTF-8'}
                </td>
                <td class="actions">
                    {if isset($payment['id'])}
                        <button class="btn btn-default open_payment_information" data-payment="{$k|escape:'htmlall':'UTF-8'}">
                            <i class="icon-search"></i>
                            {l s='Details' mod='payplug'}
                        </button>
                    {/if}
                </td>
            </tr>
            {if isset($payment['id'])}
                <tr class="payment_information payment_information-{$k|escape:'htmlall':'UTF-8'}">
                    <td colspan="5">
                        {include file='./details.tpl' payment=$payment}
                    </td>
                </tr>
            {/if}
        {/foreach}
        </tbody>
    </table>
    {if !$installment.is_paid}
        {if !$installment.is_active}
            <input class="{$module_name|escape:'htmlall':'UTF-8'}Button -disabled" type="submit" name="{$module_name|escape:'htmlall':'UTF-8'}SubmitAbort" value="{l s='Aborted' mod='payplug'}" disabled="disabled" />
        {else}
            <input type="hidden" name="admin_ajax_url" value="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="inst_id" value="{$installment.id|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="id_order" value="{$order->id|escape:'htmlall':'UTF-8'}" />
            <input class="{$module_name|escape:'htmlall':'UTF-8'}Button -green" type="submit" name="{$module_name|escape:'htmlall':'UTF-8'}SubmitAbort" value="{l s='Abort' mod='payplug'}"/>
        {/if}
        <br class="clear" />
    {/if}
</div>
