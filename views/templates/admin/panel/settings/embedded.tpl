{*
* 2020 PayPlug
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
*  @copyright 2020 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
<div class="panel-row separate_margin_block">
    <div class="payplugPanel">
        <div class="payplugPanel_label">{l s='Payment page' mod='payplug'}</div>
        <div class="payplugPanel_content">
            {include file='./switch.tpl' switch=$payplug_switch.embedded}
        </div>
    </div>
    <div class="payplugPanel">
        <div class="payplugPanel_content">
            <div class="payplugTips payplugTips-{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}">
                <div class="payplugTips_item payplugTips_item-left" {if !$payplug_switch.embedded.checked}style="display: none;"{/if}>
                    {l s='Payments are performed in an embeddable payment form.' mod='payplug'}<br>{l s='The customers will pay without being redirected.' mod='payplug'}
                    <a class="payplugLink" href="{$faq_links.payment_page|escape:'htmlall':'UTF-8'}" target="_blank">{l s='Learn more.' mod='payplug'}</a>
                </div>
                <div class="payplugTips_item payplugTips_item-right"{if $payplug_switch.embedded.checked}style="display: none;"{/if}>
                    {l s='The customers will be redirected to a PayPlug payment page to finalize the transaction.' mod='payplug'}
                    <a class="payplugLink" href="{$faq_links.payment_page|escape:'htmlall':'UTF-8'}" target="_blank">{l s='Learn more.' mod='payplug'}</a>
                </div>
            </div>
        </div>
    </div>
</div>
