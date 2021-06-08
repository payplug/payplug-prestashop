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

{if isset($can_use_oney_fees) && $can_use_oney_fees}
    <div class="payplugOneyFees">
        <label class="payplugOneyFees_option{if $payplug_switch.oney_fees.checked} -selected{/if}">
            <span class="payplugOneyFees_title">{l s='admin.panel.setting.oney.withFees' mod='payplug'}</span>
            <span class="payplugOneyFees_content">{l s='admin.panel.setting.oney.withFeesText' mod='payplug'}</span>
            <span class="payplugOneyFees_state">{l s='admin.panel.setting.oney.activate' mod='payplug'}</span>
            <span class="payplugOneyFees_checker">
                <input type="radio"
                        id="{$payplug_switch.oney_fees.name|escape:'htmlall':'UTF-8'}_left"
                        name="{$payplug_switch.oney_fees.name|escape:'htmlall':'UTF-8'}"
                        value="1"
                        {if $payplug_switch.oney_fees.checked} checked="checked"{/if}/>
            </span>
        </label>
        <label class="payplugOneyFees_option{if !$payplug_switch.oney_fees.checked} -selected{/if}">
            <span class="payplugOneyFees_title">{l s='admin.panel.setting.oney.withoutFees' mod='payplug'}</span>
            <span class="payplugOneyFees_content">{l s='admin.panel.setting.oney.withoutFeesText' mod='payplug'}</span>
            <span class="payplugOneyFees_state">{l s='admin.panel.setting.oney.activate' mod='payplug'}</span>
            <span class="payplugOneyFees_checker">
                <input type="radio"
                        id="{$payplug_switch.oney_fees.name|escape:'htmlall':'UTF-8'}_right"
                        name="{$payplug_switch.oney_fees.name|escape:'htmlall':'UTF-8'}"
                        value="0"
                        {if !$payplug_switch.oney_fees.checked} checked="checked"{/if}/>
            </span>
        </label>
    </div>
{/if}
