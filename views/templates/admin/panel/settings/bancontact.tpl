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
<div class="payplugBancontact panel-row separate_margin_block{if $payplug_switch.sandbox.checked} -hide{/if}">
    <div class="payplugPanel">
        <div class="payplugPanel_label">{l s='admin.panel.settings.bancontact.label' mod='payplug'}</div>
        <div class="payplugPanel_content">{include file='./switch.tpl' switch=$payplug_switch.bancontact}</div>
    </div>
    <div class="payplugPanel">
        <div class="payplugPanel_content">
            <p>
                {l s='admin.panel.settings.bancontact.content' mod='payplug'}
                <a class="payplugLink" href="{$faq_links.bancontact|escape:'htmlall':'UTF-8'}" data-e2e-link="faq" target="_blank">
                    {l s='admin.panel.settings.bancontact.link' mod='payplug'}
                </a>
            </p>
        </div>
    </div>
</div>
