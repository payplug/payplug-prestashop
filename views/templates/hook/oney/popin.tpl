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
<span class="{$module_name}OneyPopin{if isset($payplug_oney_error) && $payplug_oney_error} -error{/if}{if isset($use_fees) && !$use_fees} -withoutFees{/if} {if isset($iso_code) && $iso_code == 'IT' } -isItalian{/if}">
    {if isset($payplug_oney_error) && $payplug_oney_error}
        <p class="oneyPopin_error">{$payplug_oney_error|escape:'htmlall':'UTF-8'}</p>
    {elseif isset($oney_payment_options) && $oney_payment_options}
        <button class="oneyPopin_close">{l s='Close' mod={$module_name}}</button>
        <span class="oneyPopin_title">
            {l s='hook.oney.popin.pay' mod={$module_name}}
            {if isset($use_fees) && !$use_fees && isset($iso_code) && $iso_code != 'IT'}
                <u>{l s='hook.oney.popin.withoutFees' mod={$module_name}}</u>
            {/if}
            <strong>{l s='hook.oney.popin.card' mod={$module_name}}</strong>
        </span>

        <ul class="oneyPopin_navigation">
            {foreach $oney_payment_options as $oney_payment_method => $oney_payment_option}
                <li{if $oney_payment_method == 'x3_with_fees'} class="selected"{/if}>
                    <button type="button"
                            data-type="{$oney_payment_option.split|escape:'htmlall':'UTF-8'}x"
                            data-e2e-oney-option="{$oney_payment_method|escape:'htmlall':'UTF-8'}">
                        {$oney_payment_option.title|escape:'htmlall':'UTF-8'}
                    </button>
                </li>
            {/foreach}
        </ul>

        {foreach $oney_payment_options as $oney_payment_method => $oney_payment_option}
            <span class="oneyPopin_option{if $oney_payment_method == 'x3_with_fees'} -show{/if}"
                  data-type="{$oney_payment_option.split|escape:'htmlall':'UTF-8'}x">
			    {include file="./payment/detail.tpl" oney_payment_option=$oney_payment_option}
            </span>
        {/foreach}

        {assign "linkToOney" "<a href='{$oneyUrl|escape:'htmlall':'UTF-8'}' target='_blank'>"}
        <span class="oneyPopin_legal">
            {if $oneyWithFees}
                {l s='hook.oney.popin.legalNoticeWithFees' tags=[$linkToOney] sprintf=[$oneyMinAmounts, $oneyMaxAmounts] mod={$module_name}}
            {else}
                {l s='hook.oney.popin.legalNoticeWithoutFees' tags=[$linkToOney] sprintf=[$oneyMinAmounts, $oneyMaxAmounts] mod={$module_name}}
            {/if}
            {if isset($learnMoreLink) && $learnMoreLink}
                <a class="oneyPopin_external" href="https://www.payplug.com/hubfs/ONEY/payplug-italy{if isset($use_fees) && !$use_fees}-no-fees{/if}.pdf"  target="_blank">{l s='hook.oney.popin.learnMore' mod={$module_name}}</a>
            {/if}
        </span>
    {else}
        <p class="oneyPopin_error">{l s='hook.oney.popin.oneyUnavailable' mod={$module_name}}</p>
    {/if}
</span>
