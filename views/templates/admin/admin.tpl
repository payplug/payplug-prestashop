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

<form class="{$module_name}" action="{$form_action|escape:'htmlall':'UTF-8'}" method="post">
    {if isset($updated_deferred_state) && $updated_deferred_state}
        <p class="alert alert-warning" style="width: 100%;">
            <span>
                {assign "link_to_order_state" "<a href ='$admin_orders_link'>"}
                {l s='admin.admin.toaccesscontrols' sprintf=[$updated_deferred_state_name] tags=[$link_to_order_state, '<strong>'] mod={$module_name}}
            </span>
        </p>
    {/if}
    <div class="panel panel-show">
        <div class="panel-heading">{l s='PRESENTATION' mod={$module_name}}</div>
        <div class="panel-row">
            <img src="{$url_logo|escape:'htmlall':'UTF-8'}" />
            <p class="block-title">{l s='The payment solution that increases your sales' mod={$module_name}}</p>
            <p>{l s='PayPlug provides merchants all the benefits of a full online payment solution.' mod={$module_name}}</p>
            <ul>
                <li>{l s='Accept all Visa and MasterCard credit and debit cards' mod={$module_name}}</li>
                <li>{l s='Display the payment form directly on your website, without redirection' mod={$module_name}}</li>
                <li>{l s='Customise your payment page with your own colours and design' mod={$module_name}}</li>
                <li>{l s='Avoid fraud by using Verified by Visa and MasterCard Secure Code' mod={$module_name}}</li>
                <li>{l s='Automatic order update and email confirmation' mod={$module_name}}</li>
                <li>{l s='Web interface to manage and export transaction history' mod={$module_name}}</li>
                <li>{l s='Funds available on your bank account within 2 to 5 business days' mod={$module_name}}</li>
            </ul>
        </div>
    </div>

    {include file='./panel/fieldset.tpl'}
    <p class="{$module_name}Interpanel {$module_name}Alert -success">
        <span>
            {l s='For more information about installing and configuring the plugin, please consult' mod={$module_name}}
            <a class="{$module_name}Link" href="{$faq_links.guide|escape:'htmlall':'UTF-8'}" data-e2e-link="faq" target="_blank">{l s='this support article' mod={$module_name}}</a>.
        </span>
    </p>
    {include file='./panel/show.tpl'}
    {include file='./panel/login.tpl'}
    {include file='./panel/settings.tpl'}
</form>
