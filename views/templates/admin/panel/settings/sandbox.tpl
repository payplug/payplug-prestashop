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
        <div class="payplugPanel_label">{l s='Mode' mod='payplug'}</div>
        <div class="payplugPanel_content">{include file='./switch.tpl' switch=$payplug_switch.sandbox}</div>
    </div>
    <div class="payplugPanel">
        <div class="payplugPanel_content">
            <div class="payplugTips payplugTips-{$payplug_switch.sandbox.name|escape:'htmlall':'UTF-8'}">
                <div class="payplugTips_item payplugTips_item-left" {if !$payplug_switch.sandbox.checked}style="display: none;"{/if}>
                    {l s='In TEST mode all payments will be simulations and will not generate real transactions.' mod='payplug'}
                    <a class="payplugLink" href="http://support.payplug.com/customer/portal/articles/1701656" target="_blank">{l s='Learn more.' mod='payplug'}</a>
                </div>
                <div class="payplugTips_item payplugTips_item-right"{if $payplug_switch.sandbox.checked}style="display: none;"{/if}>
                    {l s='In LIVE mode the payments will generate real transactions.' mod='payplug'}
                </div>
            </div>
        </div>
    </div>
</div>
