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
        {include file='./../../atoms/switch/switch.tpl'
            switchEnabledLabel='On'
            switchDisabledLabel='Off'
            switchDataName='optimizedSwitch'
            switchChecked=$payplug_switch.oney_optimized.checked
            switchClassName="optimizedSwitch"
            switchName=$payplug_switch.oney_optimized.name}
    {/capture}

    {assign var='paylaterAdvancedOptions' value=[
        [
            'className' => 'thresholds',
            'img' => './../../../../img/svg/screen/paylater-thresholds.svg',
            'title' => $thresholdsTitle,
            'content' => $thresholdsContent
        ],
        [
            'className' => 'optimized',
            'img' => './../../../../img/svg/screen/paylater-optimized.svg',
            'title' => $optimizedTitle,
            'content' => $optimizedContent
        ]
    ]}
    {foreach $paylaterAdvancedOptions as $paylaterAdvancedOption}
        {include file='./paylater_advanced_option.tpl'
            paylaterAdvancedOptionClassName=$paylaterAdvancedOption.className
            paylaterAdvancedOptionImg=$paylaterAdvancedOption.img
            paylaterAdvancedOptionTitle=$paylaterAdvancedOption.title
            paylaterAdvancedOptionContent=$paylaterAdvancedOption.content}
    {/foreach}
{/capture}
{include file='./../../atoms/accordion/accordion.tpl'
    accordionIdentifier='payplugUIAccordion.identifier'
    accordionClassName='_paylaterAdvanced'
    accordionData='oneyAdvancedSettings'
    accordionLabel=$paylaterAdvancedTitle
    accordionContent=$paylaterAdvancedContent}