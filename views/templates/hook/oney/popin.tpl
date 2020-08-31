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
<span class="oneyPopin{if isset($payplug_oney_error) && $payplug_oney_error} oneyPopin-error{/if}">
    {if isset($payplug_oney_error) && $payplug_oney_error}
        <p class="oneyPopin_error">{$payplug_oney_error|escape:'htmlall':'UTF-8'}</p>
    {elseif isset($oney_payment_options) && $oney_payment_options}
        <button class="oneyPopin_close">{l s='Close' mod='payplug'}</button>
        <span class="oneyPopin_title">
            {l s='Pay' mod='payplug'}<strong>{l s='by card' mod='payplug'}</strong>
        </span>

        <ul class="oneyPopin_navigation">
            {foreach $oney_payment_options as $oney_payment_method => $oney_payment_option}
                <li{if $oney_payment_method == 'x3_with_fees'} class="selected"{/if}><button type="button" data-type="{$oney_payment_option.split|escape:'htmlall':'UTF-8'}x">{$oney_payment_option.title|escape:'htmlall':'UTF-8'}</button></li>
            {/foreach}
        </ul>

        {foreach $oney_payment_options as $oney_payment_method => $oney_payment_option}
            <span class="oneyPopin_option{if $oney_payment_method == 'x3_with_fees'} oneyPopin_option-show{/if}" data-type="{$oney_payment_option.split|escape:'htmlall':'UTF-8'}x">
			    {include file="./payment_detail.tpl" oney_payment_option=$oney_payment_option}
            </span>
        {/foreach}

        {*  Phrase 'Accept CGV' *}
        {if $tos_active && $tos_url}
                <span class="oneyPopin_legal">
                    {l s='By placing an order, you accept our ' mod='payplug'}
                    <a href="{$tos_url|escape:'htmlall':'UTF-8'}" target="_blank">
                        <u>{l s='TOS' mod='payplug'}</u>.
                    </a>
                </span>
        {/if}

        <span class="oneyPopin_legal">{$legal_notice|escape:'htmlall':'UTF-8'}</span>
    {else}
        <p class="oneyPopin_error">{l s='Oney is momentarily unavailable.' mod='payplug'}</p>
    {/if}
</span>
