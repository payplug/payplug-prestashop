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
{* paylater Block *}
{capture assign="paylaterBlock_title"}{l s='paylater.block.title' mod='payplug'}{/capture}
{capture assign="payLaterBlock_description"}{l s='paylater.block.description' mod='payplug'}{/capture}
{capture assign="paylaterBlock_content"}
    {capture assign="paylaterTitle"}{l s='paylater.block.detailsTitle' mod='payplug'}{/capture}
    {capture assign="paylaterDescription"}{l s='paylater.block.detailsDescription' mod='payplug'}{/capture}
    {capture assign='paylaterAdvanced'}
        {* PayLater Options: oney with or without fees *}
        {capture assign="oneyWithFeesTitle"}{l s='paylater.block.oneyWithFeesTitle' mod='payplug'}{/capture}
        {capture assign="oneyWithFeesDescription"}{l s='paylater.block.oneyWithFeesDescription' mod='payplug'}{/capture}
        {capture assign="oneyWithoutFeesTitle"}{l s='paylater.block.oneyWithoutFeesTitle' mod='payplug'}{/capture}
        {capture assign="oneyWithoutFeesDescription"}{l s='paylater.block.oneyWithoutFeesDescription' mod='payplug'}{/capture}
        {assign var=items value=[
        ['value'=>"1", "dataName" =>'oneyWithFees' ,"text" => $oneyWithFeesTitle, "subText" => $oneyWithFeesDescription, className=>'_paylaterLabel'],
        ['value'=>"0", "dataName" =>'oneyWithoutFees' ,"text" => $oneyWithoutFeesTitle, "subText" => $oneyWithoutFeesDescription, className=>'_paylaterLabel']
        ]}
        {include file='./../../atoms/options/options.tpl'
        optionsSelected=$payplug_switch.oney_fees.checked
        optionsClassName='_paylaterOptions'
        optionsName=$payplug_switch.oney_fees.name}

        {* Hide optimisedOption for pspaylater module *}
        {if $module_name=='pspaylater'}
            {include file='./../../atoms/input/input.tpl'
            inputType = 'hidden'
            inputValue = '1'
            inputName = $payplug_switch.oney_optimized.name}
        {/if}

{*         Advanced Paylater Settings *}
        {include file='./paylater_advanced.tpl'}
    {/capture}

    {capture assign="oneyWithFeesTitle"}
        {l s='paylater.block.oneyWithFeesTitle' mod='payplug'}
    {/capture}

    {if 'pspaylater' == $module_name}
        {assign var="paylaterChecked" value=1}
    {else}
        {assign var="paylaterChecked" value=$payplug_switch.oney.checked}
    {/if}

    {include file='./../payment/paymentMethod.tpl'
        paymentOptionClassName = $module_name
        paymentOptionIdentifier = 'oney'
        paymentOptionName = $paylaterTitle
        paymentOptionDescription = $paylaterDescription
        paymentOptionChecked = $paylaterChecked
        paymentOptionLink = $faq_links.oney}
{/capture}

{assign var='paylaterBlock_className' value='paylaterBlock'}

{include file='./../../atoms/block/block.tpl'
    blockTitle=$paylaterBlock_title
    blockDescription=$payLaterBlock_description
    blockContent=$paylaterBlock_content
    blockData='blockPaylater'
    blockDisabled=!$connected || !$payplug_switch.show.checked
    blockClassName=$paylaterBlock_className}