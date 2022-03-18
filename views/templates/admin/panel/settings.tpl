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
<div class="panel {$module_name}Settings">
    <div class="panel-heading">{l s='SETTINGS' mod={$module_name}}</div>

    {if $connected && !$verified}
        <div class="panel-row">
            <p class="{$module_name}Alert -warning">
                <span>
                    {l s='You are able to perform only TEST transactions.' mod={$module_name}} {l s='Please activate your account to perform LIVE transactions.' mod={$module_name}}
                    <a href="{$faq_links.activation|escape:'htmlall':'UTF-8'}" target="_blank">{l s='More information' mod={$module_name}}</a>
                </span>
            </p>
        </div>
    {/if}

    {if $display_mode_isActivated && ($standard_isActivated || $installment_isActivated)}
        {include file='./settings/embedded.tpl'}
        <div class="{$module_name}Settings_separator">
            <p><strong>{l s='Advanced settings' mod={$module_name}}</strong></p>
        </div>
    {/if}


    <div class="{$module_name}Settings_advanced">
        {if $standard_isActivated}
            {include file='./settings/standard.tpl'}
        {/if}
        {include file='./settings/oney.tpl'}

        {if $bancontact}
            {include file='./settings/bancontact.tpl'}
        {/if}
        {if $installment_isActivated}
            {include file='./settings/installment.tpl'}
        {/if}
        {if $deferred_isActivated && (($standard_isActivated || $installment_isActivated))}
            {include file='./settings/deferred.tpl'}
        {/if}
    </div>
</div>
