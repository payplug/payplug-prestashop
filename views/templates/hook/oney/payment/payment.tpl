{*
* 2022 PayPlug
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
*  @copyright 2022 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
<div class="payment_module {$module_name|escape:'htmlall':'UTF-8'}Payment {$module_name|escape:'htmlall':'UTF-8'}OneyPayment{if !$payplug_oney_allowed} -disabled{/if}">
    <button href="javascript:void(0);" class="{$module_name|escape:'htmlall':'UTF-8'}OneyPayment_trigger">
        {if isset($oney_image['optimized'])}
            {assign var=oney_logo value=$oney_image['optimized']}
        {else}
            {assign var=oney_logo value=$oney_image}
        {/if}
        <img src="{$oney_logo|escape:'htmlall':'UTF-8'}"
                     alt="{if isset($use_fees) && !$use_fees}{l s='hook.oney.payment.paywithoneywithoutfees' mod='payplug'}{else}{l s='hook.oney.payment.paywithoney' mod='payplug'}{/if}"
                    class="{$module_name|escape:'htmlall':'UTF-8'}OneyLogo -optimized-16 {$payplug_payment_option.extra_classes|escape:'htmlall':'UTF-8'}"/>
        <span class="{$module_name|escape:'htmlall':'UTF-8'}OneyPayment_label">
            {if isset($use_fees) && !$use_fees}
                {l s='hook.oney.payment.paywithoneywithoutfees' mod='payplug'}
            {else}
                {l s='hook.oney.payment.paywithoney' mod='payplug'}
            {/if}

            {if $payplug_oney_error}<span class="{$module_name|escape:'htmlall':'UTF-8'}OneyPayment_error">{$payplug_oney_error|escape:'htmlall':'UTF-8'}</span>{/if}
        </span>
    </button>
    {if $payplug_oney_allowed}
        {if isset($oney_payment_options) && $oney_payment_options}
            <div class="{$module_name|escape:'htmlall':'UTF-8'}OneyOption_wrapper">
                {include file="./options.tpl" oney_image=$oney_image}
            </div>
            {if isset($oney_required_fields)}
                {include file="./../required.tpl" oney_required_fields=$oney_required_fields}
            {/if}
            <div class="{$module_name|escape:'htmlall':'UTF-8'}OneyPayment_cta">
                <button class="{$module_name|escape:'htmlall':'UTF-8'}OneyPayment_button"></button>
                {if $lang_iso == 'it' && $merchant_company_iso == 'IT'}
                    <a href="https://www.payplug.com/hubfs/ONEY/payplug-italy{if isset($use_fees) && !$use_fees}-no-fees{/if}.pdf" target="_blank">{l s='hook.oney.payment.cgv' mod='payplug'}</a>
                {/if}
            </div>
        {else}
            <div class="{$module_name|escape:'htmlall':'UTF-8'}OneyOption_wrapper -loading">
                <span class="{$module_name|escape:'htmlall':'UTF-8'}OneyLoader">
                    <span class="{$module_name|escape:'htmlall':'UTF-8'}OneyLoader_spinner"><span></span></span>
                    <span class="{$module_name|escape:'htmlall':'UTF-8'}OneyLoader_message">{$payplug_oney_loading_msg|escape:'htmlall':'UTF-8'} <i>.</i><i>.</i><i>.</i></span>
                </span>
            </div>
        {/if}
    {/if}
</div>
