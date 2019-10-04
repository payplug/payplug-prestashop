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

<div class="panel panel-1-6" id="pppanel">

    <div class="panel-heading">
        <i class="icon-money"></i> {l s='Payplug payment details' mod='payplug'}
    </div>
    <img class="logo" src="{$logo_url|escape:'htmlall':'UTF-8'}" width="79" height="28" />

    {if $show_menu_installment}
        <div>
            <p class="ppwarning">{l s='This order is subjected to an installment plan, whose status is' mod='payplug'} <span class="pp_inst_status">{$inst_status|escape:'htmlall':'UTF-8'}</span></p>
            <p>{l s='Payment schedule ID' mod='payplug'} : {$inst_id|escape:'htmlall':'UTF-8'}</p>
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
                {foreach from=$payment_list_new item=payment}
                    <tr class="pp_fixed_height">
                        <td>{$payment['date']|escape:'htmlall':'UTF-8'}</td>
                        <td>{displayPrice price=$payment['amount']}</td>
                        <td class="{$payment['status_class']|escape:'htmlall':'UTF-8'}">{$payment['status']|escape:'htmlall':'UTF-8'}</td>
                        {if isset($payment['id'])}
                            <td class="actions">
                                <button class="btn btn-default open_payment_information">
                                    <i class="icon-search"></i>
                                    {l s='Details' mod='payplug'}
                                </button>
                            </td>
                        {/if}
                    </tr>
                    {if isset($payment['id'])}
                        <tr class="payment_information" style="display: none;">
                            <td colspan="5">
                                {include file='./payment_details.tpl' payment=$payment}
                            </td>
                        </tr>
                    {/if}
                {/foreach}
                </tbody>
            </table>
            {if !$inst_paid}
                {if $inst_aborted}
                    <input class="btn green-button" type="submit" name="submitPPAbort" value="{l s='Aborted' mod='payplug'}" disabled="disabled" />
                {elseif $inst_can_be_aborted}
                    <input type="hidden" name="admin_ajax_url" value="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" />
                    <input type="hidden" name="inst_id" value="{$inst_id|escape:'htmlall':'UTF-8'}" />
                    <input class="btn green-button" type="submit" name="submitPPAbort" value="{l s='Abort' mod='payplug'}"/>
                {/if}
                <br class="clear" />
            {/if}
        </div>
    {/if}

    {if $display_single_payment}
        {if $single_payment.can_be_captured && isset($single_payment.date)}
            <span class="pp_block">{l s='Capture of this payment is authorized before %s. After this date, you will not be able to get paid.' sprintf=$single_payment.date_expiration mod='payplug'}</span>
        {/if}
        {include file='./payment_details.tpl' payment=$single_payment}
    {/if}
    {if $display_refund}
        <hr />
        {include file='./admin_order_refund.tpl'}
    {elseif $show_menu_refunded}
        <hr />
        {include file='./admin_order_refunded.tpl'}
    {elseif $show_menu_update}
        <hr />
        {include file='./admin_order_update.tpl'}
    {/if}

</div>
