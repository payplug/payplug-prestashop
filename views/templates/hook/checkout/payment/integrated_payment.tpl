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

<form class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment">
    <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_container -cardHolder"></div>
    <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_error -cardHolder">
        <span class="-hide invalidField">{l s='hook.checkout.payment.integrated.cardholder.error' mod='payplug'}</span>
        <span class="-hide emptyField">{l s='hook.checkout.payment.integrated.cardholder.empty' mod='payplug'}</span>
    </div>

    <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_container -scheme">
        <div>{l s='hook.integratedPayment.scheme' mod='payplug'}</div>
        <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_schemes">
            <label class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_scheme -visa">
                <input type="radio" name="schemeOptions" value="visa" />
                <span></span>
            </label>
            <label class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_scheme -mastercard">
                <input type="radio" name="schemeOptions" value="mastercard" />
                <span></span>
            </label>
            <label class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_scheme -cb">
                <input type="radio" name="schemeOptions" value="cb" />
                <span></span>
            </label>
        </div>
    </div>

    <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_container -pan"></div>
    <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_error -pan">
        <span class="-hide invalidField">{l s='hook.checkout.payment.integrated.cardpan.error' mod='payplug'}</span>
        <span class="-hide emptyField">{l s='hook.checkout.payment.integrated.cardholder.empty' mod='payplug'}</span>
    </div>

    <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_container -exp"></div>
    <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_container -cvv"></div>

    <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_error -exp">
        <span class="-hide invalidField">{l s='hook.checkout.payment.integrated.cardexp.error' mod='payplug'}</span>
        <span class="-hide emptyField">{l s='hook.checkout.payment.integrated.cardholder.empty' mod='payplug'}</span>
    </div>
    <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_error -cvv">
        <span class="-hide invalidField">{l s='hook.checkout.payment.integrated.cardcvv.error' mod='payplug'}</span>
        <span class="-hide emptyField">{l s='hook.checkout.payment.integrated.cardholder.empty' mod='payplug'}</span>
    </div>

    {if isset($is_one_click_activated) && $is_one_click_activated && $customer.is_guest != '1'}
        <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_container -saveCard">
            <label>
                <input type="checkbox" name="savecard">
                <span></span>
                {l s='hook.integratedPayment.savecard' mod='payplug'}
            </label>
        </div>
    {/if}

    {if isset($is_deferred_activated) && $is_deferred_activated }
        <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_container -deferred">
            {l s='hook.integratedPayment.deferred' mod='payplug'}
        </div>
    {/if}

    <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_error -fields">
        {l s='hook.checkout.payment.integrated.fields.error' mod='payplug'}
    </div>
    <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_error -payment">
    </div>
    <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_error -api">
        {l s='hook.header.integratedPayment.api.genericError' tags=['<br>'] mod='payplug'}
    </div>

    <div class="{$module_name|escape:'htmlall':'UTF-8'}IntegratedPayment_privacy_policy">
        <img class="-lock" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/integrated/lock.svg"/>
        <span>{$secure|escape:'htmlall':'UTF-8'}</span>
        <img class="-logo" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/payplug.svg"/>
        <br/>
        <a href="{$privacyLink|escape:'htmlall':'UTF-8'}" target="_blank">{$privacy|escape:'htmlall':'UTF-8'}</a>
    </div>
</form>
<script type="text/javascript">
    {literal}
        var placeholderCardholder = '{/literal}{$placeholderCardholder|escape:'javascript':'UTF-8'}{literal}';
        var placeholderPan = '{/literal}{$placeholderPan|escape:'javascript':'UTF-8'}{literal}';
        var placeholderCvv = '{/literal}{$placeholderCvv|escape:'javascript':'UTF-8'}{literal}';
        var placeholderExp = '{/literal}{$placeholderExp|escape:'javascript':'UTF-8'}{literal}';
        var loadIntegrated = function() {
            if (typeof window['payplug_utilities'] != 'undefined') {
                console.log('Loading script: {/literal}{$integrated_payment_js_url|escape:'javascript':'UTF-8'}{literal}');
                window['payplug_utilities'].loadScript('{/literal}{$integrated_payment_js_url|escape:'javascript':'UTF-8'}{literal}', function() {
                    if(typeof window['payplugModule'] != 'undefined') {
                        window['payplugModule'].integrated.init();
                    } else {
                        console.log('Type of payplugModule : ' + typeof window['payplugModule']);
                    }
                });
            } else {
                console.log('Type of payplug_utilities : ' + typeof window['payplug_utilities']);
            }
        }
        if (typeof window['payplug_utilities'] != 'undefined' && typeof window['payplugModule'] != 'undefined') {
            loadIntegrated();
        } else {
            window.addEventListener("load", loadIntegrated);
        }
    {/literal}
</script>