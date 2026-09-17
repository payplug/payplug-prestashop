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

<form class="{$module_name|escape:'htmlall':'UTF-8'}HostedFields">
    <div class="{$module_name|escape:'htmlall':'UTF-8'}HostedFields_container -cardHolder">
        <input type="text" name="cardholder" id="hf-cardholder" placeholder="{$placeholderCardholder|escape:'htmlall':'UTF-8'}" autocomplete="cc-name" />
    </div>
    <div class="{$module_name|escape:'htmlall':'UTF-8'}HostedFields_error -cardHolder">
        <span class="-hide invalidField">{l s='hook.checkout.payment.hosted_fields.cardholder.error' mod='payplug'}</span>
        <span class="-hide emptyField">{l s='hook.checkout.payment.hosted_fields.cardholder.empty' mod='payplug'}</span>
    </div>

    <div class="{$module_name|escape:'htmlall':'UTF-8'}HostedFields_container -scheme">
        <div>{l s='hook.hostedFields.scheme' mod='payplug'}</div>
        <div class="{$module_name|escape:'htmlall':'UTF-8'}HostedFields_schemes" id="hosted-brand-container"></div>
    </div>

    <div class="{$module_name|escape:'htmlall':'UTF-8'}HostedFields_container -pan" id="hosted-card-container"></div>
    <div class="{$module_name|escape:'htmlall':'UTF-8'}HostedFields_error -pan">
        <span class="-hide invalidField">{l s='hook.checkout.payment.hosted_fields.pan.error' mod='payplug'}</span>
        <span class="-hide emptyField">{l s='hook.checkout.payment.hosted_fields.pan.empty' mod='payplug'}</span>
    </div>

    <div class="{$module_name|escape:'htmlall':'UTF-8'}HostedFields_container -exp" id="hosted-expiry-container"></div>
    <div class="{$module_name|escape:'htmlall':'UTF-8'}HostedFields_container -cvv" id="hosted-cvv-container"></div>

    <div class="{$module_name|escape:'htmlall':'UTF-8'}HostedFields_error -exp">
        <span class="-hide invalidField">{l s='hook.checkout.payment.hosted_fields.exp.error' mod='payplug'}</span>
        <span class="-hide emptyField">{l s='hook.checkout.payment.hosted_fields.exp.empty' mod='payplug'}</span>
    </div>
    <div class="{$module_name|escape:'htmlall':'UTF-8'}HostedFields_error -cvv">
        <span class="-hide invalidField">{l s='hook.checkout.payment.hosted_fields.cvv.error' mod='payplug'}</span>
        <span class="-hide emptyField">{l s='hook.checkout.payment.hosted_fields.cvv.empty' mod='payplug'}</span>
    </div>


    {if isset($is_one_click_activated) && $is_one_click_activated && $customer.is_guest != '1'}
    <div class="{$module_name|escape:'htmlall':'UTF-8'}HostedFields_container -saveCard">
        <label>
            <input type="checkbox" name="save_card" id="hf-save-card" />
            {l s='hook.checkout.payment.hosted_fields.save_card.label' mod='payplug'}
        </label>
    </div>
    {/if}

    <div class="{$module_name|escape:'htmlall':'UTF-8'}HostedFields_error -payment"></div>

{*    {if isset($is_deferred_activated) && $is_deferred_activated }*}
{*        <div class="{$module_name|escape:'htmlall':'UTF-8'}HostedFields_container -deferred">*}
{*            {l s='hook.HostedFields.deferred' mod='payplug'}*}
{*        </div>*}
{*    {/if}*}

    <div class="{$module_name|escape:'htmlall':'UTF-8'}HostedFields_privacy_policy">
        <img class="-lock" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/integrated/lock.svg"/>
        <span>{$secure|escape:'htmlall':'UTF-8'}</span>
        <img class="-logo" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/payplug.svg"/>
        <br/>
        <a href="{$privacyLink|escape:'htmlall':'UTF-8'}" target="_blank">{$privacy|escape:'htmlall':'UTF-8'}</a>
    </div>

    <input type="hidden" id="hf-token" />
    <input type="hidden" id="hf-selected-brand" />
</form>
<script type="text/javascript">
    {literal}
    var placeholderPan = '{/literal}{$placeholderPan|escape:'javascript':'UTF-8'}{literal}';
    var placeholderCvv = '{/literal}{$placeholderCvv|escape:'javascript':'UTF-8'}{literal}';
    var placeholderExp = '{/literal}{$placeholderExp|escape:'javascript':'UTF-8'}{literal}';
    window['hosted_company_id'] = '{/literal}{$hosted_company_id|escape:'javascript':'UTF-8'}{literal}';
    window['payplug_hosted_fields_identifier'] = '{/literal}{$hosted_fields_identifier|escape:'javascript':'UTF-8'}{literal}';
    // nofilter is safe here only because this value is PrestashopAdapter17::HOSTED_FIELDS_ACCEPTED_BRANDS,
    // a hardcoded PHP constant - never use nofilter for dynamic/user-controlled data.
    window['payplug_hosted_fields_accepted_brands'] = {/literal}{$hosted_fields_accepted_brands nofilter}{literal};
    window['payplug_hosted_fields_uhf_url'] = '{/literal}{$hosted_fields_uhf_url|escape:'javascript':'UTF-8'}{literal}';
    window['payplug_hosted_fields_error_generic'] = '{/literal}{l s='hook.checkout.payment.hosted_fields.payment.error' mod='payplug' js=1}{literal}';
    window['payplug_hosted_fields_error_unsupported_brand'] = '{/literal}{l s='hook.checkout.payment.hosted_fields.payment.error.unsupported_brand' mod='payplug' js=1}{literal}';

    var loadHostedFields = function() {
        if (typeof window['payplug_utilities'] != 'undefined') {
            window['payplug_utilities'].loadScript('{/literal}{$hosted_fields_js_url|escape:'javascript':'UTF-8'}{literal}', function() {
                if (typeof window['payplugModule'] != 'undefined' && typeof window['payplugModule'].hosted_fields != 'undefined') {
                    window['payplugModule'].hosted_fields.init();
                } else {
                    console.log('Type of payplugModule : ' + typeof window['payplugModule']);
                }
            }, function() {
                // loadScript()'s onerror fires for a network failure, a 404, or (the case
                // this complements) an empty src - i.e. HOSTED_FIELDS_URL unset server-side
                // (see Routes::getHostedFieldsUrl(), which logs that misconfiguration for
                // ops). Without this, the form was left silently broken for the customer.
                // Same generic-error convention as every other failure path in this
                // sub-module (see dev/js/front.js, __moduleName__Module.hosted_fields).
                console.error('[HostedFields] Failed to load hosted fields SDK script');
                if (typeof $ != 'undefined') {
                    $('.{/literal}{$module_name|escape:'javascript':'UTF-8'}{literal}HostedFields_error.-payment')
                        .text(window['payplug_hosted_fields_error_generic'])
                        .addClass('-show');
                }
            });
        } else {
            console.log('Type of payplug_utilities : ' + typeof window['payplug_utilities']);
        }
    }
    if (typeof window['payplug_utilities'] != 'undefined' && typeof window['payplugModule'] != 'undefined') {
        loadHostedFields();
    } else {
        window.addEventListener("load", loadHostedFields);
    }
    {/literal}
</script>
