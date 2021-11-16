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
<div class="panel-row separate_margin_block">
    <div class="payplugPanel">
        <div class="payplugPanel_label">{l s='Defer the payment capture' mod='payplug'}</div>
        <div class="payplugPanel_content">{include file='./switch.tpl' switch=$payplug_switch.deferred}</div>
    </div>
    <div class="payplugPanel">
        <div class="payplugPanel_content">
            <p>
                {l s='admin.panel.settings.deferred.7days' mod='payplug'}
                <a class="payplugLink" href="{$faq_links.deferred|escape:'htmlall':'UTF-8'}" data-e2e-link="faq" target="_blank">{l s='admin.panel.settings.deffered.more' mod='payplug'}</a>
                <br/>
                <span class="payplugDeferred -example">{l s='admin.panel.settings.deferred.example' mod='payplug'}</span>
            </p>
            <div class="payplugTips -{$payplug_switch.deferred.name|escape:'htmlall':'UTF-8'}">
                <div class="payplugTips_item -left{if !$payplug_switch.deferred.checked} -hide{/if}">
                    <div class="payplugDeferred">
                        <label for="{$payplug_switch.deferred_auto.name|escape:'htmlall':'UTF-8'}">
                            <input type="checkbox" name="{$payplug_switch.deferred_auto.name|escape:'htmlall':'UTF-8'}" value="1" id="{$payplug_switch.deferred_auto.name|escape:'htmlall':'UTF-8'}" {if $payplug_switch.deferred_auto.checked}checked="checked"{/if}>
                            {l s='admin.panel.settings.deferred.trigger' mod='payplug'}

                        </label>
                        <select name="payplug_deferred_state" data-id_state="{$deferred_state|escape:'htmlall':'UTF-8'}" id="payplug_deferred_state"{if !$payplug_switch.deferred_auto.checked} disabled="disabled"{/if}>
                            <option value="0">{l s='admin.panel.settings.deferred.choose' mod='payplug'}</option>
                            {foreach from=$order_states item=order_state}
                                <option value="{$order_state.id_order_state|escape:'htmlall':'UTF-8'}"{if $deferred_state == $order_state.id_order_state} selected="selected"{/if}>{$order_state.name|escape:'htmlall':'UTF-8'}</option>
                            {/foreach}
                        </select>
                            <span style="display: none;" data-e2e-error="deferred_state" class="payplugDeferred_error">{l s='admin.panel.settings.deferred.mustchoose' mod='payplug'}</span>
                    <div style="display: none;" data-e2e-error="change_state" class="payplugDeferred_warning"><p>{l s='admin.panel.settings.deferred.warning' mod='payplug'}</p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
