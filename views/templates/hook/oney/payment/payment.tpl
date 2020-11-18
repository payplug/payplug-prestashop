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
<div class="payment_module payplugPayment oneyPayment{if !$payplug_oney_allowed} -disabled{/if}{if isset($payplug_carrier_error) && $payplug_carrier_error} -invalidCarrier{/if}">
    <button href="javascript:void(0);" class="oneyPayment_trigger">
        <span class="oneyPayment_logo oneyLogo -x3x4">
            {if isset($payplug_payment_option.logo_url) && isset($payplug_payment_option.logo_url) && $payplug_payment_option.logo_url}
                <img src="{$payplug_payment_option.logo_url|escape:'html'}" alt="{l s='Pay by card in 3 or 4' mod='payplug'}" />
            {/if}
        </span>
        <span class="oneyPayment_label">
            {l s='Pay by card in 3 or 4' mod='payplug'}
            {if $payplug_oney_error}<span class="oneyPayment_error">{$payplug_oney_error|escape:'htmlall':'UTF-8'}</span>{/if}
        </span>
    </button>
    {if $payplug_oney_allowed}
        {if isset($oney_payment_options) && $oney_payment_options}
            <div class="oneyOption_wrapper">
                {include file="./options.tpl"}
            </div>
            {$payplug_oney_required_field}
            <div class="oneyPayment_cta">
                <button class="oneyPayment_button"></button>
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
