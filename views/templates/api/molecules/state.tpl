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
{capture assign="stateBlock_title"}{l s='state.block.title' mod='payplug'}{/capture}
{capture assign="stateBlock_description"}{l s='state.block.description' mod='payplug'}{/capture}
{capture assign="stateBlock_content"}

    {capture assign="stateBlock_alertText"}
        {l s='state.block.textAlert' mod='payplug'}
    {/capture}

    {capture assign="accordionContent"}

        <div class="_stateAlert">
            {include file="./../atoms/textAlert/textAlert.tpl"
            textAlertType='warning'
            textAlertText=$stateBlock_alertText}
        </div>

        <div class="_stateContent">

            {capture assign="stateBlock_stateButton"}
                {l s='state.block.stateButton' mod='payplug'}
            {/capture}
            {include file='./../atoms/button/button.tpl'
                buttonName='stateButton'
                buttonStyle='tertiary'
                buttonText=$stateBlock_stateButton}
            <div class="indicators">
                <div class="indicator">
                    {capture assign="stateBlock_stateCurl"}
                        {l s='state.block.stateCurl' mod='payplug'}
                    {/capture}
                    {include file='./../atoms/icon/icon.tpl'
                    iconClassName="{$check_configuration.status.curl}Class"
                    iconName=$check_configuration.status.curl}
                    {include file='./../atoms/paragraph/paragraph.tpl'
                    paragraphText=$stateBlock_stateCurl}
                </div>
                <div class="indicator">
                    {capture assign="stateBlock_statePhp"}
                        {l s='state.block.statePhp' mod='payplug'}
                    {/capture}
                    {include file='./../atoms/icon/icon.tpl'
                    iconClassName="{$check_configuration.status.php}Class"
                    iconName=$check_configuration.status.php}
                    {include file='./../atoms/paragraph/paragraph.tpl'
                    paragraphText=$stateBlock_statePhp}
                </div>
                <div class="indicator">
                    {capture assign="stateBlock_stateSsl"}
                        {l s='state.block.stateSsl' mod='payplug'}
                    {/capture}
                    {include file='./../atoms/icon/icon.tpl'
                    iconClassName="{$check_configuration.status.ssl}Class"
                    iconName=$check_configuration.status.ssl}
                    {include file='./../atoms/paragraph/paragraph.tpl'
                    paragraphText=$stateBlock_stateSsl}
                </div>
            </div>
        </div>

    {/capture}
    {include file='./../atoms/accordion/accordion.tpl'
    accordionIdentifier='state.accordion'
    accordionClassName='-stateBlock'
    accordionContent=$accordionContent}

{/capture}

{assign var='stateBlock_className' value='stateBlock'}

{include file='./../atoms/block/block.tpl'
    blockTitle=$stateBlock_title
    blockDescription=$stateBlock_description
    blockContent=$stateBlock_content
    blockData='blockState'
    blockDisabled=!$connected || !$payplug_switch.show.checked
    blockClassName=$stateBlock_className}