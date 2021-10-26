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
<div class="payment_module payplugPayment oneyPayment{if !$payplug_oney_allowed} -disabled{/if}">
    <button href="javascript:void(0);" class="oneyPayment_trigger">
        <span class="oneyPayment_logo oneyLogo -x3x4{if isset($use_fees) && !$use_fees} -withoutFees{/if} {if isset($iso_code) && $iso_code == 'IT' } -isItalian{/if} ">
            <img src="/modules/payplug/views/img/oney/x3x4_with{if isset($use_fees) && !$use_fees}out{/if}_fees_side{if isset($use_fees) && !$use_fees && isset($iso_code) && $iso_code == 'IT' }_IT{/if}.svg"
                     alt="{if isset($use_fees) && !$use_fees}{l s='hook.oney.payment.paywithoneywithoutfees' mod='payplug'}{else}{l s='hook.oney.payment.paywithoney' mod='payplug'}{/if}" />
        </span>
        <span class="oneyPayment_label">
            {if isset($use_fees) && !$use_fees}
                {l s='hook.oney.payment.paywithoneywithoutfees' mod='payplug'}
            {else}
                {l s='hook.oney.payment.paywithoney' mod='payplug'}
            {/if}

            {if $payplug_oney_error}<span class="oneyPayment_error">{$payplug_oney_error|escape:'htmlall':'UTF-8'}</span>{/if}
        </span>
    </button>
    {if $payplug_oney_allowed}
        {if isset($oney_payment_options) && $oney_payment_options}
            <div class="oneyOption_wrapper">
                {include file="./options.tpl"}
            </div>
            {if isset($oney_required_fields)}
                {include file="./../required.tpl" oney_required_fields=$oney_required_fields}
            {/if}
            <div class="oneyPayment_cta">
                <button class="oneyPayment_button"></button>
                {if $lang_iso == 'it' && $merchant_company_iso == 'IT'}
                    <a href="https://www.payplug.com/hubfs/ONEY/payplug-italy{if isset($use_fees) && !$use_fees}-no-fees{/if}.pdf" target="_blank">{l s='hook.oney.payment.cgv' mod='payplug'}</a>
                {/if}
            </div>
        {else}
            <div class="oneyOption_wrapper -loading">
                <span class="oneyLoader">
                    <span class="oneyLoader_spinner"><span></span></span>
                    <span class="oneyLoader_message">{$payplug_oney_loading_msg|escape:'htmlall':'UTF-8'} <i>.</i><i>.</i><i>.</i></span>
                </span>
            </div>
        {/if}
    {/if}
</div>
