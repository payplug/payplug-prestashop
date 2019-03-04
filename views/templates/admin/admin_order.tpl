{*
* 2019 PayPlug
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
*  @copyright 2019 PayPlug SAS
*  @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}

<div class="panel panel-1-6" id="pppanel">

    <div class="panel-heading">
        <i class="icon-money"></i> {l s='Payplug payment details' mod='payplug'}
        {*<i class="icon-money"></i> {l s='Payplug payment details' d='Modules.Payplug.Admin'}*}
    </div>
    <img class="logo" src="{$logo_url|escape:'htmlall':'UTF-8'}" width="79" height="28" />

    {if $show_menu_installment}
        <div>
            <p>{l s='This order is subjected to an installment plan, whose status is' mod='payplug'} <span class="pp_inst_status">{$inst_status|escape:'htmlall':'UTF-8'}</span></p>
            <p>{l s='Payment schedule ID' mod='payplug'} : {$inst_id|escape:'htmlall':'UTF-8'}</p>
        </div>
        <div class="table-responsive half-width">
            <table class="table">
                <thead>
                <tr>
                    <th><span class="title_box ">{l s='Date' mod='payplug'}</span></th>
                    <th><span class="title_box ">{l s='Amount' mod='payplug'}</span></th>
                    <th><span class="title_box ">{l s='Status' mod='payplug'}</span></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$payment_list item=payment}
                    <tr class="pp_fixed_height">
                        <td>{$payment['date']|escape:'htmlall':'UTF-8'}</td>
                        <td>{displayPrice price=$payment['amount']}</td>
                        <td class="{$payment['status_class']|escape:'htmlall':'UTF-8'}">{$payment['status']|escape:'htmlall':'UTF-8'}</td>
                        {if isset($payment['id'])}
                            <td class="actions">
                                <button class="btn btn-default open_payment_information">
                                    <i class="icon-search"></i>
                                    {l s='Details' mod='payplug'}
                                </button>
                            </td>
                        {/if}
                    </tr>
                    {if isset($payment['id'])}
                        <tr class="payment_information" style="display: none;">
                            <td colspan="5">
                                <ul>
                                    <li><span class="ppbold">{l s='Payplug Payment ID' mod='payplug'} : </span>{$payment['id']|escape:'htmlall':'UTF-8'}</li>
                                    <li><span class="ppbold">{l s='Status' mod='payplug'} : {$payment['status']|escape:'htmlall':'UTF-8'}</span> {$payment['error']|escape:'htmlall':'UTF-8'}</li>
                                    <li><span class="ppbold">{l s='Amount' mod='payplug'} : </span>{displayPrice price=$payment['amount']}</li>
                                    <li><span class="ppbold">{l s='Paid at' mod='payplug'} : </span>{$payment['date']|escape:'htmlall':'UTF-8'}</li>
                                    <li><span class="ppbold">{l s='Credit card' mod='payplug'} : </span>{$payment['brand']|escape:'htmlall':'UTF-8'}</li>
                                    <li><span class="ppbold">{l s='Card mask' mod='payplug'} : </span>{$payment['card_mask']|escape:'htmlall':'UTF-8'}</li>
                                    <li><span class="ppbold">{l s='3-D Secure' mod='payplug'} : </span>{$payment['tds']|escape:'htmlall':'UTF-8'}</li>
                                    <li><span class="ppbold">{l s='Expiry Date' mod='payplug'} : </span>{$payment['card_date']|escape:'htmlall':'UTF-8'}</li>
                                    <li><span class="ppbold">{l s='Mode' mod='payplug'} : </span>
                                        <span class="ppred">
                                        <span class="ppbold">{$payment['mode']|escape:'htmlall':'UTF-8'}</span>
                                    </span>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    {/if}
                {/foreach}
                </tbody>
            </table>
            {if !$inst_paid}
                {if $inst_aborted}
                    <input class="btn green-button" type="submit" name="submitPPAbort" value="{l s='Aborted' mod='payplug'}" disabled="disabled" />
                {else}
                    <input class="btn green-button" type="submit" name="submitPPAbort" value="{l s='Abort' mod='payplug'}"/>
                {/if}
                <br class="clear" />
            {/if}
        </div>
    {/if}
    {if $show_menu_payment}
    <ul>
        <li><span class="ppbold">{l s='Payplug Payment ID' mod='payplug'} : </span>{$pay_id|escape:'htmlall':'UTF-8'}</li>
        {*<li><span class="ppbold">{l s='Payplug Payment ID' d='Modules.Payplug.Admin'} : </span>{$pay_id|escape:'htmlall':'UTF-8'}</li>*}
        <li><span class="ppbold">{l s='Status' mod='payplug'} : {$pay_status|escape:'htmlall':'UTF-8'}</span></li>
        {*<li><span class="ppbold">{l s='Status' d='Modules.Payplug.Admin'} : {$pay_status|escape:'htmlall':'UTF-8'}</span></li>*}
        <li><span class="ppbold">{l s='Amount' mod='payplug'} : </span>{displayPrice price=$pay_amount}</li>
        {*<li><span class="ppbold">{l s='Amount' d='Modules.Payplug.Admin'} : </span>{displayPrice price=$pay_amount}</li>*}
        <li><span class="ppbold">{l s='Paid at' mod='payplug'} : </span>{$pay_date|escape:'htmlall':'UTF-8'}</li>
        {*<li><span class="ppbold">{l s='Paid at' d='Modules.Payplug.Admin'} : </span>{$pay_date|escape:'htmlall':'UTF-8'}</li>*}
        <li><span class="ppbold">{l s='Credit card' mod='payplug'} : </span>{$pay_brand|escape:'htmlall':'UTF-8'}</li>
        {*<li><span class="ppbold">{l s='Credit card' d='Modules.Payplug.Admin'} : </span>{$pay_brand|escape:'htmlall':'UTF-8'}</li>*}
        <li><span class="ppbold">{l s='Card mask' mod='payplug'} : </span>{$pay_card_mask|escape:'htmlall':'UTF-8'}</li>
        {*<li><span class="ppbold">{l s='Card mask' d='Modules.Payplug.Admin'} : </span>{$pay_card_mask|escape:'htmlall':'UTF-8'}</li>*}
        <li><span class="ppbold">{l s='3-D Secure' mod='payplug'} : </span>{$pay_tds|escape:'htmlall':'UTF-8'}</li>
        {*<li><span class="ppbold">{l s='3-D Secure' d='Modules.Payplug.Admin'} : </span>{$pay_tds|escape:'htmlall':'UTF-8'}</li>*}
        <li><span class="ppbold">{l s='Expiry Date' mod='payplug'} : </span>{$pay_card_date|escape:'htmlall':'UTF-8'}</li>
        <li><span class="ppbold">{l s='Mode' mod='payplug'} : </span>
        {*<li><span class="ppbold">{l s='Mode' d='Modules.Payplug.Admin'} : </span>*}
            <span class="ppred">
                <span class="ppbold">{$pay_mode|escape:'htmlall':'UTF-8'}</span>
            </span>
        </li>
    </ul>
    {/if}

    {if $show_menu}
        <hr />
        {include file='./admin_order_refund.tpl'}
    {elseif $show_menu_refunded}
        <hr />
        {include file='./admin_order_refunded.tpl'}
    {elseif $show_menu_update}
        <hr />
        {include file='./admin_order_update.tpl'}
    {/if}

</div>
