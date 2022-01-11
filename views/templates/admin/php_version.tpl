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
<p class="alert alert-danger" data-e2e-error="php_version">
    {assign "link_to_php_version_support_page" "<a href ='$faq_url'>"}
    {l s='admin.phpversion.wrongPhpVersion' tags=['<br>',$link_to_php_version_support_page] mod='payplug'} <br>
</p>
<div class="panel panel-show">
    <div class="panel-heading">{l s='admin.phpversion.heading' mod='payplug'}</div>
    <div class="panel-row">
        <img src="{$url_logo|escape:'htmlall':'UTF-8'}" />
        <p class="block-title">{l s='admin.phpversion.title' mod='payplug'}</p>
        <p>{l s='admin.phpversion.headlist' mod='payplug'}</p>
        <ul>
            <li>{l s='admin.phpversion.card' mod='payplug'}</li>
            <li>{l s='admin.phpversion.integrated' mod='payplug'}</li>
            <li>{l s='admin.phpversion.custom' mod='payplug'}</li>
            <li>{l s='admin.phpversion.secure' mod='payplug'}</li>
            <li>{l s='admin.phpversion.automatic' mod='payplug'}</li>
            <li>{l s='admin.phpversion.history' mod='payplug'}</li>
            <li>{l s='admin.phpversion.fast' mod='payplug'}</li>
        </ul>
    </div>
</div>
