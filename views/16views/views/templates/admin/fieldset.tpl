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
<div class="panel-heading">{l s='STATUS' mod='payplug'}</div>
<div class="panel-row separate_margin_block">
    {if isset($check_configuration.warning) && !empty($check_configuration.warning) && sizeof($check_configuration.warning)}
        {foreach from = $check_configuration.warning item = warning}
            <p class="ppwarning">{$warning|escape:'htmlall':'UTF-8'}</p>
        {/foreach}
    {/if}
    <p>{l s='Version of PayPlug module:' mod='payplug'} {$pp_version|escape:'htmlall':'UTF-8'}</p>
    {if isset($check_configuration.success) && !empty($check_configuration.success) && sizeof($check_configuration.success)}
        {foreach from = $check_configuration.success item = success}
            <p class="ppsuccess">{$success|escape:'htmlall':'UTF-8'}</p>
        {/foreach}
    {/if}
    {if isset($check_configuration.error) && !empty($check_configuration.error) && sizeof($check_configuration.error)}
        {foreach from = $check_configuration.error item = error}
            <p class="pperror">{$error|escape:'htmlall':'UTF-8'}</p>
        {/foreach}
    {/if}
    <p class="oney_carrier pperror">{l s='At least one of your shipping method isn’t configured for Oney.' mod='payplug'}</p>
    <p class="oney_carrier ppwarn">{l s='Your shipping methods configuration doesn’t allow to provide Oney' mod='payplug'}</p>
    <p class="oney_carrier ppsuccess">{l s='Your shipping methods are configured for Oney.' mod='payplug'}</p>

</div>
<img class="loader" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/admin/spinner.gif" />
<div class="block-button">
    <input type="button" class="white-button submit-btn"
           name="submitCheckConfiguration" value="{l s='Check' mod='payplug'}">
</div>
