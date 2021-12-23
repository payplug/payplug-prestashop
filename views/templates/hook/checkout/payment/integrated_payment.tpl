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

<form class="payplugIntegratedPayment">
    <div class="payplugIntegratedPayment_container -cardholder"></div>
    <span class="payplugIntegratedPayment_error -hide" id="errorCardHolder">
        {l s='hook.checkout.payment.integrated.cardholder.error' mod='payplug'}
    </span>

    <div class="payplugIntegratedPayment_container -scheme">
        <div>{l s='hook.integratedPayment.scheme' mod='payplug'}</div>
        <div class="payplugIntegratedPayment_schemes">
            <label class="payplugIntegratedPayment_scheme -visa">
                <input type="radio" name="schemeOptions" value="visa" />
                <span></span>
            </label>
            <label class="payplugIntegratedPayment_scheme -mastercard">
                <input type="radio" name="schemeOptions" value="mastercard" />
                <span></span>
            </label>
            <label class="payplugIntegratedPayment_scheme -cb">
                <input type="radio" name="schemeOptions" value="cb" />
                <span></span>
            </label>
        </div>
    </div>

    <div class="payplugIntegratedPayment_container -pan"></div>
    <span class="payplugIntegratedPayment_error -hide" id="errorCardPan">
        {l s='hook.checkout.payment.integrated.cardpan.error' mod='payplug'}
        <br>
    </span>

    <div class="payplugIntegratedPayment_container -exp"></div>
    <div class="payplugIntegratedPayment_container -cvv"></div>

    <div class="payplugIntegratedPayment_container -errorContainer">
        <div class="left">
            <span class="payplugIntegratedPayment_error -exp -hide" id="errorCardExp">
                {l s='hook.checkout.payment.integrated.cardexp.error' mod='payplug'}
            </span>
        </div>
        <div class="right">
            <span class="payplugIntegratedPayment_error -cvv -hide" id="errorCardCvv">
                {l s='hook.checkout.payment.integrated.cardcvv.error' mod='payplug'}
            </span>
        </div>
    </div>


    {if isset($is_one_click_activated) && $is_one_click_activated }
        <div class="payplugIntegratedPayment_container -saveCard">
            <label>
                <input type="checkbox" name="savecard">
                <span></span>
                {l s='hook.integratedPayment.savecard' mod='payplug'}
            </label>
        </div>
    {/if}
    <div class="payplugIntegratedPayment_error -fields -hide">
        {l s='hook.checkout.payment.integrated.fields.error' mod='payplug'}
    </div>
    <div class="payplugIntegratedPayment_error -payment"></div>
</form>
<script type="text/javascript">
    {literal}
        var placeholderCardholder = '{/literal}{$placeholderCardholder|escape:'htmlall':'UTF-8'}{literal}';
        var placeholderPan = '{/literal}{$placeholderPan|escape:'htmlall':'UTF-8'}{literal}';
        var placeholderCvv = '{/literal}{$placeholderCvv|escape:'htmlall':'UTF-8'}{literal}';
        var placeholderExp = '{/literal}{$placeholderExp|escape:'htmlall':'UTF-8'}{literal}';
        var loadIntegrated = function() {
            if (typeof payplug_utilities != 'undefined') {
                payplug_utilities.loadScript('{/literal}{$integrated_payment_js_url|escape:'htmlall':'UTF-8'}{literal}', function() {
                    if(typeof payplugModule != 'undefined') {
                        payplugModule.integrated.init();
                    } else {
                        console.log('Type of payplugModule : ' + typeof payplugModule);
                    }
                });
            } else {
                console.log('Type of payplug_utilities : ' + typeof payplug_utilities);
            }
        }
        if (typeof payplug_utilities != 'undefined' && typeof payplugModule != 'undefined') {
            loadIntegrated();
        } else {
            window.addEventListener("load", loadIntegrated);
        }
    {/literal}
</script>