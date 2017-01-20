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
{if $version < 1.5}
    <br />
    <div id="pppanel" style="float: left">
        <fieldset style="width: 400px">
            <legend><img src="../img/t/AdminPayment.gif"> {l s='Payplug payment details' mod='payplug'}</legend>
            <img class="logo" src="{$logo_url|escape:'htmlall':'UTF-8'}" width="74" height="22" />
            <ul>
                <li><span class="ppbold">{l s='Payplug Payment ID' mod='payplug'} : </span>{$pay_id|escape:'htmlall':'UTF-8'}</li>
                <li><span class="ppbold">{l s='Status' mod='payplug'} : {$pay_status|escape:'htmlall':'UTF-8'}</span></li>
                <li><span class="ppbold">{l s='Amount' mod='payplug'} : </span>{displayPrice price=$pay_amount}</li>
                <li><span class="ppbold">{l s='Paid at' mod='payplug'} : </span>{$pay_date|escape:'htmlall':'UTF-8'}</li>
                <li><span class="ppbold">{l s='Credit card' mod='payplug'} : </span>{$pay_brand|escape:'htmlall':'UTF-8'}</li>
                <li><span class="ppbold">{l s='Card mask' mod='payplug'} : </span>{$pay_card_mask|escape:'htmlall':'UTF-8'}</li>
                <li><span class="ppbold">{l s='3-D Secure' mod='payplug'} : </span>{$pay_tds|escape:'htmlall':'UTF-8'}</li>
                {*<li><span class="ppbold">{l s='Processing fee' mod='payplug'} : </span>{$pay_fees|escape:'htmlall':'UTF-8'}</li>*}
                <li><span class="ppbold">{l s='Mode' mod='payplug'} : </span>
                <span class="ppred">
                    <span class="ppbold">{$pay_mode|escape:'htmlall':'UTF-8'}</span>
                    {*$pay_mode_info|escape:'htmlall':'UTF-8'*}
                </span>
                </li>
            </ul>

            {if $show_menu}
                <hr />
                {include file='./admin_order_refund.tpl'}
            {elseif $show_menu_refunded}
                <hr />
                {include file='./admin_order_refunded.tpl'}
            {/if}

        </fieldset>
    </div>
{elseif $version < 1.6}
    <br />
    <fieldset id="pppanel">
        <legend><img src="../img/admin/money.gif"> {l s='Payplug payment details' mod='payplug'}</legend>
        <ul>
            <li><span class="ppbold">{l s='Payplug Payment ID' mod='payplug'} : </span>{$pay_id|escape:'htmlall':'UTF-8'}</li>
            <li><span class="ppbold">{l s='Status' mod='payplug'} : {$pay_status|escape:'htmlall':'UTF-8'}</span></li>
            <li><span class="ppbold">{l s='Amount' mod='payplug'} : </span>{displayPrice price=$pay_amount}</li>
            <li><span class="ppbold">{l s='Paid at' mod='payplug'} : </span>{$pay_date|escape:'htmlall':'UTF-8'}</li>
            <li><span class="ppbold">{l s='Credit card' mod='payplug'} : </span>{$pay_brand|escape:'htmlall':'UTF-8'}</li>
            <li><span class="ppbold">{l s='Card mask' mod='payplug'} : </span>{$pay_card_mask|escape:'htmlall':'UTF-8'}</li>
            <li><span class="ppbold">{l s='3-D Secure' mod='payplug'} : </span>{$pay_tds|escape:'htmlall':'UTF-8'}</li>
            {*<li><span class="ppbold">{l s='Processing fee' mod='payplug'} : </span>{$pay_fees|escape:'htmlall':'UTF-8'}</li>*}
            <li><span class="ppbold">{l s='Mode' mod='payplug'} : </span>
                <span class="ppred">
                    <span class="ppbold">{$pay_mode|escape:'htmlall':'UTF-8'}</span>
                    {*$pay_mode_info|escape:'htmlall':'UTF-8'*}
                </span>
            </li>
        </ul>

        {if $show_menu}
            <hr />
            {include file='./admin_order_refund.tpl'}
        {elseif $show_menu_refunded}
            <hr />
            {include file='./admin_order_refunded.tpl'}
        {/if}

    </fieldset>
{elseif $version >= 1.6}
    <div class="panel panel-1-6" id="pppanel">

        <div class="panel-heading">
            <i class="icon-money"></i> {l s='Payplug payment details' mod='payplug'}
        </div>
        <img class="logo" src="{$logo_url|escape:'htmlall':'UTF-8'}" width="74" height="22" />

        <ul>
            <li><span class="ppbold">{l s='Payplug Payment ID' mod='payplug'} : </span>{$pay_id|escape:'htmlall':'UTF-8'}</li>
            <li><span class="ppbold">{l s='Status' mod='payplug'} : {$pay_status|escape:'htmlall':'UTF-8'}</span></li>
            <li><span class="ppbold">{l s='Amount' mod='payplug'} : </span>{displayPrice price=$pay_amount}</li>
            <li><span class="ppbold">{l s='Paid at' mod='payplug'} : </span>{$pay_date|escape:'htmlall':'UTF-8'}</li>
            <li><span class="ppbold">{l s='Credit card' mod='payplug'} : </span>{$pay_brand|escape:'htmlall':'UTF-8'}</li>
            <li><span class="ppbold">{l s='Card mask' mod='payplug'} : </span>{$pay_card_mask|escape:'htmlall':'UTF-8'}</li>
            <li><span class="ppbold">{l s='3-D Secure' mod='payplug'} : </span>{$pay_tds|escape:'htmlall':'UTF-8'}</li>
            <li><span class="ppbold">{l s='Date' mod='payplug'} : </span>{$pay_card_date|escape:'htmlall':'UTF-8'}</li>
            {*<li><span class="ppbold">{l s='Processing fee' mod='payplug'} : </span>{$pay_fees|escape:'htmlall':'UTF-8'}</li>*}
            <li><span class="ppbold">{l s='Mode' mod='payplug'} : </span>
                <span class="ppred">
                    <span class="ppbold">{$pay_mode|escape:'htmlall':'UTF-8'}</span>
                    {*$pay_mode_info|escape:'htmlall':'UTF-8'*}
                </span>
            </li>
        </ul>

        {if $show_menu}
            <hr />
            {include file='./admin_order_refund.tpl'}
        {elseif $show_menu_refunded}
            <hr />
            {include file='./admin_order_refunded.tpl'}
        {/if}

    </div>
{else}
    <p>{l s='Your Prestashop version is not compatible' mod='payplug'}</p>
{/if}