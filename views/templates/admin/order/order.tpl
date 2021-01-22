{*
* 2021 PayPlug
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
*  @copyright 2021 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}

<div class="panel panel-1-6 payplugOrder card mt-2 d-print-none" id="pppanel">
    <h3 class="panel-heading card-header">
        <i class="icon-money"></i> {l s='Payplug payment details' mod='payplug'}
    </h3>
    <div class="card-body">
        <img class="logo" src="{$logo_url|escape:'htmlall':'UTF-8'}" width="79" height="28" />
        {if $show_menu_installment}
            {include file='./installment.tpl'}
        {/if}

        {if $display_single_payment}
            {if $single_payment.can_be_captured && isset($single_payment.date)}
                <span class="payplugAlert -warning">{l s='Capture of this payment is authorized before %s. After this date, you will not be able to get paid.' sprintf=$single_payment.date_expiration mod='payplug'}</span>
            {/if}
            {include file='./details.tpl' payment=$single_payment}
        {/if}
        {if $display_refund}
            <hr />
            {include file='./refund.tpl'}
        {elseif $show_menu_refunded}
            <hr />
            {include file='./refunded.tpl'}
        {elseif $show_menu_update}
            <hr />
            {include file='./update.tpl'}
        {/if}
    </div>
</div>
