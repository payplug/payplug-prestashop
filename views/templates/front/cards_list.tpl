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

{extends file='customer/page.tpl'}

{block name='page_title'}
    {l s='Saved Cards' mod='payplug'}
    {*{l s='Saved Cards' d='Modules.Payplug.Shop'}*}
{/block}

{block name='page_content'}
    <h6>{l s='Here are the cards you have saved.' mod='payplug'}</h6>
    {*<h6>{l s='Here are the cards you have saved.' d='Modules.Payplug.Shop'}</h6>*}
    <div class="col-xs-12 message alert alert-success">
        <ul>
            <li>{l s='Card sucessfuly deleted.' mod='payplug'}</li>
            {*<li>{l s='Card sucessfuly deleted.' d='Modules.Payplug.Shop'}</li>*}
        </ul>
    </div>

    {if isset($payplug_cards) AND !empty($payplug_cards) AND sizeof($payplug_cards)}
        <table class="table table-striped table-bordered table-labeled hidden-sm-down" id="card-list">
            <thead class="thead-default">
            <tr>
                <th class="first_item">{l s='Card' mod='payplug'}</th>
                {*<th class="first_item">{l s='Card' d='Modules.Payplug.Shop'}</th>*}
                <th class="item">{l s='Brand' mod='payplug'}</th>
                {*<th class="item">{l s='Brand' d='Modules.Payplug.Shop'}</th>*}
                <th class="item">{l s='Card mask' mod='payplug'}</th>
                {*<th class="item">{l s='Card mask' d='Modules.Payplug.Shop'}</th>*}
                <th class="item">{l s='Expiry date' mod='payplug'}</th>
                {*<th class="item">{l s='Expiry date' d='Modules.Payplug.Shop'}</th>*}
                <th class="item">{l s='Delete' mod='payplug'}</th>
                {*<th class="item">{l s='Delete' d='Modules.Payplug.Shop'}</th>*}
            </tr>
            </thead>
            <tbody>
            {foreach from=$payplug_cards item=card name=ppcards}
                <tr id="id_payplug_card_{$card.id_payplug_card|escape:'htmlall':'UTF-8'}" class="{if $smarty.foreach.ppcards.first}first_item{elseif $smarty.foreach.ppcards.last}last_item{else}item{/if} {if $smarty.foreach.ppcards.index % 2}alternate_item{/if}">
                    <td class="id_payplug_card bold">{$smarty.foreach.ppcards.index +1|escape:'htmlall':'UTF-8'}</td>
                    <td class="brand bold">{if $card.brand != 'none'}{$card.brand|escape:'htmlall':'UTF-8'}{else}{l s='card' mod='payplug'}{/if}</td>
                    {*<td class="brand bold">{if $card.brand != 'none'}{$card.brand|escape:'htmlall':'UTF-8'}{else}{l s='card' d='Modules.Payplug.Shop'}{/if}</td>*}
                    <td class="last4 bold">**** **** **** {$card.last4|escape:'htmlall':'UTF-8'}</td>
                    <td class="expiry_date bold">{$card.expiry_date|escape:'htmlall':'UTF-8'}</td>
                    <td class="delete bold"><a class="ppdeletecard" data-id_card="{$card.id_payplug_card|escape:'htmlall':'UTF-8'}" href="{$payplug_delete_card_url|escape:'htmlall':'UTF-8'}" title="{l s='Delete' mod='payplug'}">{l s='Delete' mod='payplug'}</a></td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    {else}
        <p class="warning">{l s='You have no card registered yet.' mod='payplug'}</p>
        {*<p class="warning">{l s='You have no card registered yet.' d='Modules.Payplug.Shop'}</p>*}
    {/if}

{/block}
