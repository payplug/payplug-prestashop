{*
* 2017 PayPlug
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
*  @copyright 2017 PayPlug SAS
*  @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}

<div class="ppoverlay"></div>
<div id="payplug_popin" class="{$type|escape:'htmlall':'UTF-8'}">
    <div class="status_bar">
{if $type == 'pwd'}
        <div class="panel-heading">{l s='LIVE mode' mod='payplug'}</div>
        {*<div class="panel-heading">{l s='LIVE mode' d='Modules.Payplug.Admin'}</div>*}
        <span class="ppclose">x</span>
    </div>
    <form action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" method="post">
        <p>{l s='Please enter your Payplug account password' mod='payplug'}</p>
        {*<p>{l s='Please enter your Payplug account password' d='Modules.Payplug.Admin'}</p>*}
        <p class="pperror hide">{l s='The password you entered is invalid' mod='payplug'}</p>
        {*<p class="pperror hide">{l s='The password you entered is invalid' d='Modules.Payplug.Admin'}</p>*}
        <input type="password" name="pwd" />
        <div class="block-button">
            <input type="button" class="popin-button ppcancel white-button" value="{l s='Cancel' mod='payplug'}">
            {*<input type="button" class="popin-button ppcancel white-button" value="{l s='Cancel' d='Modules.Payplug.Admin'}">*}
            <input class="popin-button green-button" type="submit" name="submitPopin_{$type|escape:'htmlall':'UTF-8'}" value="{l s='Ok' mod='payplug'}">
            {*<input class="popin-button green-button" type="submit" name="submitPopin_{$type|escape:'htmlall':'UTF-8'}" value="{l s='Ok' d='Modules.Payplug.Admin'}">*}
        </div>
{elseif $type == 'activate'}
        <div class="panel-heading">{l s='LIVE mode' mod='payplug'}</div>
        {*<div class="panel-heading">{l s='LIVE mode' d='Modules.Payplug.Admin'}</div>*}
        <span class="ppclose">x</span>
    </div>
    <form action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" method="post">
        <p class="ppoc">{l s='You need to activate your account before performing LIVE transactions.' mod='payplug'}</p>
        {*<p class="ppoc">{l s='You need to activate your account before performing LIVE transactions.' d='Modules.Payplug.Admin'}</p>*}
        <div class="block-button">
            <input type="button" class="popin-button ppcancel white-button" value="{l s='Cancel' mod='payplug'}">
            {*<input type="button" class="popin-button ppcancel white-button" value="{l s='Cancel' d='Modules.Payplug.Admin'}">*}
            <a class="green-button popin-button" target="_blank" href="{$payplug_url|escape:'htmlall':'UTF-8'}/portal">{l s='Activate account' mod='payplug'}</a>
            {*<a class="green-button popin-button" target="_blank" href="{$payplug_url|escape:'htmlall':'UTF-8'}/portal">{l s='Activate account' d='Modules.Payplug.Admin'}</a>*}
        </div>
{elseif $type == 'premium'}
        <div class="panel-heading">{l s='Enable one-click payments' mod='payplug'}</div>
        {*<div class="panel-heading">{l s='Enable one-click payments' d='Modules.Payplug.Admin'}</div>*}
        <span class="ppclose">x</span>
    </div>
    <form action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" method="post">
        <p class="ppoc">{l s='This option is available only to Premium accounts' mod='payplug'}</p>
        {*<p class="ppoc">{l s='This option is available only to Premium accounts' d='Modules.Payplug.Admin'}</p>*}
        <div class="block-button">
            <input class="popin-button green-button center-button ppcancel" type="button" name="submitPopin_{$type|escape:'htmlall':'UTF-8'}" value="{l s='Ok' mod='payplug'}">
            {*<input class="popin-button green-button center-button ppcancel" type="button" name="submitPopin_{$type|escape:'htmlall':'UTF-8'}" value="{l s='Ok' d='Modules.Payplug.Admin'}">*}
        </div>
{elseif $type == 'confirm'}
        <div class="panel-heading">{l s='Save settings' mod='payplug'}</div>
        {*<div class="panel-heading">{l s='Save settings' d='Modules.Payplug.Admin'}</div>*}
        <span class="ppclose">x</span>
    </div>
    <form action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" method="post">
        <p>{l s='Once the settings are saved, the Payplug module will be displayed:' mod='payplug'}
        {*<p>{l s='Once the settings are saved, the Payplug module will be displayed:' d='Modules.Payplug.Admin'}*}
        <ul>
            <li>{l s='Mode:' mod='payplug'} <span class="ppbold">{if $sandbox == 1}{l s='TEST' mod='payplug'}{else}{l s='LIVE' mod='payplug'}{/if}</span></li>
            {*<li>{l s='Mode:' d='Modules.Payplug.Admin'} <span class="ppbold">{if $sandbox == 1}{l s='TEST' d='Modules.Payplug.Admin'}{else}{l s='LIVE' d='Modules.Payplug.Admin'}{/if}</span></li>*}
            <li>{l s='Payment page:' mod='payplug'} <span class="ppbold">{if $embedded == 1}{l s='EMBEDDED' mod='payplug'}{else}{l s='REDIRECTED' mod='payplug'}{/if}</span></li>
            {*<li>{l s='Payment page:' d='Modules.Payplug.Admin'} <span class="ppbold">{if $embedded == 1}{l s='EMBEDDED' d='Modules.Payplug.Admin'}{else}{l s='REDIRECTED' d='Modules.Payplug.Admin'}{/if}</span></li>*}
            <li>{l s='One-click payments:' mod='payplug'} <span class="ppbold">{if $one_click == 1}{l s='ENABLED' mod='payplug'}{else}{l s='DISABLED' mod='payplug'}{/if}</span></li>
            {*<li>{l s='One-click payments:' d='Modules.Payplug.Admin'} <span class="ppbold">{if $one_click == 1}{l s='ENABLED' d='Modules.Payplug.Admin'}{else}{l s='DISABLED' d='Modules.Payplug.Admin'}{/if}</span></li>*}
        </ul>
        </p>
        <div class="block-button">
            <input type="button" class="popin-button ppcancel white-button{if $activate == 1} activate{/if}" value="{l s='Cancel' mod='payplug'}">
            {*<input type="button" class="popin-button ppcancel white-button{if $activate == 1} activate{/if}" value="{l s='Cancel' d='Modules.Payplug.Admin'}">*}
            <input class="popin-button green-button" type="submit" name="submitPopin_{$type|escape:'htmlall':'UTF-8'}{if $activate == 1}_a{/if}" value="{l s='SAVE SETTINGS' mod='payplug'}">
            {*<input class="popin-button green-button" type="submit" name="submitPopin_{$type|escape:'htmlall':'UTF-8'}{if $activate == 1}_a{/if}" value="{l s='SAVE SETTINGS' d='Modules.Payplug.Admin'}">*}
        </div>
{elseif $type == 'desactivate'}
        <div class="panel-heading">{l s='Desactivate' mod='payplug'}</div>
        {*<div class="panel-heading">{l s='Desactivate' d='Modules.Payplug.Admin'}</div>*}
        <span class="ppclose">x</span>
    </div>
    <form action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" method="post">
        <p>{l s='Payplug will no longer be displayed as a payment option to your customers.' mod='payplug'}</p>
        {*<p>{l s='Payplug will no longer be displayed as a payment option to your customers.' d='Modules.Payplug.Admin'}</p>*}
        <div class="block-button">
            <input type="button" class="popin-button ppcancel white-button" value="{l s='Cancel' mod='payplug'}">
            {*<input type="button" class="popin-button ppcancel white-button" value="{l s='Cancel' d='Modules.Payplug.Admin'}">*}
            <input class="popin-button green-button" type="submit" name="submitPopin_{$type|escape:'htmlall':'UTF-8'}" value="{l s='Ok' mod='payplug'}">
            {*<input class="popin-button green-button" type="submit" name="submitPopin_{$type|escape:'htmlall':'UTF-8'}" value="{l s='Ok' d='Modules.Payplug.Admin'}">*}
        </div>
{elseif $type == 'refund'}
        <div class="panel-heading">{l s='Refund' mod='payplug'}</div>
        {*<div class="panel-heading">{l s='Refund' d='Modules.Payplug.Admin'}</div>*}
        <span class="ppclose">x</span>
    </div>
    <form action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" method="post">
        <p>{l s='You can refund your customer on his card from the Refund with Payplug section located on this page.' mod='payplug'} <a href="http://support.payplug.com/customer/portal/articles/2563976" target="_blank">{l s='More information' mod='payplug'}</a></p>
        {*<p>{l s='You can refund your customer on his card from the Refund with Payplug section located on this page.' d='Modules.Payplug.Admin'} <a href="http://support.payplug.com/customer/portal/articles/2563976" target="_blank">{l s='More information' d='Modules.Payplug.Admin'}</a></p>*}
        <div class="block-button">
            <input type="button" class="popin-button center-button ppclose green-button" value="{l s='Ok' mod='payplug'}">
            {*<input type="button" class="popin-button center-button ppclose green-button" value="{l s='Ok' d='Modules.Payplug.Admin'}">*}
        </div>
{/if}
    </form>
</div>