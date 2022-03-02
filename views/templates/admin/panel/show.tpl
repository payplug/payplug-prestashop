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
<div class="panel {$module_name}Show">
    <div class="panel-heading">{l s='Display to customers' mod={$module_name}}</div>
    {if !$connected}
        <p class="{$module_name}Alert -warning">{l s='Before being able to display PayPlug to your customers you need to connect your PayPlug account below.' mod={$module_name}}</p>
    {/if}
    <div class="panel-row">
        <div class="{$module_name}Panel">
            <div class="{$module_name}Panel_label">{l s='Show Payplug to my customers' mod={$module_name}}</div>
            <div class="{$module_name}Panel_content">{include file='./settings/switch.tpl' switch=$payplug_switch.show}</div>
        </div>
    </div>
</div>
