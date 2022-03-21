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
{capture assign="paylaterBlock_title"}{l s='paylater.block.title' mod={$module_name}}{/capture}
{capture assign="payLaterBlock_description"}{l s='paylater.block.description' mod={$module_name}}{/capture}
{capture assign="paylaterBlock_content"}
    {capture assign="detailsTitle"}{l s='paylater.block.detailsTitle' mod={$module_name}}{/capture}
    {include file='./../atoms/title/title.tpl' titleText=$detailsTitle}
    <div class="_details">
        {capture assign='faq_oneyBlock'}
            {include file='./../atoms/link/link.tpl'
                linkText=''
                linkHref=$faq_links.oney
                linkData='faqOney'
                linkNoTag=true}
        {/capture}
        {capture assign="detailsDescription"}{l s='paylater.block.detailsDescription' tags=[$faq_oneyBlock] mod={$module_name}}{/capture}
        {include file='./../atoms/paragraph/paragraph.tpl' paragraphText=$detailsDescription}
        {* Hide PayLater switch if module_name is pspaylater *}
        <div class="_detailsSwitch">
            {if $module_name=='pspaylater'}
                {include file='./../atoms/input/input.tpl'
                    inputType = 'hidden'
                    inputValue = '1'
                    inputName = $payplug_switch.oney.name}
            {else}
                {include file='./../atoms/switch/switch.tpl' switchEnabledLabel='On'
                    switchDisabledLabel='Off'
                    switchDataName='switchData'
                    switchValue='1'
                    checked=true
                    switchName=$payplug_switch.oney.name}
            {/if}
        </div>
    </div>

    {* PayLater Options: oney with or without fees *}
    {capture assign="oneyWithFeesTitle"}{l s='paylater.block.oneyWithFeesTitle' mod={$module_name}}{/capture}
    {capture assign="oneyWithFeesDescription"}{l s='paylater.block.oneyWithFeesDescription' mod={$module_name}}{/capture}
    {capture assign="oneyWithoutFeesTitle"}{l s='paylater.block.oneyWithoutFeesTitle' mod={$module_name}}{/capture}
    {capture assign="oneyWithoutFeesDescription"}{l s='paylater.block.oneyWithoutFeesDescription' mod={$module_name}}{/capture}
    {assign var=items value=[
        ['value'=>"1", "dataName" =>'oneyWithFees' ,"text" => $oneyWithFeesTitle, "subText" => $oneyWithFeesDescription],
        ['value'=>"0", "dataName" =>'oneyWithoutFees' ,"text" => $oneyWithoutFeesTitle, "subText" => $oneyWithoutFeesDescription]
    ]}
    {include file='./../atoms/options/options.tpl'
        optionsSelected='0'
        optionsName=$payplug_switch.oney_fees.name}

    {* Hide optimisedOption for pspaylater module *}
    {if $module_name=='pspaylater'}
        {include file='./../atoms/input/input.tpl'
            inputType = 'hidden'
            inputValue = '1'
            inputName = $payplug_switch.oney_optimized.name}
    {/if}
{/capture}

{assign var='paylaterBlock_className' value='paylaterBlock'}

{include file='./../atoms/block/block.tpl'
    blockTitle=$paylaterBlock_title
    blockDescription=$payLaterBlock_description
    blockContent=$paylaterBlock_content
    blockData='blockPaylater'
    blockDisabled=!$connected
    blockClassName=$paylaterBlock_className}