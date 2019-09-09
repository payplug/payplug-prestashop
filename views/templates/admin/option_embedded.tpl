{*
* 2019 PayPlug
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
*  @copyright 2019 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
<div class="panel-row separate_margin_block">
    {include file='./switch.tpl' switch=$switch_embedded}
    <div class="panel-row">
        <div class="block-right">
            <p class="pptips">
                <span{if !$PAYPLUG_EMBEDDED_MODE} class="hide"{/if} id="payment_page_embedded_tips">{l s='Payments are performed in an embeddable payment form.' mod='payplug'}<br>{l s='The customers will pay without being redirected.' mod='payplug'}
                    <a href="http://support.payplug.com/customer/portal/articles/2563974" target="_blank">{l s='Learn more.' mod='payplug'}</a>
                </span>
                <span{if $PAYPLUG_EMBEDDED_MODE} class="hide"{/if} id="payment_page_redirect_tips">{l s='The customers will be redirected to a PayPlug payment page to finalize the transaction.' mod='payplug'}
                    <a href="http://support.payplug.com/customer/portal/articles/2018493" target="_blank">{l s='Learn more.' mod='payplug'}</a>
                </span>
            </p>
        </div>
    </div>
</div>
