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
{if $version < 1.5}
    <br />
    <div id="pppanel" style="float: left">
        <fieldset style="width: 400px">
            <legend><img src="../img/t/AdminPayment.gif"> {l s='Payplug payment details' mod='payplug'}</legend>
            <img class="logo" src="{$logo_url|escape:'htmlall':'UTF-8'}" width="79" height="28" />
            {if $show_menu_installment}
                {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/admin_order_installment.tpl'}
            {/if}
            {if $display_single_payment}
                {if $single_payment.can_be_captured && isset($single_payment.date)}
                    <span class="pp_block">{$single_payment.expiration_display}</span>
                {/if}
                {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/payment_details.tpl' payment=$single_payment}
            {/if}
            {if $display_refund}
                <hr />
                {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/admin_order_refund.tpl'}
            {elseif $show_menu_refunded}
                <hr />
                {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/admin_order_refunded.tpl'}
            {elseif $show_menu_update}
                <hr />
                {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/admin_order_update.tpl'}
            {/if}
        </fieldset>
    </div>
{elseif $version < 1.6}
    <br />
    <fieldset id="pppanel">
        <legend><img src="../img/admin/money.gif"> {l s='Payplug payment details' mod='payplug'}</legend>
        {if $show_menu_installment}
            {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/admin_order_installment.tpl'}
        {/if}
        {if $display_single_payment}
            {if $single_payment.can_be_captured && isset($single_payment.date)}
                <span class="pp_block">{l s='Capture of this payment is authorized before %s. After this date, you will not be able to get paid.' sprintf=$single_payment.date_expiration mod='payplug'}</span>
            {/if}
            {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/payment_details.tpl' payment=$single_payment}
        {/if}
        {if $display_refund}
            <hr />
            {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/admin_order_refund.tpl'}
        {elseif $show_menu_refunded}
            <hr />
            {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/admin_order_refunded.tpl'}
        {elseif $show_menu_update}
            <hr />
            {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/admin_order_update.tpl'}
        {/if}
    </fieldset>
{elseif $version >= 1.6}
    <div class="panel panel-1-6" id="pppanel">
        <div class="panel-heading">
            <i class="icon-money"></i> {l s='Payplug payment details' mod='payplug'}
        </div>
        <img class="logo" src="{$logo_url|escape:'htmlall':'UTF-8'}" width="79" height="28" />
        {if $show_menu_installment}
            {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/admin_order_installment.tpl'}
        {/if}
        {if $display_single_payment}
            {if $single_payment.can_be_captured && isset($single_payment.date)}
                <span class="pp_block">{l s='Capture of this payment is authorized before %s. After this date, you will not be able to get paid.' sprintf=$single_payment.date_expiration mod='payplug'}</span>
            {/if}
            {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/payment_details.tpl' payment=$single_payment}
        {/if}
        {if $display_refund}
            <hr />
            {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/admin_order_refund.tpl'}
        {elseif $show_menu_refunded}
            <hr />
            {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/admin_order_refunded.tpl'}
        {elseif $show_menu_update}
            <hr />
            {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/admin_order_update.tpl'}
        {/if}
    </div>
{else}
    <p>{l s='Your Prestashop version is not compatible' mod='payplug'}</p>
{/if}

{include file=$payplug_module_dir|cat:'payplug/views/templates/admin/admin_order_fields.tpl'}
