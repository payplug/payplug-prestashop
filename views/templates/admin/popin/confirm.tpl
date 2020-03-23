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
<p>{l s='Once the settings are saved, the Payplug module will be displayed:' mod='payplug'}</p>
<ul>
    <li data-e2e-type="sandbox" data-e2e-state="{if $sandbox}on{else}off{/if}">
        {l s='Mode:' mod='payplug'}
        <strong>{if $sandbox}{l s='TEST' mod='payplug'}{else}{l s='LIVE' mod='payplug'}{/if}</strong>
    </li>
    <li data-e2e-type="embedded" data-e2e-state="{if $embedded}on{else}off{/if}">
        {l s='Payment page:' mod='payplug'}
        <strong>{if $embedded}{l s='EMBEDDED' mod='payplug'}{else}{l s='REDIRECTED' mod='payplug'}{/if}</strong>
    </li>
    <li data-e2e-type="one_click" data-e2e-state="{if $one_click}on{else}off{/if}">
        {l s='One-click payments:' mod='payplug'}
        <strong>{if $one_click}{l s='ENABLED' mod='payplug'}{else}{l s='DISABLED' mod='payplug'}{/if}</strong>
    </li>
    <li data-e2e-type="oney" data-e2e-state="{if $oney}on{else}off{/if}">
        {l s='Payments 3x, 4x Oney:' mod='payplug'}
        <strong>{if $oney}{l s='ENABLED' mod='payplug'}{else}{l s='DISABLED' mod='payplug'}{/if}</strong>
    </li>
    <li data-e2e-type="installment" data-e2e-state="{if $installment}on{else}off{/if}">
        {l s='Installments :' mod='payplug'}
        <strong>{if $installment}{l s='ENABLED' mod='payplug'}{else}{l s='DISABLED' mod='payplug'}{/if}</strong>
    </li>
    <li data-e2e-type="deferred" data-e2e-state="{if $deferred}on{else}off{/if}">
        {l s='Deferred payments :' mod='payplug'}
        <strong>{if $deferred}{l s='ENABLED' mod='payplug'}{else}{l s='DISABLED' mod='payplug'}{/if}</strong>
    </li>
</ul>
<div class="payplugPopup_footer">
    <button type="button" class="payplugButton payplugButton-close">{l s='Cancel' mod='payplug'}</button>
    <button type="button" class="payplugButton payplugButton-green" name="confirm">{l s='SAVE SETTINGS' mod='payplug'}</button>
</div>
