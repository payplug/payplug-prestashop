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

{extends file='customer/page.tpl'}

{block name='page_title'}
    {l s='Saved Cards' mod='payplug'}
    {*{l s='Saved Cards' d='Modules.Payplug.Shop'}*}
{/block}

{block name='page_content'}
    <h6>{l s='Here are the cards you have saved.' mod='payplug'}</h6>

    {assign var='payplug_has_cards' value=(isset($payplug_cards) && !empty($payplug_cards))}
    {assign var='payplug_has_aliases' value=(isset($payplug_aliases) && !empty($payplug_aliases))}
    {if $payplug_has_cards || $payplug_has_aliases}
        {assign var='payplug_row' value=0}
        {assign var='payplug_total' value=0}
        {if $payplug_has_cards}{assign var='payplug_total' value=$payplug_total+count($payplug_cards)}{/if}
        {if $payplug_has_aliases}{assign var='payplug_total' value=$payplug_total+count($payplug_aliases)}{/if}
        <table class="table table-striped table-bordered table-labeled" data-e2e-card="list">
            <thead class="thead-default">
            <tr>
                <th class="first_item hidden-sm-down">{l s='Card' mod='payplug'}</th>
                <th class="item hidden-sm-down">{l s='Brand' mod='payplug'}</th>
                <th class="item">{l s='Card mask' mod='payplug'}</th>
                <th class="item">{l s='Expiry date' mod='payplug'}</th>
                <th class="item">{l s='Delete' mod='payplug'}</th>
            </tr>
            </thead>
            <tbody>
            {if $payplug_has_cards}
            {foreach from=$payplug_cards item=card}
                {assign var='payplug_row' value=$payplug_row+1}
                <tr class="{$module_name|escape:'htmlall':'UTF-8'}Card {if $payplug_row == 1}first_item{elseif $payplug_row == $payplug_total}last_item{else}item{/if} {if $payplug_row % 2 == 0}alternate_item{/if}" data-type="card" data-id_card="{$card.id_payplug_card|escape:'htmlall':'UTF-8'}" data-e2e-card="item">
                    <td class="id_payplug_card bold hidden-sm-down">{$payplug_row|escape:'htmlall':'UTF-8'}</td>
                    <td class="brand bold hidden-sm-down">{if $card.brand != 'none'}{$card.brand|escape:'htmlall':'UTF-8'}{else}{l s='card' mod='payplug'}{/if}</td>
                    <td class="last4 bold">**** **** **** {$card.last4|escape:'htmlall':'UTF-8'}</td>
                    <td class="expiry_date bold">{$card.expiry_date|escape:'htmlall':'UTF-8'}</td>
                    <td class="delete bold"><a class="{$module_name|escape:'htmlall':'UTF-8'}Card_delete" data-type="card" data-id_card="{$card.id_payplug_card|escape:'htmlall':'UTF-8'}" href="{$payplug_delete_card_url|escape:'htmlall':'UTF-8'}" title="{l s='Delete' mod='payplug'}" data-e2e-card="delete">{l s='Delete' mod='payplug'}</a></td>
                </tr>
            {/foreach}
            {/if}
            {if $payplug_has_aliases}
            {foreach from=$payplug_aliases item=alias}
                {assign var='payplug_row' value=$payplug_row+1}
                <tr class="{$module_name|escape:'htmlall':'UTF-8'}Card {if $payplug_row == 1}first_item{elseif $payplug_row == $payplug_total}last_item{else}item{/if} {if $payplug_row % 2 == 0}alternate_item{/if}" data-type="alias" data-id_card="{$alias.id_payplug_alias|escape:'htmlall':'UTF-8'}" data-e2e-card="item">
                    <td class="id_payplug_card bold hidden-sm-down">{$payplug_row|escape:'htmlall':'UTF-8'}</td>
                    <td class="brand bold hidden-sm-down">{if $alias.brand}{$alias.brand|escape:'htmlall':'UTF-8'}{else}{l s='card' mod='payplug'}{/if}</td>
                    <td class="last4 bold">
                        {if $alias.last4}**** **** **** {$alias.last4|escape:'htmlall':'UTF-8'}{else}{l s='card' mod='payplug'}{/if}
                        {if !$alias.usable}<br/><small>{l s='Not usable for payment at the moment' mod='payplug'}</small>{/if}
                    </td>
                    <td class="expiry_date bold">{if $alias.expiry_date}{$alias.expiry_date|escape:'htmlall':'UTF-8'}{/if}</td>
                    <td class="delete bold"><a class="{$module_name|escape:'htmlall':'UTF-8'}Card_delete" data-type="alias" data-id_card="{$alias.id_payplug_alias|escape:'htmlall':'UTF-8'}" href="{$payplug_delete_card_url|escape:'htmlall':'UTF-8'}" title="{l s='Delete' mod='payplug'}" data-e2e-card="delete">{l s='Delete' mod='payplug'}</a></td>
                </tr>
            {/foreach}
            {/if}
            </tbody>
        </table>
    {else}
        <p class="warning">{l s='You have no card registered yet.' mod='payplug'}</p>
    {/if}

{/block}
