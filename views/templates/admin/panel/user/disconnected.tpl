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
    <div class="payplugPanel_label">{l s='Email' mod='payplug'}</div>
    <div class="payplugPanel_content">
        <input type="text" name="PAYPLUG_EMAIL" placeholder="{l s='E-mail address' mod='payplug'}" value="{if isset($PAYPLUG_EMAIL)}{$PAYPLUG_EMAIL|escape:'htmlall':'UTF-8'}{/if}"/>
        <span class="input-error">
            <span class="error-email-input">{$p_error|escape:'htmlall':'UTF-8'}</span>
            <span id="error-email-regexp" class="hide">{l s='E-mail address is not valid.' mod='payplug'}</span>
        </span>
    </div>
</div>
<div class="payplugPanel">
    <div class="payplugPanel_label">{l s='Password' mod='payplug'}</div>
    <div class="payplugPanel_content">
        <input type="password" name="PAYPLUG_PASSWORD" placeholder="{l s='Password' mod='payplug'}" value=""/>
        <span class="input-error">
            <span class="error-password-input">{$p_error|escape:'htmlall':'UTF-8'}</span>
            <span id="error-password-regexp" class="hide">{l s='Password must be a least 8 caracters long.' mod='payplug'}</span>
        </span>
    </div>
</div>
<div class="payplugPanel">
    <div class="payplugPanel_content">
        <a class="payplugLink" href="{$site_url|escape:'htmlall':'UTF-8'}/portal/forgot_password" target="_blank">{l s='Forgot your password?' mod='payplug'}</a>
    </div>
</div>
<div class="payplugPanel">
    <div class="payplugPanel_content">
        <button type="button" class="payplugButton payplugButton-green payplugLogin_login">{l s='Connect account' mod='payplug'}</button>
    </div>
</div>
<div class="payplugPanel">
    <div class="payplugPanel_content">
        <p>
            {l s='Don\'t have an account?' mod='payplug'}<br>
            <a class="payplugLink" href="{$site_url|escape:'htmlall':'UTF-8'}/portal/signup?origin=PrestashopV2Config" target="_blank">{l s='Sign up' mod='payplug'}</a>
        </p>
    </div>
</div>

