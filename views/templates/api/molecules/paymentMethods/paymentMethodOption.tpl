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

<div class="_option">
    <div class="_content">
        {include file='./../../atoms/title/title.tpl'
            titleClassName='_title'
            titleText=$paymentMethodOption.title}
        <p class="_description">
            {$paymentMethodOption.description|escape:'htmlall':'UTF-8'}
            {if isset($paymentMethodOption.link) && $paymentMethodOption.link}
                {include file='./../../atoms/link/link.tpl'
                    linkText={l s='paymentMethodOption.link' mod='payplug'}
                    linkHref=$paymentMethodOption.link|escape:'htmlall':'UTF-8'
                    linkTarget='_blank'
                    linkData='data-link'}
            {/if}
        </p>
    </div>
    <div class="_action">
        {if 'switch' == $paymentMethodOption.action.type}
            {include file='./../../atoms/switch/switch.tpl'
                switchEnabledLabel=$paymentMethodOption.action.params.enabledLabel|escape:'htmlall':'UTF-8'
                switchDisabledLabel=$paymentMethodOption.action.params.disabledLabel|escape:'htmlall':'UTF-8'
                switchDataName=$paymentMethodOption.action.params.dataName|escape:'htmlall':'UTF-8'
                switchChecked=$paymentMethodOption.action.params.checked|escape:'htmlall':'UTF-8'
                switchClassName=$paymentMethodOption.action.params.className|escape:'htmlall':'UTF-8'
                switchName=$paymentMethodOption.action.params.name|escape:'htmlall':'UTF-8'}
        {elseif 'options' == $paymentMethodOption.action.type}
            {include file='./../../atoms/options/options.tpl'
                optionsItems=$paymentMethodOption.action.params.items
                optionsClassName=$paymentMethodOption.action.params.className|escape:'htmlall':'UTF-8'
                optionsSelected=$paymentMethodOption.action.params.selected|escape:'htmlall':'UTF-8'
                optionsName=$paymentMethodOption.action.params.name|escape:'htmlall':'UTF-8'}
        {/if}
    </div>
</div>