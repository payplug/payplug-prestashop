<form class="payplugIntegratedPayment">
    <div class="scheme">
        <select id ="schemeOptions" class="payplugIntegratedPayment_select">
            <option class="selectWording" value="nothing">{l s='hook.integratedPayment.select.message' mod='payplug'}</option>
            <option class="selectAuto" value="auto">{l s='hook.integratedPayment.select.Auto' mod='payplug'}</option>
        </select>
        <span class="errorScheme" id="errorCardScheme"></span>
    </div>
    <div class="payplugIntegratedPayment_input -cardholder"></div>
    <span class="errorCB" id="errorCardHolder"></span>
    <div class="payplugIntegratedPayment_input -pan"></div>
    <span class="errorPan" id="errorCardPan"></span>
    <div style="display: inline-block">
        <div class="payplugIntegratedPayment_input -exp"></div>
        <div class="payplugIntegratedPayment_input -cvv"></div>
        <span class="errorExp" id="errorCardExp"></span>
        <span class="errorCvv" id="errorCardCvv"></span>
    </div>
    {if isset($is_one_click_activated) && $is_one_click_activated }
        <div class="payplugIntegratedPayment_input -saveCard">
            <input type="checkbox" name="savecard">
            <label for="savecard">{l s='hook.integratedPayment.savecard' mod='payplug'}</label>
        </div>
    {/if}
</form>
<script type="text/javascript">
    {literal}
        var loadIntegrated = function() {
            if (typeof payplug_utilities != 'undefined') {
                payplug_utilities.loadScript('{/literal}{$integrated_payment_js_url}{literal}', function() {
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