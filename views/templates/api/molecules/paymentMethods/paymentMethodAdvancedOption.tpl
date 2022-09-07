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

<div class="_advancedOption -{$advancedOptionName|escape:'htmlall':'UTF-8'}">
    <div class="_header">
        {include file='./../../atoms/title/title.tpl'
            titleClassName='_title'
            titleText=$paymentMethodAdvancedOption.title}

        {if isset($paymentMethodAdvancedOption.premium) && $paymentMethodAdvancedOption.premium}
            {assign var="paymentMethodAdvancedOptionClassName" value='-premium'}
        {else}
            {assign var="paymentMethodAdvancedOptionClassName" value=''}
        {/if}

        {include file='./../../atoms/switch/switch.tpl'
            switchEnabledLabel='On'
            switchDisabledLabel='Off'
            switchClassName=$paymentMethodAdvancedOptionClassName
            switchDataName=$paymentMethodAdvancedOptionName|escape:'htmlall':'UTF-8'|cat:'Switch'
            switchChecked=$paymentMethodAdvancedOption.checked|escape:'htmlall':'UTF-8'
            switchName=$paymentMethodAdvancedOption.name|escape:'htmlall':'UTF-8'}
    </div>
    <div class="_content">
        {assign var="templateName" value='./'|cat:$paymentMethodName|cat:'/'|cat:$paymentMethodAdvancedOptionName|cat:'.tpl'}
        {include file=$templateName}
    </div>
</div>