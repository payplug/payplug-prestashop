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
        switchName='payplug_inst'}
    {/capture}

    {* Installment Content *}
    {capture assign="installmentContent"}
        <div class="_inputs">
            <p>{l s='standard.block.installmentBeforeText' mod='payplug'}</p>
            {capture assign="twotimes"}{l s='standard.block.installments.2times' mod='payplug'}{/capture}
            {capture assign="threetimes"}{l s='standard.block.installments.3times' mod='payplug'}{/capture}
            {capture assign="fourtimes"}{l s='standard.block.installments.4times' mod='payplug'}{/capture}
            {assign var='installmentsSelect' value=[
                ['key' => 0, 'value' => {$twotimes}|escape:'htmlall':'UTF-8', 'selected' => ($inst_mode == 0)],
                ['key' => 1, 'value' => {$threetimes}|escape:'htmlall':'UTF-8', 'selected' => ($inst_mode == 1)],
                ['key' => 2, 'value' => {$fourtimes}|escape:'htmlall':'UTF-8', 'selected' => ($inst_mode == 2)]
            ]}
            {include file='./../../atoms/select/select.tpl'
            selectDisabled=!$payplug_switch.installment.checked
            selectClassName='installmentMode'
            selectName='payplug_inst_mode'
            selectOptions=$installmentsSelect
            selectData='installment_mode_select'}
            <p>{l s='standard.block.installmentBetweenText' mod='payplug'}</p>
            {include file='./../../atoms/input/input.tpl'
            inputDisabled=!$payplug_switch.installment.checked
            inputType='number'
            inputMin=4
            inputMax=20000
            inputValue=$inst_min_amount|escape:'htmlall':'UTF-8'
            inputIcon='Euro'
            inputClassName='installmentMinAmount'
            inputName='payplug_inst_min_amount'
            inputData='installmentMinAmount'}
        </div>
        <div class="_statement">
            {include file='./../../atoms/icon/icon.tpl'
            iconName='error'
            iconClassName='installmentErrorIcon'}
            <span class="installmentError" data-e2e-name="installment_amount_error"></span>
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

    {* Deferred Switch *}
    {capture assign="deferredSwitch"}
        {include file='./../../atoms/switch/switch.tpl'
            switchEnabledLabel='On'
            switchDisabledLabel='Off'
            switchDataName='deferredSwitch'
            switchChecked=$payplug_switch.deferred.checked
            switchClassName="deferredSwitch"
            switchName=$payplug_switch.deferred.name}
    {/capture}

    {* Deferred Content *}
    {capture assign="deferredContent"}
        <p>
            {l s='standard.block.deferred.description' mod='payplug'}
            {if isset($faq_links.deferred) && $faq_links.deferred}
                {include file='./../../atoms/link/link.tpl'
                    linkText={l s='standard.block.deferred.faq.link.text' mod='payplug'}
                    linkHref=$faq_links.deferred
                    linkTarget='_blank'
                    linkData='data-faqdeferredLink'}
            {/if}
        </p>
        <div class="_inputs">
            <p>{l s='standard.block.deferredBeforeText' mod='payplug'}</p>
            {include file='./../../atoms/select/select.tpl'
                selectClassName='-deferredState'
                selectName='payplug_deferred_state'
                selectData='deferredSelect'
                selectScrollbar=true
                selectOptions=$order_states_values}
        </div>
        {include file='./../../atoms/textAlert/textAlert.tpl'
            textAlertType='warning'
            textAlertText={l s='standard.block.deferredAlertContent' mod='payplug'}
        }
    {/capture}

    {if $installment_isActivated }
        {include file='./standardPaymentAdvancedOption.tpl'
            standardAdvancedOptionClassName='installment'
            standardAdvancedOptionTitle=$installmentTitle
            standardAdvancedOptionSwitch=$installmentSwitch
            standardAdvancedOptionContent=$installmentContent}
    {/if}
    {if $deferred_isActivated }
        {include file='./standardPaymentAdvancedOption.tpl'
            standardAdvancedOptionClassName='deferred'
            standardAdvancedOptionTitle={l s='standard.block.deferredTitle' mod='payplug'}
            standardAdvancedOptionSwitch=$deferredSwitch
            standardAdvancedOptionContent=$deferredContent}
    {/if}
{/capture}

{include file='./../../atoms/accordion/accordion.tpl'
    accordionIdentifier='standardPaymentAdvanced'
    accordionClassName='standardPaymentAdvanced'
    accordionData='standardAdvancedSettings'
    accordionLabel=$standardAdvancedTitle
    accordionContent=$standardAdvancedContent}