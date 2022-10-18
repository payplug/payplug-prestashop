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
<div class="payplugUIIOptions
    {if isset($optionsClassName) && $optionsClassName} {$optionsClassName|escape:'htmlall':'UTF-8'}{/if}">
    {foreach $optionsItems as $optionsItem}
        <label class="{if isset($optionsItem.className) && $optionsItem.className} {$optionsItem.className|escape:'htmlall':'UTF-8'}{/if}{if isset($optionsItem.disabled) && $optionsItem.disabled} -disabled{/if}" {if isset($optionsItem.dataName) && $optionsItem.dataName} data-e2e-name="{$optionsItem.dataName|escape:'htmlall':'UTF-8'}"{/if}>
            <input
                    type="radio" name="{$optionsName|escape:'htmlall':'UTF-8'}"
            value="{$optionsItem.value|escape:'htmlall':'UTF-8'}"
                    {if isset($optionsSelected) && isset($optionsItem.value) && $optionsSelected == $optionsItem.value } checked="checked" {/if}
                    {if isset($optionsItem.disabled) && $optionsItem.disabled} disabled{/if}
                    data-notallowed="{if isset($optionsItem.notallowed) && $optionsItem.notallowed}{$optionsItem.notallowed|escape:'htmlall':'UTF-8'}{else}0{/if}">
            <span>
                {if isset($optionsItem.text) && $optionsItem.text}{$optionsItem.text|escape:'htmlall':'UTF-8'}{/if}
                {if isset($optionsItem.subText) && $optionsItem.subText}<span>{$optionsItem.subText}</span>{/if}
            </span>
        </label>
    {/foreach}
</div>
