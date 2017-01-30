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

<div id="payplug_admin_form">
    <form action="{$form_action|escape:'htmlall':'UTF-8'}" method="post">
        <div class="panel panel-show">
            <div class="panel-heading">{l s='PRESENTATION' mod='payplug'}</div>
            {*<div class="panel-heading">{l s='PRESENTATION' d='Modules.Payplug.Admin'}</div>*}
            <div class="panel-row">
                <img src="{$url_logo|escape:'htmlall':'UTF-8'}" />
                <p class="block-title">{l s='The payment solution that increases your sales' mod='payplug'}</p>
                {*<p class="block-title">{l s='The payment solution that increases your sales' d='Modules.Payplug.Admin'}</p>*}
                <p>{l s='PayPlug provides merchants all the benefits of a full online payment solution.' mod='payplug'}</p>
                {*<p>{l s='PayPlug provides merchants all the benefits of a full online payment solution.' d='Modules.Payplug.Admin'}</p>*}
                <ul>
                    <li>{l s='Accept all Visa and MasterCard credit and debit cards' mod='payplug'}</li>
                    {*<li>{l s='Accept all Visa and MasterCard credit and debit cards' d='Modules.Payplug.Admin'}</li>*}
                    <li>{l s='Display the payment form directly on your website, without redirection' mod='payplug'}</li>
                    {*<li>{l s='Display the payment form directly on your website, without redirection' d='Modules.Payplug.Admin'}</li>*}
                    <li>{l s='Customise your payment page with your own colours and design' mod='payplug'}</li>
                    {*<li>{l s='Customise your payment page with your own colours and design' d='Modules.Payplug.Admin'}</li>*}
                    <li>{l s='Avoid fraud by using Verified by Visa and MasterCard Secure Code' mod='payplug'}</li>
                    {*<li>{l s='Avoid fraud by using Verified by Visa and MasterCard Secure Code' d='Modules.Payplug.Admin'}</li>*}
                    <li>{l s='Automatic order update and email confirmation' mod='payplug'}</li>
                    {*<li>{l s='Automatic order update and email confirmation' d='Modules.Payplug.Admin'}</li>*}
                    <li>{l s='Web interface to manage and export transaction history' mod='payplug'}</li>
                    {*<li>{l s='Web interface to manage and export transaction history' d='Modules.Payplug.Admin'}</li>*}
                    <li>{l s='Funds available on your bank account within 2 to 5 business days' mod='payplug'}</li>
                    {*<li>{l s='Funds available on your bank account within 2 to 5 business days' d='Modules.Payplug.Admin'}</li>*}
                    <li>{l s='Our fees adapt to your volume : sell more, pay less. No fees for wire transfer and refunds' mod='payplug'}</li>
                    {*<li>{l s='Our fees adapt to your volume : sell more, pay less. No fees for wire transfer and refunds' d='Modules.Payplug.Admin'}</li>*}
                </ul>
            </div>
        </div>

        <div class="panel checkFieldset">
            {include file='./fieldset.tpl' admin_ajax_url=$admin_ajax_url check_configuration=$check_configuration}
        </div>

        <p class="interpanel">{l s='For more information about installing and configuring the plugin, please consult' mod='payplug'} <a href="http://support.payplug.com/customer/portal/articles/2591965" target="_blank">{l s='this support article' mod='payplug'}</a>.</p>
        {*<p class="interpanel">{l s='For more information about installing and configuring the plugin, please consult' d='Modules.Payplug.Admin'} <a href="http://support.payplug.com/customer/portal/articles/2591965" target="_blank">{l s='this support article' d='Modules.Payplug.Admin'}</a>.</p>*}

        {include file='./login.tpl' login_infos=$login_infos}

        <div class="panel">
            <div class="panel-heading">{l s='Debug mode' mod='payplug'}</div>
            {*<div class="panel-heading">{l s='Debug mode' d='Modules.Payplug.Admin'}</div>*}
            <div class="panel-row">
                <label class="left-block">{l s='Enable debug mode' mod='payplug'}</label>
                {*<label class="left-block">{l s='Enable debug mode' d='Modules.Payplug.Admin'}</label>*}
                <div class="block-right">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" class="switch-input" name="PAYPLUG_DEBUG_MODE" value="on"
                               id="payplug_debug_mode_on" {if $PAYPLUG_DEBUG_MODE}checked="checked"{/if}>
                        <label title="{l s='Enable Debug mode' mod='payplug'}" for="payplug_debug_mode_on"
                        {*<label title="{l s='Enable Debug mode' d='Modules.Payplug.Admin'}" for="payplug_debug_mode_on"*}
                               class="switch-label switch-label-on">{l s='YES' mod='payplug'}</label>
                               {*class="switch-label switch-label-on">{l s='YES' d='Modules.Payplug.Admin'}</label>*}
                        <input type="radio" class="switch-input" name="PAYPLUG_DEBUG_MODE" value="off" id="payplug_debug_mode_off"
                               {if !$PAYPLUG_DEBUG_MODE}checked="checked"{/if}>
                        <label title="{l s='Disable one click payment' mod='payplug'}" for="payplug_debug_mode_off"
                        {*<label title="{l s='Disable one click payment' d='Modules.Payplug.Admin'}" for="payplug_debug_mode_off"*}
                               class="switch-label switch-label-off">{l s='NO' mod='payplug'}</label>
                               {*class="switch-label switch-label-off">{l s='NO' d='Modules.Payplug.Admin'}</label>*}
                        <span class="switch-selection"></span>
                        <a class="slide-button btn" {if !$PAYPLUG_DEBUG_MODE}style="left: 50%"{/if}></a>
                    </span>
                </div>
            </div>
        </div>
    </form>
</div>