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

{*Template for the Payment Method Block*}
{if isset($paymentMethods) && $paymentMethods}
    {capture assign="paymentMethod_title"}{l s='paymentMethods.block.title' mod='payplug'}{/capture}
    {capture assign="paymentMethod_description"}{l s='paymentMethods.block.description' mod='payplug'}{/capture}
    {capture assign="paymentMethod_content"}
        {foreach $paymentMethods as $paymentMethodName => $paymentMethod}
            {include file='./paymentMethod.tpl' paymentMethodName=$paymentMethodName paymentMethod=$paymentMethod}
        {/foreach}
    {/capture}

    {include file='./../../atoms/block/block.tpl'
        blockTitle=$paymentMethod_title
        blockDescription=$paymentMethod_description
        blockContent=$paymentMethod_content
        blockDisabled=!$connected || !$payplug_switch.show.checked
        blockData='paymentMethodsBlock'
        blockClassName='paymentMethodBlock'}
{/if}