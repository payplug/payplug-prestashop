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

{capture assign="modalContent"}
    {capture assign='premiumLink'}
        {include file='./../../atoms/link/link.tpl'
        linkText=''
        linkHref=$premiumContent.link
        linkData='premiumLink'
        linkNoTag=true}
    {/capture}

    {capture assign='featureUnavailable'}
        {l s='admin.popin.premium.featureUnavailable' mod='payplug'}
    {/capture}
    {if 'oneyPremium' == $premiumContent.type}
        {capture assign='premiumText'}
            {l s='admin.popin.premium.activateFeatureOney' tags=[$premiumLink] mod='payplug'}
        {/capture}
    {elseif 'bancontactPremium' == $premiumContent.type}
        {capture assign='premiumText'}
            {l s='admin.popin.premium.activateFeatureBancontact' tags=[$premiumLink] mod='payplug'}
        {/capture}
    {elseif 'applepayPremium' == $premiumContent.type}
        {capture assign='premiumText'}
            {l s='admin.popin.premium.activateFeatureApplePay' tags=[$premiumLink] mod='payplug'}
        {/capture}
    {elseif 'amexPremium' == $premiumContent.type}
        {capture assign='premiumText'}
            {l s='admin.popin.premium.activateFeatureAmex' tags=[$premiumLink] mod='payplug'}
        {/capture}
    {else}
        {capture assign='premiumText'}
            {l s='admin.popin.premium.activateFeature' tags=[$premiumLink] mod='payplug'}
        {/capture}
    {/if}
    {capture assign="popinFeatureActivationText"}{$featureUnavailable}{$premiumText}{/capture}
    {include file='./../../atoms/paragraph/paragraph.tpl'
    paragraphText=$popinFeatureActivationText}

    {capture assign="popinConfirmPremium"}{l s='admin.popin.premium.ok' mod='payplug'}{/capture}
    {include file='./../../atoms/button/button.tpl'
    buttonData='submit'
    buttonName='closePopin'
    buttonText=$popinConfirmPremium}
{/capture}

{include file='./../../atoms/modal/modal.tpl'
modalClassName='modalPremium'
modalContent=$modalContent
modalTitle=$premiumContent.title
modalData='popinPremiumPermission'}
