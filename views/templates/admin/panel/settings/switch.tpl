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
<div class="payplugSwitch{if !$switch.checked || !$switch.active} payplugSwitch-right{/if}{if isset($switch.small) && $switch.small} payplugSwitch-small{/if}{if !$switch.active} payplugSwitch-disabled{/if}">
    <input type="radio" name="{$switch.name|escape:'htmlall':'UTF-8'}" value="1" id="{$switch.name|escape:'htmlall':'UTF-8'}_left" {if $switch.checked}checked="checked"{/if}>
    <input type="radio" name="{$switch.name|escape:'htmlall':'UTF-8'}" value="0" id="{$switch.name|escape:'htmlall':'UTF-8'}_right" {if !$switch.checked}checked="checked"{/if}>

    {if isset($switch.label_left) && $switch.label_left}<label for="{$switch.name|escape:'htmlall':'UTF-8'}_left" class="payplugSwitch_label payplugSwitch_label-left">{$switch.label_left|escape:'htmlall':'UTF-8'}</label>{/if}
    {if isset($switch.label_right) && $switch.label_right}<label for="{$switch.name|escape:'htmlall':'UTF-8'}_right" class="payplugSwitch_label payplugSwitch_label-right">{$switch.label_right|escape:'htmlall':'UTF-8'}</label>{/if}
</div>
