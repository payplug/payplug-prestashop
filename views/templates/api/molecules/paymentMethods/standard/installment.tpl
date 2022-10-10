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

<div class="_inputs">
    <p>{l s='installment.beforeText' mod='payplug'}</p>
    {capture assign="twotimes"}{l s='installment.2times' mod='payplug'}{/capture}
    {capture assign="threetimes"}{l s='installment.3times' mod='payplug'}{/capture}
    {capture assign="fourtimes"}{l s='installment.4times' mod='payplug'}{/capture}
    {assign var='installmentsSelect' value=[
        ['key' => 2, 'value' => {$twotimes}|escape:'htmlall':'UTF-8', 'selected' => ($inst_mode == 2)],
        ['key' => 3, 'value' => {$threetimes}|escape:'htmlall':'UTF-8', 'selected' => ($inst_mode == 3)],
        ['key' => 4, 'value' => {$fourtimes}|escape:'htmlall':'UTF-8', 'selected' => ($inst_mode == 4)]
    ]}
    {include file='./../../../atoms/select/select.tpl'
        selectDisabled=!$payplug_switch.installment.checked
        selectClassName='installmentMode'
        selectName='payplug_inst_mode'
        selectOptions=$installmentsSelect
        selectData='installment_mode_select'}
    <p>{l s='installment.betweenText' mod='payplug'}</p>
    {include file='./../../../atoms/input/input.tpl'
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
    {include file='./../../../atoms/icon/icon.tpl'
        iconName='error'
        iconClassName='installmentErrorIcon'}
    <span class="installmentError" data-e2e-name="installment_amount_error"></span>
</div>
<p>
    {l s='installment.description' mod='payplug'}
    {if isset($installments_panel_url) && $installments_panel_url}
        {include file='./../../../atoms/link/link.tpl'
            linkText={l s='installment.textLink' mod='payplug'}
            linkHref=$installments_panel_url
            linkTarget='_blank'
            linkData='data-panelInstallmentLink'}
    {/if}
    {if isset($faq_links.installments) && $faq_links.installments}
        {include file='./../../../atoms/link/link.tpl'
            linkText={l s='installment.faqLink' mod='payplug'}
            linkHref=$faq_links.installments|escape:'htmlall':'UTF-8'
            linkTarget='_blank'
            linkData='data-faqInstallmentLink'}
    {/if}
</p>
{include file='./../../../atoms/textAlert/textAlert.tpl'
    textAlertType='warning'
    textAlertText={l s='installment.alertContent' tags=['<br>'] mod='payplug'}}