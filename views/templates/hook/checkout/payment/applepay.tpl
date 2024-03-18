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
{if isset($iso_lang)}
    {assign var=iso_code value=$iso_lang}
{else}
    {assign var=iso_code value='en'}
{/if}
{if !isset($applepay_workflow) || !$applepay_workflow}
    {assign var=applepay_workflow value='checkout'}
{/if}
<div class="row {$module_name|escape:'htmlall':'UTF-8'}ApplePay_wrapper -{$applepay_workflow}">
    <div class="col-xs-12">
        <apple-pay-button buttonstyle="black" type="pay" locale="{$iso_code|escape:'htmlall':'UTF-8'}" id="apple-pay-button" class="{$module_name|escape:'htmlall':'UTF-8'}ApplePay_button"></apple-pay-button>
        <p class="{$module_name|escape:'htmlall':'UTF-8'}Payment_error{if isset($method) && $method} -{$method|escape:'htmlall':'UTF-8'}{/if}"></p>
    </div>
</div>

<script type="text/javascript">
    {literal}
    var loadApplePay = function() {
        if (typeof window['payplug_utilities'] != 'undefined') {
            console.log('Loading script: {/literal}{$applepay_js_url|escape:'javascript':'UTF-8'}{literal}');
            window['payplug_utilities'].loadScript('{/literal}{$applepay_js_url|escape:'javascript':'UTF-8'}{literal}', function() {
                if(typeof window['payplugModule'] != 'undefined') {
                    window['payplugModule'].applepay.set('workflow', '{/literal}{$applepay_workflow|escape:'htmlall':'UTF-8'}{literal}');
                    window['payplugModule'].applepay.init();
                } else {
                    console.log('Type of payplugModule : ' + typeof window['payplugModule']);
                }
            });
        } else {
            console.log('Type of payplug_utilities : ' + typeof window['payplug_utilities']);
        }
    }
    if (typeof window['payplug_utilities'] != 'undefined' && typeof window['payplugModule'] != 'undefined') {
        loadApplePay();
    } else {
        window.addEventListener("load", loadApplePay);
    }
    {/literal}
</script>
