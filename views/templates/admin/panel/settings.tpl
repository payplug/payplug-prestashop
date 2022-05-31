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
{if $standard_isActivated || $installment_isActivated || $bancontact || $installment_isActivated || $deferred_isActivated}
    <div class="panel {$module_name|escape:'htmlall':'UTF-8'}Settings">
        <div class="panel-heading">{l s='SETTINGS' mod='payplug'}</div>

        {if $connected && !$verified}
            <div class="panel-row">
                <p class="{$module_name|escape:'htmlall':'UTF-8'}Alert -warning">
                    <span>
                        {l s='You are able to perform only TEST transactions.' mod='payplug'} {l s='Please activate your account to perform LIVE transactions.' mod='payplug'}
                        <a href="{$faq_links.activation|escape:'htmlall':'UTF-8'}" target="_blank">{l s='More information' mod='payplug'}</a>
                    </span>
                </p>
            </div>
        {/if}

        <div class="{$module_name|escape:'htmlall':'UTF-8'}Settings_advanced">
            {if $installment_isActivated}
                {include file='./settings/installment.tpl'}
            {/if}
            {if $deferred_isActivated && (($standard_isActivated || $installment_isActivated))}
                {include file='./settings/deferred.tpl'}
            {/if}
        </div>
    </div>
{/if}
