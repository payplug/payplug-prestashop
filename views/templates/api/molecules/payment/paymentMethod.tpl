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

{if isset($payment_methods) && $payment_methods}
    {capture assign="paymentMethod_title"}{l s='paymentMethod.block.title' mod='payplug'}{/capture}
    {capture assign="paymentMethod_description"}{l s='paymentMethod.block.description' mod='payplug'}{/capture}
    {capture assign="paymentMethod_content"}
{*        {$sex = ($bloke) ? 'Male' : 'Female'}*}
{*        $payplug_switch.sandbox.checked ?$payment_method.sandboxDescription : $payment_method.description*}
{*        {var_dump($payplug_switch.sandbox.checked)}*}
        {$description = $payplug_switch.sandbox.checked ?$payment_method.sandboxDescription : $payment_method.description}
        {assign description ($payplug_switch.sandbox.checked ) ? $payment_method.sandboxDescription : $payment_method.description}
        {foreach $payment_methods as $payment_method_name => $payment_method}
            {include file='./paymentOption.tpl'
                paymentOptionIdentifier = $payment_method_name
                paymentOptionName = $payment_method.name
                paymentOptionImage_url = $payment_method.image_url
                paymentOptionDescription = $description
                paymentOptionLink = $payment_method.link
                paymentOptionChecked = $payment_method.checked
                paymentOptionInformations = $payment_method.informations}
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