{*
* 2016 PayPlug
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PayPlug SAS
*  @copyright 2016 PayPlug SAS
*  @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}

{capture name=path}<a href="{$link->getPageLink('my-account.php', true)|escape:'htmlall':'UTF-8'}">{l s='My account' mod='payplug'}</a><span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{l s='Saved cards' mod='payplug'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}
{include file="$tpl_dir./errors.tpl"}

<h1>{l s='Saved cards' mod='payplug'}</h1>
<p>{l s='Here are the cards you have saved.' mod='payplug'}.</p>
<p class="message success">{l s='Card sucessfuly deleted.' mod='payplug'}</p>

<div class="block-center" id="block-card">
    {if isset($payplug_cards) AND !empty($payplug_cards) AND sizeof($payplug_cards)}
        <table id="card-list" class="std">
            <thead>
            <tr>
                <th class="first_item">{l s='Card' mod='payplug'}</th>
                <th class="item">{l s='Brand' mod='payplug'}</th>
                <th class="item">{l s='Card mask' mod='payplug'}</th>
                <th class="item">{l s='Expiry date' mod='payplug'}</th>
                <th class="item">{l s='Delete' mod='payplug'}</th>
            </tr>
            </thead>
            <tbody>
            {foreach from=$payplug_cards item=card name=ppcards}
                <tr id="id_payplug_card_{$card.id_payplug_card|escape:'htmlall':'UTF-8'}" class="{if $smarty.foreach.ppcards.first}first_item{elseif $smarty.foreach.ppcards.last}last_item{else}item{/if} {if $smarty.foreach.ppcards.index % 2}alternate_item{/if}">
                    <td class="id_payplug_card bold">{$smarty.foreach.ppcards.index|escape:'htmlall':'UTF-8' +1}</td>
                    <td class="brand bold">{if $card.brand != 'none'}{$card.brand|escape:'htmlall':'UTF-8'}{else}{l s='card' mod='payplug'}{/if}</td>
                    <td class="last4 bold">**** **** **** {$card.last4|escape:'htmlall':'UTF-8'}</td>
                    <td class="expiry_date bold">{$card.expiry_date|escape:'htmlall':'UTF-8'}</td>
                    <td class="delete bold"><a class="ppdeletecard" href="{$payplug_delete_card_url|escape:'htmlall':'UTF-8'}" title="{l s='Delete' mod='payplug'}">{l s='Delete' mod='payplug'}</a></td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    {else}
        <p class="warning">{l s='You have no card registered yet.' mod='payplug'}</p>
    {/if}
</div>

<ul class="footer_links">
    <li><a href="{$link->getPageLink('my-account.php', true)|escape:'htmlall':'UTF-8'}"><img src="{$img_dir|escape:'htmlall':'UTF-8'}icon/my-account.gif" alt="" class="icon" /></a><a href="{$link->getPageLink('my-account.php', true)|escape:'htmlall':'UTF-8'}">{l s='Back to Your Account' mod='payplug'}</a></li>
    <li><a href="{$base_dir|escape:'htmlall':'UTF-8'}"><img src="{$img_dir|escape:'htmlall':'UTF-8'}icon/home.gif" alt="" class="icon" /></a><a href="{$base_dir|escape:'htmlall':'UTF-8'}">{l s='Home' mod='payplug'}</a></li>
</ul>
