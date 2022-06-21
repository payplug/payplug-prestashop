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
{if isset($selectOptions) && $selectOptions}
    {assign var='defaultValue' value=false}

    {foreach $selectOptions as $option}
        {if isset($option.selected) && $option.selected && !$defaultValue}
            {assign var='defaultValue' value=$option.key}
        {/if}
    {/foreach}

    {if !$defaultValue}
        {assign var='firstOption' value=$selectOptions|reset}
        {assign var='defaultValue' value=$firstOption.key}
    {/if}

    <div class="payplugUISelect
        {if isset($selectDisabled) && $selectDisabled} -disabled{/if}
        {if isset($selectClassName) && $selectClassName} {$selectClassName|escape:'htmlall':'UTF-8'}{/if}"
        {if isset($selectData) && $selectData} data-e2e-name="{$selectData|escape:'htmlall':'UTF-8'}"{/if}>
        <div class="_current" {if !isset($selectDisabled) || !$selectDisabled} tabindex="1"{/if}>
            {foreach $selectOptions as $option}
                <div class="_value">
                    <input
                            class="_input"
                            type="radio"
                            id="{$selectName|escape:'htmlall':'UTF-8'}-{$option.key|escape:'htmlall':'UTF-8'}"
                            value="{$option.key|escape:'htmlall':'UTF-8'}"
                            name="{$selectName|escape:'htmlall':'UTF-8'}"
                            data-e2e-name="{$selectName|escape:'htmlall':'UTF-8'}-{$option.key|escape:'htmlall':'UTF-8'}"
                            {if $defaultValue == $option.key} checked="checked"{/if}>
                    <span class="_text">{$option.value|escape:'htmlall':'UTF-8'}</span>
                </div>
            {/foreach}
        </div>
        <div class="_listWrapper{if isset($selectScrollbar) && $selectScrollbar} -scrollbar{/if}">
            <div class="_list">
                <ul>
                    {foreach $selectOptions as $option}
                        <li>
                            <label class="_option"
                                   for="{$selectName|escape:'htmlall':'UTF-8'}-{$option.key|escape:'htmlall':'UTF-8'}"
                                   aria-hidden="aria-hidden">{$option.value|escape:'htmlall':'UTF-8'}</label>
                        </li>
                    {/foreach}
                </ul>
            </div>
        </div>
    </div>
{/if}