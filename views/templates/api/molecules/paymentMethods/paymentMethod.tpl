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
{if $paymentMethodName != 'oney'}
    <div class="paymentMethod -{$paymentMethodName|escape:'htmlall':'UTF-8'}{if isset($paymentMethod.useSandbox) && $paymentMethod.useSandbox} -useSandbox{/if}">
        {if isset($paymentMethod.premium) && $paymentMethod.premium}
            {assign var="paymentMethodClassName" value='paymentMethod_switch -premium'}
        {else}
            {assign var="paymentMethodClassName" value='paymentMethod_switch'}
        {/if}
        {include file='./../../atoms/switch/switch.tpl'
        switchClassName=$paymentMethodClassName
        switchDataName='paymentMethod_'|cat:$paymentMethodName|escape:'htmlall':'UTF-8'
        switchName=$paymentMethod.name|escape:'htmlall':'UTF-8'
        switchChecked=$paymentMethod.checked}
        {if isset($paymentMethod.image_url) && $paymentMethod.image_url}
            <div class="_logo">
                <img src="{$paymentMethod.image_url|escape:'htmlall':'UTF-8'}">
            </div>
        {/if}
        <div class="_text">
            {include file='./../../atoms/title/title.tpl'
            titleClassName='_title'
            titleText=$paymentMethod.title|escape:'htmlall':'UTF-8'}

            {if $paymentMethod.description}
                {foreach $paymentMethod.description as $modifier => $description}
                    <p class="_description -{$modifier}">
                        {$description.text|escape:'htmlall':'UTF-8'}
                        {if isset($description.link) && $description.link}
                            {include file='./../../atoms/link/link.tpl'
                            linkText={l s='paymentMethod.link' mod='payplug'}
                            linkHref=$description.link|escape:'htmlall':'UTF-8'
                            linkTarget='_blank'
                            linkData='data-link'}
                        {/if}
                    </p>
                {/foreach}
            {/if}
        </div>
        {if (isset($paymentMethod.options) && $paymentMethod.options) || (isset($paymentMethod.advancedOptions) && $paymentMethod.advancedOptions)}
            <div class="_additionnal">
                {if isset($paymentMethod.options) && $paymentMethod.options}
                    <div class="_options">
                        {foreach $paymentMethod.options as $option}
                            {include file='./paymentMethodOption.tpl' paymentMethodOption=$option}
                        {/foreach}
                    </div>
                {/if}
                {if isset($paymentMethod.advancedOptions) && $paymentMethod.advancedOptions}
                    {capture assign="advancedOptions"}
                        {foreach $paymentMethod.advancedOptions as $advancedOptionName => $advancedOption}
                            {include file='./paymentMethodAdvancedOption.tpl'
                            paymentMethodAdvancedOptionChecked=$advancedOption.checked
                            paymentMethodAdvancedOptionName=$advancedOptionName
                            paymentMethodAdvancedOption=$advancedOption}
                        {/foreach}
                    {/capture}

                    {include file='./../../atoms/accordion/accordion.tpl'
                    accordionClassName='_advancedOptions'
                    accordionIdentifier=$paymentMethodName|cat:'AdvancedSettings'
                    accordionData=$paymentMethodName|cat:'AdvancedSettings'
                    accordionLabel={l s='paymentMethod.advancedOptions' mod='payplug'}
                    accordionContent=$advancedOptions}
                {/if}
            </div>
        {/if}
    </div>
{/if}
