{*
* 2023 Payplug
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
*  @author Payplug SAS
*  @copyright 2023 Payplug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of Payplug SAS
*}
<p class="{$module_name|escape:'htmlall':'UTF-8'}Popup_text">
    {assign "premiumLink" "<a href='{$premiumContent.link|escape:'htmlall':'UTF-8'}' target='_blank'>"}
    {l s='admin.popin.premium.featureUnavailable' mod='payplug'}<br>
    {if 'oneyPremium' == $premiumContent.use}
        {l s='admin.popin.premium.activateFeatureOney' tags=[$premiumLink] mod='payplug'}
    {elseif 'bancontactPremium' == $premiumContent.use}
        {l s='admin.popin.premium.activateFeatureBancontact' tags=[$premiumLink] mod='payplug'}
    {else}
        {l s='admin.popin.premium.activateFeature' tags=[$premiumLink] mod='payplug'}
    {/if}
</p>
<div class="{$module_name|escape:'htmlall':'UTF-8'}Popup_footer -center">
    <button type="button" class="{$module_name|escape:'htmlall':'UTF-8'}Button -green -close">{l s='admin.popin.premium.ok' mod='payplug'}</button>
</div>
