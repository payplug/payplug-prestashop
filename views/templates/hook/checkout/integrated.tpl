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

<!-- Set up a form with 5 divs -->
<form class="payplugIntegratedPayment" domain="https://secure.alpha.notpayplug.com">
    <div class="scheme"></div>
    <div data-integrated="cardholder" class="payplugIntegratedPayment_input -cardholder"></div>
    <div data-integrated="pan" class="payplugIntegratedPayment_input -pan"></div>
    <div data-integrated="exp" class="payplugIntegratedPayment_input -exp"></div>
    <div data-integrated="cvv" class="payplugIntegratedPayment_input -cvv"></div>
    <div class="payplugIntegratedPayment_input -saveCard">
        <input type="checkbox" name="save_card">
        {l s='hook.checkout.integrated.saveCardLabel' mod='payplug'}
    </div>
    <button class="payplugIntegratedPayment_button -green" type="submit">{l s='hook.checkout.integrated.button' mod='payplug'}</button>
</form>
<script type="text/javascript">
    if (typeof payplugModule != 'undefined') {
        payplugModule.tools.loadScript('https://secure.alpha.notpayplug.com/integrated/js', function() {
            payplugModule.integrated.init();
        });
    } else {
        console.log('Type of payplugModule : ' + typeof payplugModule);
    }
</script>
