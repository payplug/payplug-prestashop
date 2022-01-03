<form class="payplugIntegratedPayment">
    <div class="payplugIntegratedPayment_container -cardHolder"></div>
    <div class="payplugIntegratedPayment_error -cardHolder">
        <span class="-hide invalidField">{l s='hook.checkout.payment.integrated.cardholder.error' mod='payplug'}</span>
        <span class="-hide emptyField">{l s='hook.checkout.payment.integrated.cardholder.empty' mod='payplug'}</span>
    </div>

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
    <div class="payplugIntegratedPayment_error -pan">
        <span class="-hide invalidField">{l s='hook.checkout.payment.integrated.cardpan.error' mod='payplug'}</span>
        <span class="-hide emptyField">{l s='hook.checkout.payment.integrated.cardholder.empty' mod='payplug'}</span>
    </div>

    <div class="payplugIntegratedPayment_container -exp"></div>
    <div class="payplugIntegratedPayment_container -cvv"></div>

    <div class="payplugIntegratedPayment_error -exp">
        <span class="-hide invalidField">{l s='hook.checkout.payment.integrated.cardexp.error' mod='payplug'}</span>
        <span class="-hide emptyField">{l s='hook.checkout.payment.integrated.cardholder.empty' mod='payplug'}</span>
    </div>
    <div class="payplugIntegratedPayment_error -cvv">
        <span class="-hide invalidField">{l s='hook.checkout.payment.integrated.cardcvv.error' mod='payplug'}</span>
        <span class="-hide emptyField">{l s='hook.checkout.payment.integrated.cardholder.empty' mod='payplug'}</span>
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

    {if isset($is_deferred_activated) && $is_deferred_activated }
        <div class="payplugIntegratedPayment_container -deferred">
            {l s='hook.integratedPayment.deferred' mod='payplug'}
        </div>
    {/if}

    <div class="payplugIntegratedPayment_error -fields">
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