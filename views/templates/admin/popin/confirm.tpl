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
<p>{l s='admin.popin.confirm.top' mod='payplug'}</p>
<ul>
    <li data-e2e-type="sandbox" data-e2e-state="{if $sandbox}on{else}off{/if}">
        {l s='admin.popin.confirm.mode' mod='payplug'}
        <strong>{if $sandbox}{l s='admin.popin.confirm.test' mod='payplug'}{else}{l s='admin.popin.confirm.live' mod='payplug'}{/if}</strong>
    </li>
    {if $display_mode_feature && ($installment_feature || $standard_feature)}
        <li data-e2e-type="embedded" data-e2e-state="{if $embedded}on{else}off{/if}">
            {l s='admin.popin.confirm.paymentpage' mod='payplug'}
            <strong>{if $embedded == 'integrated'}{l s='admin.popin.confirm.embedded' mod='payplug'}{elseif $embedded == 'popup'}{l s='admin.popin.confirm.popup' mod='payplug'}{elseif $embedded == 'redirected'}{l s='admin.popin.confirm.redirected' mod='payplug'}{/if}</strong>
        </li>
    {/if}
    {if $standard_feature}
        <li data-e2e-type="standard" data-e2e-state="{if $standard}on{else}off{/if}">
            {l s='admin.popin.confirm.standard' mod='payplug'}
            <strong>{if $standard}{l s='admin.popin.confirm.enabled' mod='payplug'}{else}{l s='admin.popin.confirm.disabled' mod='payplug'}{/if}</strong>
        </li>
        <li data-e2e-type="one_click" data-e2e-state="{if $one_click}on{else}off{/if}">
            {l s='admin.popin.confirm.one_click' mod='payplug'}
            <strong>{if $one_click}{l s='admin.popin.confirm.enabled' mod='payplug'}{else}{l s='admin.popin.confirm.disabled' mod='payplug'}{/if}</strong>
        </li>
    {/if}
    <li data-e2e-type="oney" data-e2e-state="{if $oney}on{else}off{/if}">
        {l s='admin.popin.confirm.oney' mod='payplug'}
        <strong>{if $oney}{l s='admin.popin.confirm.enabled' mod='payplug'}{else}{l s='admin.popin.confirm.disabled' mod='payplug'}{/if}</strong>
    </li>
    {if $bancontact_feature && !$sandbox}
        <li data-e2e-type="bancontact" data-e2e-state="{if $bancontact}on{else}off{/if}">
            {l s='admin.popin.confirm.bancontact' mod='payplug'}
            <strong>{if $bancontact}{l s='admin.popin.confirm.enabled' mod='payplug'}{else}{l s='admin.popin.confirm.disabled' mod='payplug'}{/if}</strong>
        </li>
    {/if}
    {if $installment_feature}
        <li data-e2e-type="installment" data-e2e-state="{if $installment}on{else}off{/if}">
            {l s='admin.popin.confirm.installment' mod='payplug'}
            <strong>{if $installment}{l s='admin.popin.confirm.enabled' mod='payplug'}{else}{l s='admin.popin.confirm.disabled' mod='payplug'}{/if}</strong>
        </li>
    {/if}
    {if $deferred_feature}
        <li data-e2e-type="deferred" data-e2e-state="{if $deferred}on{else}off{/if}">
            {l s='admin.popin.confirm.deferred' mod='payplug'}
            <strong>{if $deferred}{l s='admin.popin.confirm.enabled' mod='payplug'}{else}{l s='admin.popin.confirm.disabled' mod='payplug'}{/if}</strong>
        </li>
    {/if}
</ul>

{if isset($has_payment) && !$has_payment}
    <p class="{$module_name|escape:'htmlall':'UTF-8'}Popup_error -confirm" data-e2e-type="confirm-error">{l s='admin.popin.confirm.error' mod='payplug'}</p>
{/if}

<div class="{$module_name|escape:'htmlall':'UTF-8'}Popup_footer">
    <button type="button" class="{$module_name|escape:'htmlall':'UTF-8'}Button -close">{l s='admin.popin.confirm.cancel' mod='payplug'}</button>
    <button type="button" class="{$module_name|escape:'htmlall':'UTF-8'}Button -green" name="confirmConfiguration">{l s='admin.popin.confirm.save' mod='payplug'}</button>
</div>
