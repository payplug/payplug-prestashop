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
<!-- MODULE Payplug -->
{if $version < 1.5}
    <li>
        <a title="{l s='Saved cards' mod='payplug'}" href="{$payplug_cards_url|escape:'htmlall':'UTF-8'}">
            <img class="icon" alt="{l s='Saved cards' mod='payplug'}" src="{$payplug_icon_url|escape:'htmlall':'UTF-8'}">
        </a>
        <a title="Bons de réduction" href="{$payplug_cards_url|escape:'htmlall':'UTF-8'}">{l s='Saved cards' mod='payplug'}</a>
    </li>
{elseif $version < 1.6}
    <li>
        <a title="{l s='Saved cards' mod='payplug'}" href="{$payplug_cards_url|escape:'htmlall':'UTF-8'}">
            <img class="icon" alt="{l s='Saved cards' mod='payplug'}" src="{$payplug_icon_url|escape:'htmlall':'UTF-8'}">
             {l s='Saved cards' mod='payplug'}
        </a>
    </li>
{elseif $version >= 1.6}
    <li>
        <a href="{$payplug_cards_url|escape:'htmlall':'UTF-8'}" title="{l s='Saved cards' mod='payplug'}">
            <i class="icon-credit-card"></i>
            <span>{l s='Saved cards' mod='payplug'}</span>
        </a>
    </li>
{else}
    <p>{l s='Your Prestashop version is not compatible' mod='payplug'}</p>
{/if}
<!-- END : MODULE Payplug -->