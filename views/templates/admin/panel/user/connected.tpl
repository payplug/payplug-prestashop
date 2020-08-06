{*
* 2020 PayPlug
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
*  @copyright 2020 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
<div class="payplugPanel">
    <div class="payplugPanel_label">{l s='Account connected' mod='payplug'}</div>
    <div class="payplugPanel_content">
        <p class="payplugLogin_email">{$PAYPLUG_EMAIL|escape:'htmlall':'UTF-8'}</p>
    </div>
</div>
<div class="payplugPanel">
    <div class="payplugPanel_content">
        <a class="payplugLink" target="_blank" href="{$site_url|escape:'htmlall':'UTF-8'}/portal">{l s='Payplug Portal' mod='payplug'}</a>
        <span class="payplugPide">|</span>
        <button type="button" class="payplugLink payplugLogin_logout">{l s='Disconnect' mod='payplug'}</button>
    </div>
</div>
