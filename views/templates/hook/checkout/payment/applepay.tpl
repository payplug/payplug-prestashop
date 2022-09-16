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
{if isset($language) && isset($language.iso_code)}
    {assign var=iso_code value=$language.iso_code}
{else}
    {assign var=iso_code value='fr'}
{/if}
<div class="row">
    <div class="col-xs-12">
        <apple-pay-button buttonstyle="black" type="pay" locale="{$iso_code}" id="apple-pay-button"></apple-pay-button>
        <p class="{$module_name|escape:'htmlall':'UTF-8'}Payment_error{if isset($method) && $method} -{$method|escape:'htmlall':'UTF-8'}{/if}"></p>
    </div>
</div>
<script type="text/javascript">
    {literal}
        var loadApplePaySDK = function() {
            if (typeof window['payplug_utilities'] != 'undefined') {
                window['payplug_utilities'].loadScript('https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js', function() {
                    if(typeof window['payplugModule'] != 'undefined') {
                        window['payplugModuleApplePay'].init();
                    } else {
                        console.log('Type of payplugModule : ' + typeof window['payplugModule']);
                    }
                });
            } else {
                console.log('Type of payplug_utilities : ' + typeof window['payplug_utilities']);
            }
        }
        if (typeof window['payplug_utilities'] != 'undefined' && typeof window['payplugModule'] != 'undefined') {
            loadApplePaySDK();
        } else {
            window.addEventListener("load", loadApplePaySDK);
        }
    {/literal}
</script>
