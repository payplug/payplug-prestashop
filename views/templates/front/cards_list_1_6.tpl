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
{if $version == 1.4}
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
{elseif $version == 1.5}
    {capture name=path}<a href="{$link->getPageLink('my-account', true)|escape:'html'}" title="{l s='Manage my account' mod='payplug'}" rel="nofollow">{l s='My account' mod='payplug'}</a><span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{l s='Saved cards' mod='payplug'}{/capture}

    {include file="$tpl_dir./breadcrumb.tpl"}

    <h2>{l s='Saved cards' mod='payplug'}</h2>
    <p class="message success">{l s='Card sucessfuly deleted.' mod='payplug'}</p>
    {if isset($payplug_cards) AND !empty($payplug_cards) AND sizeof($payplug_cards)}
        <div class="block-center" id="block-history">
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
        </div>
    {else}
        <p class="warning">{l s='You have no card registered yet.' mod='payplug'}</p>
    {/if}

    <ul class="footer_links">
        <li>
            <a href="{$link->getPageLink('my-account', true)|escape:'htmlall':'UTF-8'}" title="{l s='Back to Your Account' mod='payplug'}" rel="nofollow"><img src="{$img_dir|escape:'htmlall':'UTF-8'}icon/my-account.gif" alt="" class="icon" /></a>
            <a href="{$link->getPageLink('my-account', true)|escape:'htmlall':'UTF-8'}" title="{l s='Back to Your Account' mod='payplug'}" rel="nofollow">{l s='Back to Your Account' mod='payplug'}</a>
        </li>
        <li class="f_right">
            <a href="{$base_dir|escape:'htmlall':'UTF-8'}" title="{l s='Home' mod='payplug'}"><img src="{$img_dir|escape:'htmlall':'UTF-8'}icon/home.gif" alt="" class="icon" /></a>
            <a href="{$base_dir|escape:'htmlall':'UTF-8'}" title="{l s='Home' mod='payplug'}">{l s='Home' mod='payplug'}</a>
        </li>
    </ul>
{elseif $version == 1.6}
    {capture name=path}<a href="{$link->getPageLink('my-account', true)|escape:'html'}" title="{l s='Manage my account' mod='payplug'}" rel="nofollow">{l s='My account' mod='payplug'}</a><span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{l s='Saved cards' mod='payplug'}{/capture}

    <h2>{l s='Saved cards' mod='payplug'}</h2>
    <p class="message alert alert-success">{l s='Card sucessfuly deleted.' mod='payplug'}</p>
    {if isset($payplug_cards) AND !empty($payplug_cards) AND sizeof($payplug_cards)}
        <div class="block-center" id="block-history">
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
        </div>
    {else}
        <p class="warning">{l s='You have no card registered yet.' mod='payplug'}</p>
    {/if}

    <ul class="footer_links clearfix">
        <li>
            <a class="btn btn-default button button-small" href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">
                <span><i class="icon-chevron-left"></i> {l s='Back to Your Account' mod='payplug'}</span>
            </a>
        </li>
        <li>
            <a class="btn btn-default button button-small" href="{if isset($force_ssl) && $force_ssl}{$base_dir_ssl|escape:'htmlall':'UTF-8'}{else}{$base_dir|escape:'htmlall':'UTF-8'}{/if}">
                <span><i class="icon-chevron-left"></i> {l s='Home' mod='payplug'}</span>
            </a>
        </li>
    </ul>
{/if}