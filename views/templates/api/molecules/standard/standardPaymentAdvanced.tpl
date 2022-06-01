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
{* Standard Payment Advanced Settings *}
{capture assign="standardAdvancedTitle"}{l s='standard.block.accordionTitle' mod='payplug'}{/capture}

{capture assign="standardAdvancedContent"}

    {* Installment Block *}

    {* Installment Title *}
    {capture assign="installmentTitle"}{l s='standard.block.installmentTitle' mod='payplug'}{/capture}
    {* Installment Switch *}
    {capture assign="installmentSwitch"}
        {include file='./../../atoms/switch/switch.tpl'
        switchEnabledLabel='On'
        switchDisabledLabel='Off'
        switchDataName='installmentSwitch'
        switchChecked=$payplug_switch.installment.checked
        switchClassName="installmentSwitch"
        switchName='installmentSwitch'}
    {/capture}

    {* Installment Content *}
    {capture assign="installmentContent"}
        <div class="_inputs">
            <p>{l s='standard.block.installmentBeforeText' mod='payplug'}</p>
            {capture assign="twotimes"}{l s='standard.block.installments.2times' mod='payplug'}{/capture}
            {capture assign="threetimes"}{l s='standard.block.installments.3times' mod='payplug'}{/capture}
            {capture assign="fourtimes"}{l s='standard.block.installments.4times' mod='payplug'}{/capture}
            {assign var='installmentsSelect' value=[
                ['key' => '0', 'value' => {$twotimes}|escape:'htmlall':'UTF-8', 'selected' => ($inst_mode == 2)],
                ['key' => '1', 'value' => {$threetimes}|escape:'htmlall':'UTF-8', 'selected' => ($inst_mode == 3)],
                ['key' => '2', 'value' => {$fourtimes}|escape:'htmlall':'UTF-8', 'selected' => ($inst_mode == 4)]
            ]}
            {include file='./../../atoms/select/select.tpl'
            selectClassName='installments_panel_select'
            selectName='installments_panel_select'
            selectOptions=$installmentsSelect}
            <p>{l s='standard.block.installmentBetweenText' mod='payplug'}</p>
            {include file='./../../atoms/input/input.tpl'
            inputType='number'
            inputMin=4
            inputMax=20000
            inputValue=$inst_min_amount|escape:'htmlall':'UTF-8'
            inputIcon='Euro'
            inputClassName='maxThreshold'
            inputName='payplug_oney_custom_max_amounts'
            inputData='oneyThresholdMax'}
        </div>
        <div class="_statement">
            {include file='./../../atoms/icon/icon.tpl'
            iconName='error'
            iconClassName='installmentErrorIcon'}
            <span class="installmentError" data-e2e-error="installmentError"></span>
        </div>

        <p>
            {l s='standard.block.installment.description' mod='payplug'}
            {if isset($installments_panel_url) && $installments_panel_url}
                {capture assign=installmentPanelLink}{$installments_panel_url}{/capture}
                {capture assign=installmentPanelLinkText}{l s='standard.block.installment.panel.link.text' mod='payplug'}{/capture}
                {include file='./../../atoms/link/link.tpl'
                linkText=$installmentPanelLinkText|escape:'htmlall':'UTF-8'
                linkHref=$installmentPanelLink|escape:'htmlall':'UTF-8'
                linkTarget='_blank'
                linkData='data-panelInstallmentLink'}
            {/if}
            {if isset($faq_links.installments) && $faq_links.installments}
                {capture assign=installmentFaqLink}{$faq_links.installments}{/capture}
                {capture assign=installmentFaqLinkText}{l s='standard.block.installment.faq.link.text' mod='payplug'}{/capture}
                {include file='./../../atoms/link/link.tpl'
                linkText=$installmentFaqLinkText|escape:'htmlall':'UTF-8'
                linkHref=$installmentFaqLink|escape:'htmlall':'UTF-8'
                linkTarget='_blank'
                linkData='data-faqInstallmentLink'}
            {/if}
        </p>
        {capture assign="installmentAlertText"}{l s='standard.block.installmentAlertContent' tags=['<br>'] mod='payplug'}{/capture}
        {include file='./../../atoms/textAlert/textAlert.tpl'
        textAlertType='warning'
        textAlertText=$installmentAlertText}
    {/capture}

    {* Deferred Block *}

    {* Deferred Title *}
    {capture assign="deferredTitle"}{l s='standard.block.deferredTitle' mod='payplug'}{/capture}
    {* Deferred Switch *}
    {capture assign="deferredSwitch"}
        {include file='./../../atoms/switch/switch.tpl'
        switchEnabledLabel='On'
        switchDisabledLabel='Off'
        switchDataName='deferredSwitch'
        switchChecked=$payplug_switch.deferred.checked
        switchClassName="deferredSwitch"
        switchName='deferredSwitch'}
    {/capture}

    {* Deferred Content *}
    {capture assign="deferredContent"}
        <p>{l s='standard.block.deferred.description' mod='payplug'}
            {if isset($faq_links.deferred) && $faq_links.deferred}
                {capture assign=deferredFaqLink}{$faq_links.deferred}{/capture}
                {capture assign=deferredFaqLinkText}{l s='standard.block.deferred.faq.link.text' mod='payplug'}{/capture}
                {include file='./../../atoms/link/link.tpl'
                linkText=$deferredFaqLinkText|escape:'htmlall':'UTF-8'
                linkHref=$deferredFaqLink|escape:'htmlall':'UTF-8'
                linkTarget='_blank'
                linkData='data-faqdeferredLink'}
            {/if}
        </p>

        <div class="_inputs">
            <p>{l s='standard.block.deferredBeforeText' mod='payplug'}</p>

            {assign var='deferredSelect' value=$order_states_values}
            {include file='./../../atoms/select/select.tpl'
            selectClassName='deferred_panel_select'
            selectName='deferred_panel_select'
            selectOptions=$deferredSelect}
        </div>

        {capture assign="deferredAlertText"}{l s='standard.block.deferredAlertContent' mod='payplug'}{/capture}
        {include file='./../../atoms/textAlert/textAlert.tpl'
        textAlertType='warning'
        textAlertText=$deferredAlertText}
    {/capture}

    {assign var='standardAdvancedOptions' value=[
    [
    'className' => 'installment',
    'title' => $installmentTitle,
    'switch' => $installmentSwitch,
    'content' => $installmentContent
    ],
    [
    'className' => 'deferred',
    'title' => $deferredTitle,
    'switch' => $deferredSwitch,
    'content' => $deferredContent
    ]
    ]}
    {foreach $standardAdvancedOptions as $standardAdvancedOption}
        {include file='./standardPaymentAdvancedOption.tpl'
        standardAdvancedOptionClassName=$standardAdvancedOption.className
        standardAdvancedOptionTitle=$standardAdvancedOption.title
        standardAdvancedOptionSwitch=$standardAdvancedOption.switch
        standardAdvancedOptionContent=$standardAdvancedOption.content}
    {/foreach}
{/capture}

{include file='./../../atoms/accordion/accordion.tpl'
    accordionIdentifier='standardPaymentAdvanced'
    accordionClassName='standardPaymentAdvanced'
    accordionData='standardAdvancedSettings'
    accordionLabel=$standardAdvancedTitle
    accordionContent=$standardAdvancedContent}