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
<ul class="pp_payment_details">
    {if isset($payment.id)}
        <li>
            <span class="pp_col1">{l s='Transaction ID' mod='payplug'} :</span>
            <span class="pp_col2">{$payment.id|escape:'htmlall':'UTF-8'}</span>
        </li>
    {/if}
    {if isset($payment.type)}
        <li>
            <span class="pp_col1">{l s='Type' mod='payplug'} :</span>
            <span class="pp_col2">{$payment.type|escape:'htmlall':'UTF-8'}</span>
        </li>
    {/if}
    {if isset($payment.status)}
        <li data-e2e-state_code="{$payment.status_code}">
            <span class="pp_col1">{l s='Status' mod='payplug'} :</span>
            <span class="pp_col2">
                <span class="pp_payment_status{if isset($payment.status_class)} {$payment.status_class|escape:'htmlall':'UTF-8'}{/if}">{$payment.status|escape:'htmlall':'UTF-8'}</span>
                <span>{if isset($payment.status_message)}{$payment.status_message|escape:'htmlall':'UTF-8'}{/if}</span>
                <span>{if isset($payment.error)} {$payment.error|escape:'htmlall':'UTF-8'}{/if}</span>
            </span>
        </li>
    {/if}
    {if isset($payment.amount)}
        <li>
            <span class="pp_col1">{l s='Amount' mod='payplug'} :</span>
            <span class="pp_col2">{displayPrice price=$payment.amount}</span>
        </li>
    {/if}
    {if isset($payment.authorization) && $payment.can_be_captured === true}
        {if isset($payment.date)}
            <li>
                <span class="pp_col1">{l s='Authorized on' mod='payplug'} :</span>
                <span class="pp_col2">{$payment.date|escape:'htmlall':'UTF-8'}</span>
            </li>
        {/if}
    {else}
        {if isset($payment.date)}
            <li>
                <span class="pp_col1">{l s='Paid at' mod='payplug'} :</span>
                <span class="pp_col2">{$payment.date|escape:'htmlall':'UTF-8'}</span>
            </li>
        {/if}
    {/if}
    {if isset($payment.card_brand)}
        <li>
            <span class="pp_col1">{l s='Credit card' mod='payplug'} :</span>
            <span class="pp_col2">{$payment.card_brand|escape:'htmlall':'UTF-8'}</span>
        </li>
    {/if}
    {if isset($payment.card_mask)}
        <li>
            <span class="pp_col1">{l s='Card mask' mod='payplug'} :</span>
            <span class="pp_col2">{$payment.card_mask|escape:'htmlall':'UTF-8'}</span>
        </li>
    {/if}
    {if isset($payment.tds)}
        <li>
            <span class="pp_col1">{l s='3-D Secure' mod='payplug'} :</span>
            <span class="pp_col2">{$payment.tds|escape:'htmlall':'UTF-8'}</span>
        </li>
    {/if}
    {if isset($payment.card_date)}
        <li>
            <span class="pp_col1">{l s='Expiry Date' mod='payplug'} :</span>
            <span class="pp_col2">{$payment.card_date|escape:'htmlall':'UTF-8'}</span>
        </li>
    {/if}
    {if isset($payment.mode)}
        <li>
            <span class="pp_col1">{l s='Mode' mod='payplug'} :</span>
            <span class="pp_col2">{$payment.mode|escape:'htmlall':'UTF-8'}</span>
        </li>
    {/if}
</ul>
{if isset($payment.can_be_captured) && $payment.can_be_captured === true}
    {include file='./capture.tpl' payment=$payment}
{/if}
