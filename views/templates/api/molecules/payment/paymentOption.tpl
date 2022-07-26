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

<div class="paymentOption
    -{$paymentOptionIdentifier|escape:'htmlall':'UTF-8'}
    {if isset($paymentOptionClassName) && $paymentOptionClassName} -{$paymentOptionClassName}{/if}">
    {if 'standard' == $paymentOptionIdentifier}
        {assign var="paymentOptionClassName" value='paymentOption_switch'}
    {else}
        {assign var="paymentOptionClassName" value='paymentOption_switch -premium'}
    {/if}
    {include
        file='./../../atoms/switch/switch.tpl'
        switchClassName=$paymentOptionClassName
        switchDataName='paymentMethod_'|cat:$paymentOptionIdentifier|escape:'htmlall':'UTF-8'
        switchName='payplug_'|cat:$paymentOptionIdentifier|escape:'htmlall':'UTF-8'
        switchChecked=$paymentOptionChecked}
    {if isset($paymentOptionImage_url) && $paymentOptionImage_url}
        <div class="_logo">
            <img src="{$paymentOptionImage_url|escape:'htmlall':'UTF-8'}">
        </div>
    {/if}
    <div class="_text">
        {include
            file='./../../atoms/title/title.tpl'
            titleClassName='_title'
            titleText=$paymentOptionName|escape:'htmlall':'UTF-8'}


        <p {if 'standard' !== $paymentOptionIdentifier}class="_liveDescription" {/if} >
            {$paymentOptionDescription|escape:'htmlall':'UTF-8'}
            {if isset($paymentOptionLink) && $paymentOptionLink}
                {capture assign="paymentOptionLinkText"}{l s='paymentOption.link.text' mod='payplug'}{/capture}
                {include
                    file='./../../atoms/link/link.tpl'
                    linkText=$paymentOptionLinkText|escape:'htmlall':'UTF-8'
                    linkHref=$paymentOptionLink|escape:'htmlall':'UTF-8'
                    linkTarget='_blank'
                    linkData='data-link'}
            {/if}
        </p>
        {if 'standard' !== $paymentOptionIdentifier}
            <p class="_sandboxDescription" >
                {if isset($paymentSandboxOptionDescription)}{$paymentSandboxOptionDescription}{/if}
            </p>
        {/if}
    </div>
    {if isset($paymentOptionInformations) && $paymentOptionInformations}
        <div class="_informations -disabled">
            {$paymentOptionInformations}
        </div>
    {/if}
    {if 'standard' !== $paymentOptionIdentifier }
            <div class="options"></div>
    {/if}
</div>
