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

{* Advanced Paylater Settings *}
{if $oney_belgium}
    {include file='./../../atoms/textAlert/textAlert.tpl'
    textAlertType='warning'
    textAlertText={l s='paylater.alertContent' tags=['<br>'] mod='payplug'}}
{/if}

{capture assign="paylaterAdvancedTitle"}{l s='paylater.block.accordionTitle' mod='payplug'}{/capture}
{capture assign="paylaterAdvancedContent"}
    {capture assign="thresholdsTitle"}{l s='paylater.block.thresholdsTitle' mod='payplug'}{/capture}
    {capture assign="thresholdsContent"}
        <p>{l s='paylater.block.thresholdsDescription' mod='payplug'}</p>
        <div class="_inputs">
            {include file='./../../atoms/input/input.tpl'
                inputType='number'
                inputMin='100'
                inputPlaceholder='100'
                inputValue=$oney_custom_min_amounts
                inputIcon='Euro'
                inputClassName='minThreshold'
                inputName='payplug_oney_custom_min_amounts'
                inputData='oneyThresholdMin'}
            <p>{l s='paylater.block.thresholdsBetweenText' mod='payplug'}</p>
            {include file='./../../atoms/input/input.tpl'
                inputType='number'
                inputPlaceholder='3000'
                inputValue=$oney_custom_max_amounts
                inputIcon='Euro'
                inputClassName='maxThreshold'
                inputName='payplug_oney_custom_max_amounts'
                inputData='oneyThresholdMax'}
        </div>
        <div class="_statement">
            {include file='./../../atoms/icon/icon.tpl'
            iconName='error'
            iconClassName='thresholdErrorIcon'}
            <span class="thresholdError" data-e2e-error="oneyThresholdError"></span>
        </div>
    {/capture}

    {capture assign="optimizedTitle"}{l s='paylater.block.optimizedTitle' mod='payplug'}{/capture}
    {capture assign="optimizedSwitch"}
        {include file='./../../atoms/switch/switch.tpl'
        switchEnabledLabel='On'
        switchDisabledLabel='Off'
        switchDataName='optimizedSwitch'
        switchChecked=$payplug_switch.oney_optimized.checked
        switchClassName="optimizedSwitch"
        switchName=$payplug_switch.oney_optimized.name}
    {/capture}
    {capture assign="optimizedContent"}
        {capture assign='faq_oneyBlock'}
            {capture assign=oneyFaqLink}{$faq_links.oney}#h_2595dd3d-a281-43ab-a51a-4986fecde5ee{/capture}
            {include file='./../../atoms/link/link.tpl'
                linkText=''
                linkHref=$oneyFaqLink
                linkData='faqOney'
                linkNoTag=true}
        {/capture}
        <p>{l s='paylater.block.optimizedDescription' tags=[$faq_oneyBlock] mod='payplug'}</p>
    {/capture}

    {capture assign="productOneyCtaTitle"}{l s='paylater.block.productOneyDisplayTitle' mod='payplug'}{/capture}
    {capture assign="productOneyCtaSwitch"}
        {include file='./../../atoms/switch/switch.tpl'
        switchEnabledLabel='On'
        switchDisabledLabel='Off'
        switchDataName='productOneyCtaTitleSwitch'
        switchChecked=$payplug_switch.oney_product_cta.checked
        switchClassName="productOneyCtaSwitch"
        switchName=$payplug_switch.oney_product_cta.name}
    {/capture}
    {capture assign="productOneyCtaContent"}

    {/capture}

    {capture assign="cartOneyCtaTitle"}{l s='paylater.block.cartOneyDisplayTitle' mod='payplug'}{/capture}
    {capture assign="cartOneyCtaSwitch"}
        {include file='./../../atoms/switch/switch.tpl'
        switchEnabledLabel='On'
        switchDisabledLabel='Off'
        switchDataName='cartOneyCtaTitleSwitch'
        switchChecked=$payplug_switch.oney_cart_cta.checked
        switchClassName="cartOneyCtaSwitch"
        switchName=$payplug_switch.oney_cart_cta.name}
    {/capture}
    {capture assign="cartOneyCtaContent"}
    {/capture}

    {assign var='paylaterBasicOptions' value=[
        [
            'className' => 'thresholds',
            'title' => $thresholdsTitle,
            'content' => $thresholdsContent,
            'switch' => ''
        ],
        [
            'className' => 'optimized',
            'title' => $optimizedTitle,
            'content' => $optimizedContent,
            'switch' => $optimizedSwitch
        ]

    ]}
    {assign var="paylaterCartAndProductOptions" value=[
        [
            'className' => 'productOneyCta',
            'title' => $productOneyCtaTitle,
            'content' => $productOneyCtaContent,
            'switch' =>$productOneyCtaSwitch
        ],
        [
            'className' => 'cartOneyCta',
            'title' => $cartOneyCtaTitle,
            'content' => $cartOneyCtaContent,
            'switch' => $cartOneyCtaSwitch
        ]
    ]}
    {if !($oney_belgium || $oney_spain)}
        {assign var='paylaterAdvancedOptions' value = $paylaterBasicOptions|array_merge:$paylaterCartAndProductOptions}
    {else}
        {assign var='paylaterAdvancedOptions' value = $paylaterBasicOptions}
    {/if}

    {foreach $paylaterAdvancedOptions as $paylaterAdvancedOption}
        {include file='./paylater_advanced_option.tpl'
            paylaterAdvancedOptionClassName=$paylaterAdvancedOption.className
            paylaterAdvancedOptionTitle=$paylaterAdvancedOption.title
            paylaterAdvancedOptionContent=$paylaterAdvancedOption.content
            paylaterAdvancedOptionSwitch=$paylaterAdvancedOption.switch}
    {/foreach}
{/capture}
{include file='./../../atoms/accordion/accordion.tpl'
    accordionIdentifier='payplugUIAccordion.identifier'
    accordionClassName='_paylaterAdvanced'
    accordionData='oneyAdvancedSettings'
    accordionLabel=$paylaterAdvancedTitle
    accordionContent=$paylaterAdvancedContent}