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

{extends file='page.tpl'}

{block name='page_title'}
    {l s='Waiting for confirmation' mod='payplug'}
{/block}

{block name='page_content'}
    <div class="payplugValidation">
        <p>{l s='Your payment is being processed. Please wait while we confirm your order.' mod='payplug'}</p>
        <div class="payplugUILoader" >
            <span></span>
        </div>
    </div>
    <script type="text/javascript">
        {literal}
        var loadValidation = function() {
            if (typeof window['payplug_utilities'] != 'undefined') {
                if(typeof window['payplugModule'] != 'undefined') {
                    window['payplugModule'].validation.init();
                } else {
                    console.log('Type of payplugModule : ' + typeof window['payplugModule']);
                }
            } else {
                console.log('Type of payplug_utilities : ' + typeof window['payplug_utilities']);
            }
        }
        if (typeof window['payplug_utilities'] != 'undefined' && typeof window['payplugModule'] != 'undefined') {
            loadValidation();
        } else {
            window.addEventListener("load", loadValidation);
        }
        {/literal}
    </script>
{/block}
