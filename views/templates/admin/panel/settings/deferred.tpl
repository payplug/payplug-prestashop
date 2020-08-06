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
*  @copyright 2019 PayPlug SAS
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
                {l s='Finalize payment later, when order has been shipped for instance. The funds will be blocked for a period of 7 days maximum once the payment has been authorized.' mod='payplug'}
                <a class="payplugLink" href="{$faq_links.deferred|escape:'htmlall':'UTF-8'}" target="_blank">{l s='More information.' mod='payplug'}</a>
            </p>
            <div class="payplugTips payplugTips-{$payplug_switch.deferred.name|escape:'htmlall':'UTF-8'}">
                <div class="payplugTips_item payplugTips_item-left" {if !$payplug_switch.deferred.checked}style="display: none;"{/if}>
                    <div class="payplugDeferred">
                        <label for="{$payplug_switch.deferred_auto.name|escape:'htmlall':'UTF-8'}">
                            <input type="checkbox" name="{$payplug_switch.deferred_auto.name|escape:'htmlall':'UTF-8'}" value="1" id="{$payplug_switch.deferred_auto.name|escape:'htmlall':'UTF-8'}" {if $payplug_switch.deferred_auto.checked}checked="checked"{/if}>
                            {l s='During the 7 days of the authorization, capture payments whose state is :' mod='payplug'}
                        </label>
                        <select name="payplug_deferred_state" id="payplug_deferred_state"{if !$payplug_switch.deferred_auto.checked} disabled="disabled"{/if}>
                            <option value="0">{l s='-- Choose an order state --' mod='payplug'}</option>
                            {foreach from=$order_states item=order_state}
                                <option value="{$order_state.id_order_state}"{if $PAYPLUG_DEFERRED_STATE == $order_state.id_order_state} selected="selected"{/if}>{$order_state.name}</option>
                            {/foreach}
                        </select>
                        <span style="display: none;" data-e2e-error="deferred_state">{l s='You have to choose an order state.' mod='payplug'}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
