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
        <div class="payplugPanel_label">{l s='Enable one-click payments' mod='payplug'}</div>
        <div class="payplugPanel_content">{include file='./switch.tpl' switch=$payplug_switch.one_click}</div>
    </div>
    <div class="payplugPanel">
        <div class="payplugPanel_content">
            <p>
                {l s='Allow customers to save their credit card information for later purchases' mod='payplug'}
                <a class="payplugLink" href="{$faq_links.one_click}" target="_blank">{l s='Learn more.' mod='payplug'}</a>
            </p>
        </div>
    </div>
</div>
