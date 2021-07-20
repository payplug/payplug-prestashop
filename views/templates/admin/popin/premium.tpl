{*
* 2021 PayPlug
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
*  @copyright 2021 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
{$payplug_contact_url = 'https://portal-qa.payplug.com/#/configuration/oney'}
 <p class="payplugPopup_text">
     {assign "link_to_payplug_contact_url" "<a href='{$payplug_contact_url|escape:'htmlall':'UTF-8'}'>"}
     {l s='admin.popin.premium.redirectOneyActivation' tags=['<br>',$link_to_payplug_contact_url] mod='payplug'}
 </p>
<div class="payplugPopup_footer -center">
    <button type="button" class="payplugButton -green -close">{l s='Ok' mod='payplug'}</button>
</div>
