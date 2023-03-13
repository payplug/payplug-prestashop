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
 * Do not edit or add to this file if you wish to upgrade Payplug module to newer
 * versions in the future.
*
*  @author Payplug SAS
*  @copyright 2023 Payplug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of Payplug SAS
*}
{capture name=path}<a href="{$link->getPageLink('my-account', true)|escape:'htmlall':'UTF-8'}" title="{l s='Manage my account' mod='payplug'}" rel="nofollow">{l s='My account' mod='payplug'}</a><span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{l s='Saved cards' mod='payplug'}{/capture}

<h2>{l s='Saved cards' mod='payplug'}</h2>
<p class="message alert alert-success" style="display: none;">{l s='Card sucessfully deleted.' mod='payplug'}</p>
{if isset($payplug_cards) AND !empty($payplug_cards) AND sizeof($payplug_cards)}
    <div class="block-center" id="block-history">
        <table id="card-list" class="std" data-e2e-card="list">
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
                <tr class="{$module_name|escape:'htmlall':'UTF-8'}Card {if $smarty.foreach.ppcards.first}first_item{elseif $smarty.foreach.ppcards.last}last_item{else}item{/if} {if $smarty.foreach.ppcards.index % 2}alternate_item{/if}" data-id_card="{$card.id_payplug_card|escape:'htmlall':'UTF-8'}" data-e2e-card="item">
                    <td class="id_payplug_card bold">{$smarty.foreach.ppcards.index|escape:'htmlall':'UTF-8' +1}</td>
                    <td class="brand bold">{if $card.brand != 'none'}{$card.brand|escape:'htmlall':'UTF-8'}{else}{l s='card' mod='payplug'}{/if}</td>
                    <td class="last4 bold">**** **** **** {$card.last4|escape:'htmlall':'UTF-8'}</td>
                    <td class="expiry_date bold">{$card.expiry_date|escape:'htmlall':'UTF-8'}</td>
                    <td class="delete bold"><a class="{$module_name|escape:'htmlall':'UTF-8'}Card_delete" href="{$payplug_delete_card_url|escape:'htmlall':'UTF-8'}" title="{l s='Delete' mod='payplug'}" data-e2e-card="delete">{l s='Delete' mod='payplug'}</a></td>
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
        <a class="btn btn-default button button-small" href="{$link->getPageLink('my-account', true)|escape:'htmlall':'UTF-8'}">
            <span><i class="icon-chevron-left"></i> {l s='Back to Your Account' mod='payplug'}</span>
        </a>
    </li>
    <li>
        <a class="btn btn-default button button-small" href="{if isset($force_ssl) && $force_ssl}{$base_dir_ssl|escape:'htmlall':'UTF-8'}{else}{$base_dir|escape:'htmlall':'UTF-8'}{/if}">
            <span><i class="icon-chevron-left"></i> {l s='Home' mod='payplug'}</span>
        </a>
    </li>
</ul>
