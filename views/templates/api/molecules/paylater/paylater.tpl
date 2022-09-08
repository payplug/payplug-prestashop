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
{capture assign="paylaterBlock_content"}
    {if 'pspaylater' == $module_name}
        {assign var="paylaterChecked" value=1}
    {else}
        {assign var="paylaterChecked" value=$payplug_switch.oney.checked}
    {/if}

    <div class="paymentMethod -{$paymentMethods.oney.name|escape:'htmlall':'UTF-8'}">
        {include file='./../../atoms/switch/switch.tpl'
            switchClassName='paymentMethod_switch -premium'
            switchDataName='paymentMethod_oney'
            switchName=$paymentMethods.oney.name|escape:'htmlall':'UTF-8'
            switchChecked=$paymentMethods.oney.checked}

        <div class="_text">
            {include
                file='./../../atoms/title/title.tpl'
                titleClassName='_title'
                titleText=$paymentMethods.oney.title|escape:'htmlall':'UTF-8'}

            <p {if isset($paymentMethods.oney.description) && $paymentMethods.oney.description != ''}
                class="-live _description" {/if}>
                {$paymentMethods.oney.description|escape:'htmlall':'UTF-8'}
                {if isset($paymentMethods.oney.link) && $paymentMethods.oney.link}
                    {include
                        file='./../../atoms/link/link.tpl'
                        linkText={l s='paymentMethod.link' mod='payplug'}
                        linkHref=$paymentMethods.oney.link|escape:'htmlall':'UTF-8'
                        linkTarget='_blank'
                        linkData='data-link'}
                {/if}
            </p>

        </div>
        <div class="_additionnal">
            {* PayLater Options: oney with or without fees *}
            {assign var=items value=[
                [
                    'value' => '1',
                    'dataName' => 'oneyWithFees' ,
                    'text' => {l s='paylater.block.oneyWithFeesTitle' mod='payplug'},
                    'subText' => {l s='paylater.block.oneyWithFeesDescription' mod='payplug'},
                    'className' => '_paylaterLabel'
                ],
                [
                    'value'=> '0',
                    'dataName' => 'oneyWithoutFees' ,
                    'text' => {l s='paylater.block.oneyWithoutFeesTitle' mod='payplug'},
                    'subText' => {l s='paylater.block.oneyWithoutFeesDescription' mod='payplug'},
                    'className' => '_paylaterLabel'
                ]
            ]}

            {include file='./../../atoms/options/options.tpl'
                optionsItems=$items
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

            {* Advanced Paylater Settings *}
            {include file='./paylater_advanced.tpl'}
        </div>
    </div>
{/capture}

{assign var='paylaterBlock_className' value='paylaterBlock'}

{include file='./../../atoms/block/block.tpl'
    blockTitle={l s='paylater.block.title' mod='payplug'}
    blockDescription={l s='paylater.block.description' mod='payplug'}
    blockContent=$paylaterBlock_content
    blockData='blockPaylater'
    blockDisabled=!$connected || !$payplug_switch.show.checked
    blockClassName=$paylaterBlock_className}