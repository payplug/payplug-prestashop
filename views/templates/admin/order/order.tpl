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
<div class="panel panel-1-6 {$module_name|escape:'htmlall':'UTF-8'}Order card mt-2 d-print-none" id="pppanel">
    <h3 class="panel-heading card-header">
        <i class="icon-money"></i> {l s='Payplug payment details' mod='payplug'}
    </h3>
    <div class="card-body">
        <span class="logo">
            <img src="{$logo_url|escape:'htmlall':'UTF-8'}" />
        </span>
        {if isset($undefined_history_states) && $undefined_history_states}
            {include file='./order_state.tpl'}
        {/if}

        {if isset($installment) && $installment}
            {include file='./installment.tpl' installment=$installment}
        {/if}

        {if isset($payment) && $payment}
            {if isset($payment.can_be_captured) && $payment.can_be_captured && isset($payment.date)}
                <span class="{$module_name|escape:'htmlall':'UTF-8'}Alert -warning">{l s='Capture of this payment is authorized before %s. After this date, you will not be able to get paid.' sprintf=$payment.date_expiration mod='payplug'}</span>
            {/if}
            {include file='./details.tpl' payment=$payment}
        {/if}

        {if $refund}
            <hr />
            {include file='./refund.tpl'}
        {elseif $refunded}
            <hr />
            {include file='./refunded.tpl' refunded=$refunded}
        {elseif $update}
            <hr />
            {include file='./update.tpl'}
        {/if}
    </div>
</div>