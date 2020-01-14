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

<div id="payplug_admin_form">
    <form action="{$form_action|escape:'htmlall':'UTF-8'}" method="post">
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

        <div class="panel checkFieldset">
            {include file='./fieldset.tpl' admin_ajax_url=$admin_ajax_url check_configuration=$check_configuration pp_version=$pp_version}
        </div>

        <p class="interpanel">{l s='For more information about installing and configuring the plugin, please consult' mod='payplug'} <a href="http://support.payplug.com/customer/portal/articles/2933281" target="_blank">{l s='this support article' mod='payplug'}</a>.</p>

        {include file='./login.tpl' login_infos=$login_infos}
    </form>
</div>
