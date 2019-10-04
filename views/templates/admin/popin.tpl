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

<div class="ppoverlay"></div>
<div id="payplug_popin" class="{$type|escape:'htmlall':'UTF-8'}">
    <div class="status_bar">
{if $type == 'pwd'}
        <div class="panel-heading">{l s='LIVE mode' mod='payplug'}</div>
        <span class="ppclose">x</span>
    </div>
    <form action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" method="post">
        <p>{l s='Please enter your Payplug account password' mod='payplug'}</p>
        <p class="pperror hide">{l s='The password you entered is invalid' mod='payplug'}</p>
        <input type="password" name="pwd" />
        <div class="block-button">
            <input type="button" class="popin-button ppcancel white-button" value="{l s='Cancel' mod='payplug'}">
            <input class="popin-button green-button" type="submit" name="submitPopin_{$type|escape:'htmlall':'UTF-8'}" value="{l s='Ok' mod='payplug'}">
        </div>
{elseif $type == 'activate'}
        <div class="panel-heading">{l s='LIVE mode' mod='payplug'}</div>
        <span class="ppclose">x</span>
    </div>
    <form action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" method="post">
        <p class="ppoc">{l s='You need to activate your account before performing LIVE transactions.' mod='payplug'}</p>
        <div class="block-button">
            <input type="button" class="popin-button ppcancel white-button" value="{l s='Cancel' mod='payplug'}">
            <a class="green-button popin-button" target="_blank" href="{$site_url|escape:'htmlall':'UTF-8'}/portal">{l s='Activate account' mod='payplug'}</a>
        </div>
{elseif $type == 'premium'}
        <div class="panel-heading">{l s='Enable advanced feature' mod='payplug'}</div>
        <span class="ppclose">x</span>
    </div>
    <form action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" method="post">
        <p class="ppoc">{l s='You cannot access this feature. For further information please contact our team : support@payplug.com' mod='payplug'}</p>
        <div class="block-button">
            <input class="popin-button green-button center-button ppcancel" type="button" name="submitPopin_{$type|escape:'htmlall':'UTF-8'}" value="{l s='Ok' mod='payplug'}">
        </div>
{elseif $type == 'confirm'}
        <div class="panel-heading">{l s='Save settings' mod='payplug'}</div>
        <span class="ppclose">x</span>
    </div>
    <form action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" method="post">
        <p>{l s='Once the settings are saved, the Payplug module will be displayed:' mod='payplug'}
        <ul>
            <li>{l s='Mode:' mod='payplug'} <span class="ppbold">{if $sandbox == 1}{l s='TEST' mod='payplug'}{else}{l s='LIVE' mod='payplug'}{/if}</span></li>
            <li>{l s='Payment page:' mod='payplug'} <span class="ppbold">{if $embedded == 1}{l s='EMBEDDED' mod='payplug'}{else}{l s='REDIRECTED' mod='payplug'}{/if}</span></li>
            <li>{l s='One-click payments:' mod='payplug'} <span class="ppbold">{if $one_click == 1}{l s='ENABLED' mod='payplug'}{else}{l s='DISABLED' mod='payplug'}{/if}</span></li>
            <li>{l s='Installments :' mod='payplug'} <span class="ppbold">{if $installment == 1}{l s='ENABLED' mod='payplug'}{else}{l s='DISABLED' mod='payplug'}{/if}</span></li>
            <li>{l s='Deferred payments :' mod='payplug'} <span class="ppbold">{if $deferred == 1}{l s='ENABLED' mod='payplug'}{else}{l s='DISABLED' mod='payplug'}{/if}</span></li>
        </ul>
        </p>
        <div class="block-button">
            <input type="button" class="popin-button ppcancel white-button{if $activate == 1} activate{/if}" value="{l s='Cancel' mod='payplug'}">
            <input class="popin-button green-button" type="submit" name="submitPopin_{$type|escape:'htmlall':'UTF-8'}{if $activate == 1}_a{/if}" value="{l s='SAVE SETTINGS' mod='payplug'}">
        </div>
{elseif $type == 'desactivate'}
        <div class="panel-heading">{l s='Desactivate' mod='payplug'}</div>
        <span class="ppclose">x</span>
    </div>
    <form action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" method="post">
        <p>{l s='Payplug will no longer be displayed as a payment option to your customers.' mod='payplug'}</p>
        <div class="block-button">
            <input type="button" class="popin-button ppcancel white-button" value="{l s='Cancel' mod='payplug'}">
            <input class="popin-button green-button" type="submit" name="submitPopin_{$type|escape:'htmlall':'UTF-8'}" value="{l s='Ok' mod='payplug'}">
        </div>
{elseif $type == 'refund'}
        <div class="panel-heading">{l s='Refund' mod='payplug'}</div>
        <span class="ppclose">x</span>
    </div>
    <form action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" method="post">
        <p>{l s='You can refund your customer on his card from the Refund with Payplug section located on this page.' mod='payplug'} <a href="http://support.payplug.com/customer/portal/articles/2563976" target="_blank">{l s='More information' mod='payplug'}</a></p>
        <div class="block-button">
            <input type="button" class="popin-button center-button ppclose green-button" value="{l s='Ok' mod='payplug'}">
        </div>
{elseif $type == 'abort'}
    <div class="panel-heading">{l s='Suspend installment' mod='payplug'}</div>
    <span class="ppclose">x</span>
    </div>
    <form action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" method="post">
        <input type="hidden" name="inst_id" value="{$inst_id|escape:'htmlall':'UTF-8'}" />
        <p>{l s='Are you sure you want to suspend the installment plan on this order?' mod='payplug'}</p>
        <p>{l s='Your customer won’t be charged on the due dates.' mod='payplug'}</p>
        <div class="block-button">
            <input type="button" class="popin-button ppcancel white-button" value="{l s='Cancel' mod='payplug'}">
            <input class="popin-button green-button no-width" type="submit" name="submitPopin_{$type|escape:'htmlall':'UTF-8'}" value="{l s='Suspend' mod='payplug'}">
        </div>
{/if}
    </form>
</div>