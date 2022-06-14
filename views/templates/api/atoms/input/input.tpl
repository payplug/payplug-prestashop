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

{if !isset($inputType) || !$inputType}
    {assign var='inputType' value='text'}
{/if}

<div class="payplugUIInput
    {if isset($inputClassName) && $inputClassName} {$inputClassName|escape:'htmlall':'UTF-8'}{/if}
    {if isset($inputIcon) && $inputIcon} -icon -icon{$inputIcon|escape:'htmlall':'UTF-8'}{/if}
    {if isset($inputDisabled) && $inputDisabled} -disabled{/if}">
    {if isset($inputLabel) && $inputLabel}
        <label for="{$inputName|escape:'htmlall':'UTF-8'}">{$inputLabel|escape:'htmlall':'UTF-8'}</label>
    {/if}
    <input
            type="{$inputType|escape:'htmlall':'UTF-8'}"
            id="{$inputName|escape:'htmlall':'UTF-8'}"
            name="{$inputName|escape:'htmlall':'UTF-8'}"
            {if isset($inputData) && $inputData} data-e2e-name="{$inputData|escape:'htmlall':'UTF-8'}"{/if}
            {if isset($inputValue) && $inputValue} value="{$inputValue|escape:'htmlall':'UTF-8'}"{/if}
            {if isset($inputPlaceholder) && $inputPlaceholder} placeholder="{$inputPlaceholder|escape:'htmlall':'UTF-8'}"{/if}
            {if $inputType=='number'}
                step="{if isset($inputStep)}{$inputStep|escape:'htmlall':'UTF-8'}{else}1{/if}"
                min="{if isset($inputMin)}{$inputMin|escape:'htmlall':'UTF-8'}{else}0{/if}"
                max="{if isset($inputMax)}{$inputMax|escape:'htmlall':'UTF-8'}{else}3000{/if}"
            {/if}
            {if isset($inputDisabled) && $inputDisabled} disabled{/if}>
</div>