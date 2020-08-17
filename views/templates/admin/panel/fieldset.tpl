{*
* 2020 PayPlug
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
*  @copyright 2020 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
<div class="panel payplugConfig">
    <div class="panel-heading">{l s='STATUS' mod='payplug'}</div>
    <div class="panel-row">
        {if isset($check_configuration.warning) && !empty($check_configuration.warning) && sizeof($check_configuration.warning)}
            {foreach from = $check_configuration.warning item = warning}
                <p class="payplugAlert payplugAlert-warning"><span>{$warning|escape:'quotes':'UTF-8'}</span></p>
            {/foreach}
        {/if}
        <p>{l s='Version of PayPlug module:' mod='payplug'} {$pp_version|escape:'htmlall':'UTF-8'}</p>
        {if isset($check_configuration.success) && !empty($check_configuration.success) && sizeof($check_configuration.success)}
            {foreach from = $check_configuration.success item = success}
                <p class="payplugConfig_item payplugConfig_item-success"><span>{$success|escape:'htmlall':'UTF-8'}</span></p>
            {/foreach}
        {/if}
        {if isset($check_configuration.error) && !empty($check_configuration.error) && sizeof($check_configuration.error)}
            {foreach from = $check_configuration.error item = error}
                <p class="payplugConfig_item payplugConfig_item-error"><span>{$error|escape:'htmlall':'UTF-8'}</span></p>
            {/foreach}
        {/if}

        <p class="payplugConfig_item payplugConfig_item-oney payplugConfig_item-error"><span>{l s='At least one of your shipping method isn’t configured for Oney.' mod='payplug'}</span></p>
        <p class="payplugConfig_item payplugConfig_item-oney payplugConfig_item-warning"><span>{l s='Your shipping methods configuration doesn’t allow to provide Oney' mod='payplug'}</span></p>
        <p class="payplugConfig_item payplugConfig_item-oney payplugConfig_item-success"><span>{l s='Your shipping methods are configured for Oney.' mod='payplug'}</span></p>

        {if $PAYPLUG_ONEY}
            {if $payplug_switch.oney_tos.checked && empty($PAYPLUG_ONEY_TOS_URL)}
                <p class="payplugAlert payplugAlert-warning">{l s='Please manage the “General terms and conditions” part for Oney' mod='payplug'}</p>
            {/if}
        {/if}


        <img class="payplugLoader" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/admin/spinner.gif" />
    </div>
    <div class="panel-footer">
        <button type="button" class="payplugButton payplugConfig_check">{l s='Check' mod='payplug'}</button>
    </div>
</div>
