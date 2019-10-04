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
    {include file='./switch.tpl' switch=$switch_deferred_payment}
    <div class="panel-row">
        <div class="block-right">
            <p class="pptips">{l s='Finalize payment later, when order has been shipped for instance. The funds will be blocked for a period of 7 days maximum once the payment has been authorized.' mod='payplug'}
                <a href="http://support.payplug.com/customer/portal/articles/2978772" target="_blank">{l s='More information.' mod='payplug'}</a>
            </p>
        </div>
    </div>
    <div class="panel-row ppdeferredchecked">
        <div class="block-right">
            <p class="pptips">
                <span>
                    <input type="checkbox" name="PAYPLUG_DEFERRED_AUTO" value="1" id="payplug_deferred_auto" {if $PAYPLUG_DEFERRED_AUTO == 1}checked="checked"{/if}>
                    <label class="vertical-top" for="payplug_deferred_auto">{l s='During the 7 days of the authorization, capture payments whose state is :' mod='payplug'}</label>
                    <select name="PAYPLUG_DEFERRED_STATE" id="payplug_deferred_state"{if $PAYPLUG_DEFERRED_AUTO == 0} disabled="disabled"{/if}>
                        <option value="0">{l s='-- Choose an order state --' mod='payplug'}</option>
                        {foreach from=$order_states item=order_state}
                            <option value="{$order_state.id_order_state}"{if $PAYPLUG_DEFERRED_STATE == $order_state.id_order_state} selected="selected"{/if}>{$order_state.name}</option>
                        {/foreach}
                    </select>
                    <span id="deferred_config_error" class="hide">{l s='You have to choose an order state.' mod='payplug'}</span>
                </span>
            </p>
        </div>
    </div>
</div>
