{*
* 2019 PayPlug
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
*  @copyright 2019 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
<div class="oneyCta_wrapper">
    <div class="oneyCta{if isset($env) && $env} oneyCta-{$env}{/if}">
        <button type="button" class="oneyCta_button{if isset($payplug_oney_error) && $payplug_oney_error} oneyCta_button-disabled{/if}">
            <span>{l s='Or pay in' mod='payplug'}</span>
            <span class="oneyCta_logo oneyLogo oneyLogo-x3x4"></span>
            <span class="oneyCta_tooltip oneyLogo oneyLogo-tooltip"></span>
        </button>
        {if isset($popin) && $popin}
            {include file="./popin.tpl"}
        {/if}
    </div>
</div>
