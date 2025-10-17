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
<div class="{$module_name|escape:'htmlall':'UTF-8'}OneyCta_wrapper">
    <div class="{$module_name|escape:'htmlall':'UTF-8'}OneyCta{if isset($env) && $env} -{$env|escape:'htmlall':'UTF-8'}{/if}">
        <button type="button" class="{$module_name|escape:'htmlall':'UTF-8'}OneyCta_button
            {if !isset($payplug_is_oney_elligible)
                || (isset($payplug_is_oney_elligible) && $payplug_is_oney_elligible neq 1)} -disabled{/if}"
                data-e2e-oney="cta">
            <span>{l s='hook.oney.cta.cta' mod='payplug'}</span>
            <span class="{$module_name|escape:'htmlall':'UTF-8'}OneyCta_logo {$module_name|escape:'htmlall':'UTF-8'}OneyLogo -x3x4{if isset($use_fees) && !$use_fees} -withoutFees{/if} {if isset($iso_code) && $iso_code == 'IT' } -isItalian{/if}"></span>
            <span class="{$module_name|escape:'htmlall':'UTF-8'}OneyCta_tooltip {$module_name|escape:'htmlall':'UTF-8'}OneyLogo -tooltip"></span>
        </button>
        {if isset($popin) && $popin}
            {include file="./popin.tpl"}
        {/if}
    </div>
</div>
