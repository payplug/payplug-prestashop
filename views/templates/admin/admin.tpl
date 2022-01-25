{*
* 2022 PayPlug
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
*  @copyright 2022 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}

<link href="{$pathVendor|escape:'htmlall':'UTF-8'}" rel=preload as=script>
<link href="{$pathApp|escape:'htmlall':'UTF-8'}" rel=preload as=script>

<form class="payplug" action="{$form_action|escape:'htmlall':'UTF-8'}" method="post">
    {*
    {if isset($updated_deferred_state) && $updated_deferred_state}
        <p class="alert alert-warning" style="width: 100%;">
            <span>
                {assign "link_to_order_state" "<a href ='$admin_orders_link'>"}
                {l s='admin.admin.toaccesscontrols' sprintf=[$updated_deferred_state_name] tags=[$link_to_order_state, '<strong>'] mod='payplug'}
            </span>
        </p>
    {/if}
    <div class="panel panel-show">
        <div class="panel-heading">{l s='PRESENTATION' mod='payplug'}</div>
        <div class="panel-row">
            <img src="{$url_logo|escape:'htmlall':'UTF-8'}" />
            <p class="block-title">{l s='The payment solution that increases your sales' mod='payplug'}</p>
            <p>{l s='PayPlug provides merchants all the benefits of a full online payment solution.' mod='payplug'}</p>
            <ul>
                <li>{l s='Accept all Visa and MasterCard credit and debit cards' mod='payplug'}</li>
                <li>{l s='Display the payment form directly on your website, without redirection' mod='payplug'}</li>
                <li>{l s='Customise your payment page with your own colours and design' mod='payplug'}</li>
                <li>{l s='Avoid fraud by using Verified by Visa and MasterCard Secure Code' mod='payplug'}</li>
                <li>{l s='Automatic order update and email confirmation' mod='payplug'}</li>
                <li>{l s='Web interface to manage and export transaction history' mod='payplug'}</li>
                <li>{l s='Funds available on your bank account within 2 to 5 business days' mod='payplug'}</li>
            </ul>
        </div>
    </div>

    {include file='./panel/fieldset.tpl'}
    <p class="payplugInterpanel payplugAlert -success">
        <span>
            {l s='For more information about installing and configuring the plugin, please consult' mod='payplug'}
            <a class="payplugLink" href="{$faq_links.guide|escape:'htmlall':'UTF-8'}" data-e2e-link="faq" target="_blank">{l s='this support article' mod='payplug'}</a>.
        </span>
    </p>
    {include file='./panel/show.tpl'}
    *}

    <div id="app"></div>

    {*
    {include file='./panel/login.tpl'}
    {include file='./panel/settings.tpl'}
    *}
</form>

<script src="{$pathVendor|escape:'htmlall':'UTF-8'}"></script>
<script src="{$pathApp|escape:'htmlall':'UTF-8'}"></script>