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
<div class="panel payplugShow">
    <div class="panel-heading">{l s='Display to customers' mod='payplug'}</div>
    {if !$connected}
        <p class="payplugAlert payplugAlert-warning">{l s='Before being able to display PayPlug to your customers you need to connect your PayPlug account below.' mod='payplug'}</p>
    {/if}
    <div class="panel-row">
        <div class="payplugPanel">
            <div class="payplugPanel_label">{l s='Show Payplug to my customers' mod='payplug'}</div>
            <div class="payplugPanel_content">{include file='./settings/switch.tpl' switch=$payplug_switch.show}</div>
        </div>
    </div>
</div>
