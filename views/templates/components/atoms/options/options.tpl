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
    {foreach $items as $item}
        <label class="{if isset($item.className) && $item.className} {$item.className|escape:'htmlall':'UTF-8'}{/if}{if isset($item.disabled) && $item.disabled} -disabled{/if}">
            <input
                    type="radio" name={$optionsName|escape:'htmlall':'UTF-8'}
                    {if isset($item.dataName) && $item.dataName} data-e2e-name="{$item.dataName|escape:'htmlall':'UTF-8'}"{/if}
                    value="{$item.value|escape:'htmlall':'UTF-8'}"
                    {if isset($optionsSelected) && isset($item.value) && $optionsSelected == $item.value } checked="checked" {/if}
                    {if isset($item.disabled) && $item.disabled} disabled{/if}>
            <div>
                {if isset($item.text) && $item.text}{$item.text}{/if}
                {if isset($item.subText) && $item.subText}<p>{$item.subText}</p>{/if}
            </div>
        </label>
    {/foreach}
</div>
