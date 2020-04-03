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
<div class="panel payplugSettings">
    <div class="panel-heading">{l s='SETTINGS' mod='payplug'}</div>

    {if $connected && !$verified}
        <div class="panel-row">
            <p class="payplugAlert payplugAlert-warning">
                <span>
                    {l s='You are able to perform only TEST transactions.' mod='payplug'} {l s='Please activate your account to perform LIVE transactions.' mod='payplug'}
                    <a href="{$faq_links.activation|escape:'htmlall':'UTF-8'}" target="_blank">{l s='More information' mod='payplug'}</a>
                </span>
            </p>
        </div>
    {/if}

    {include file='./settings/sandbox.tpl'}
    {include file='./settings/embedded.tpl'}

    <div class="payplugSettings_separator">
        <p><strong>{l s='Advanced settings' mod='payplug'}</strong></p>
    </div>

    <div class="payplugSettings_advanced">
        {include file='./settings/one_click.tpl'}
        {include file='./settings/oney.tpl'}
        {include file='./settings/installment.tpl'}
        {include file='./settings/deferred.tpl'}
    </div>

    <div class="panel-footer">
        <button type="submit" name="submitSettings" class="payplugButton payplugButton-green{if !$connected} payplugButton-disabled{/if}">{l s='Update settings' mod='payplug'}</button>
    </div>
</div>
