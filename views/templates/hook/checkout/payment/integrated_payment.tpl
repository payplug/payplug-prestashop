<form class="payplugIntegratedPayment" id ="test">
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
</form>
<script src={$integrated_payment_js_url}></script>