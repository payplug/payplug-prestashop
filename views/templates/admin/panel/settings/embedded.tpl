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
{assign "paymentPagePersoPopup" "<a href='{$faq_links.integrated_payment_page|escape:'htmlall':'UTF-8'}' target='_blank'>"}
{assign "paymentPagePersoRedirect" "<a href='{$faq_links.payment_page|escape:'htmlall':'UTF-8'}' target='_blank'>"}
<div class="panel-row separate_margin_block">
    <div class="payplugPanel">
        <div class="payplugPanel_label">{l s='Payment page' mod='payplug'}</div>
        <div class="payplugPanel_content">
            {if isset($payplug_switch.embedded.format) && $integrated}
                <div class="payplugSwitch
                    {if $payplug_switch.embedded.checked == 'integrated'}-left{/if}
                    {if $payplug_switch.embedded.checked == 'popup'}-center{/if}
                    {if $payplug_switch.embedded.checked == 'redirected'}-right{/if}
                    {if !$payplug_switch.embedded.active} -disabled{/if} -format">
                    <input type="radio" name="{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}" value="integrated" id="{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}_left" {if $payplug_switch.embedded.checked == 'integrated'}checked="checked"{/if}>
                    {if isset($payplug_switch.embedded.label_left) && $payplug_switch.embedded.label_left}<label for="{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}_left" class="payplugSwitch_label -left">{$payplug_switch.embedded.label_left|escape:'htmlall':'UTF-8'}</label>{/if}

                    <input type="radio" name="{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}" value="popup" id="{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}_center" {if $payplug_switch.embedded.checked == 'popup'}checked="checked"{/if}>
                    {if isset($payplug_switch.embedded.label_center) && $payplug_switch.embedded.label_center}<label for="{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}_center" class="payplugSwitch_label -center">{$payplug_switch.embedded.label_center|escape:'htmlall':'UTF-8'}</label>{/if}

                    <input type="radio" name="{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}" value="redirected" id="{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}_right" {if $payplug_switch.embedded.checked == 'redirected'}checked="checked"{/if}>
                    {if isset($payplug_switch.embedded.label_right) && $payplug_switch.embedded.label_right}<label for="{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}_right" class="payplugSwitch_label -right">{$payplug_switch.embedded.label_right|escape:'htmlall':'UTF-8'}</label>{/if}
                </div>
            {else}
                <div class="payplugSwitch
                    {if $payplug_switch.embedded.checked == 'popup'}-left{/if}
                    {if $payplug_switch.embedded.checked == 'redirected'}-right{/if}
                    {if !$payplug_switch.embedded.active} -disabled{/if}">
                    <input type="radio" name="{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}" value="popup" id="{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}_left" {if $payplug_switch.embedded.checked == 'popup'}checked="checked"{/if}>
                    <input type="radio" name="{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}" value="redirected" id="{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}_right" {if $payplug_switch.embedded.checked == 'redirected'}checked="checked"{/if}>

                    {if isset($payplug_switch.embedded.label_left) && $payplug_switch.embedded.label_left}<label for="{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}_left" class="payplugSwitch_label -left">{$payplug_switch.embedded.label_left|escape:'htmlall':'UTF-8'}</label>{/if}
                    {if isset($payplug_switch.embedded.label_right) && $payplug_switch.embedded.label_right}<label for="{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}_right" class="payplugSwitch_label -right">{$payplug_switch.embedded.label_right|escape:'htmlall':'UTF-8'}</label>{/if}
                </div>
            {/if}
        </div>
    </div>
    <div class="payplugPanel">
        <div class="payplugPanel_content">
            <div class="payplugTips -{$payplug_switch.embedded.name|escape:'htmlall':'UTF-8'}">
                {if isset($payplug_switch.embedded.format) && $integrated }
                    <div class="payplugTips_item -left {if $payplug_switch.embedded.checked !== 'integrated'}-hide{/if}">
                        {l s='admin.panel.settings.embedded.integrated' mod='payplug'}
                        <a class="payplugLink" href="{$faq_links.support|escape:'htmlall':'UTF-8'}" data-e2e-link="faq" target="_blank">{l s='admin.panel.settings.embedded.learnmore' mod='payplug'}</a>
                    </div>
                {/if}
                    <div class="payplugTips_item {if isset($payplug_switch.embedded.format) && $integrated}-center{else}-left{/if} {if $payplug_switch.embedded.checked != 'popup'}-hide{/if}">
                        {l s='admin.panel.settings.embedded.popup' tags=[$paymentPagePersoPopup] mod='payplug'}
                        <a class="payplugLink" href="{$faq_links.support|escape:'htmlall':'UTF-8'}" data-e2e-link="faq" target="_blank">{l s='admin.panel.settings.embedded.learnmore' mod='payplug'}</a>
                    </div>
                    <div class="payplugTips_item -right {if $payplug_switch.embedded.checked != 'redirected'}-hide{/if}">
                        {l s='admin.panel.settings.embedded.redirected' tags=[$paymentPagePersoRedirect] mod='payplug'}
                        <a class="payplugLink" href="{$faq_links.support|escape:'htmlall':'UTF-8'}" data-e2e-link="faq" target="_blank">{l s='admin.panel.settings.embedded.learnmore' mod='payplug'}</a>
                    </div>
            </div>
        </div>
    </div>
</div>
