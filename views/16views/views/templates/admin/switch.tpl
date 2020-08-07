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
<label class="left-block">{$switch.label|escape:'htmlall':'UTF-8'}</label>
<div class="block-right">
    <span class="switch prestashop-switch fixed-width-lg">
        <input type="radio" class="switch-input{if !$switch.active} ppdisabled{/if}{if !$premium} not_premium{/if}" name="{$switch.name|escape:'htmlall':'UTF-8'}" value="1"
               id="{$switch.name|escape:'htmlall':'UTF-8'}_left" {if $switch.checked}checked="checked"{/if}>
        <label title="{$switch.label_left|escape:'htmlall':'UTF-8'}" for="{$switch.name|escape:'htmlall':'UTF-8'}_left"
               class="switch-label switch-label-on{if !$switch.active} ppdisabled{/if}">{$switch.label_left|escape:'htmlall':'UTF-8'}</label>
        <input type="radio" class="switch-input{if !$switch.active} ppdisabled{/if}" name="{$switch.name|escape:'htmlall':'UTF-8'}" value="0" id="{$switch.name|escape:'htmlall':'UTF-8'}_right"
               {if !$switch.checked}checked="checked"{/if}>
        <label title="{$switch.label_right|escape:'htmlall':'UTF-8'}" for="{$switch.name|escape:'htmlall':'UTF-8'}_right"
               class="switch-label switch-label-off{if !$switch.active} ppdisabled{/if}">{$switch.label_right|escape:'htmlall':'UTF-8'}</label>
        <span class="switch-selection{if !$switch.active} ppdisabled{/if}"></span>
        <a class="slide-button btn" {if !$switch.checked}style="left: 50%"{/if}></a>
    </span>
</div>
