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

<div class="paymentMethodOption">
    <div class="_text">
        {include file='./../../atoms/title/title.tpl'
        titleClassName='_title'
        titleText=$informationTitle}
        <p>
            {$informationDescription|escape:'htmlall':'UTF-8'}
            {if isset($informationLink) && $informationLink}
                {capture assign="informationLinkText"}{l s='PaymentInformation.link.text' mod='payplug'}{/capture}
                {include
                file='./../../atoms/link/link.tpl'
                linkText=$informationLinkText|escape:'htmlall':'UTF-8'
                linkHref=$informationLink|escape:'htmlall':'UTF-8'
                linkTarget='_blank'
                linkData='data-link'
                }
            {/if}
        </p>
    </div>
    <div class="_action">
        {$informationAction}
    </div>
</div>
