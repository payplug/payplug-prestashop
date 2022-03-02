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
<div class="panel {$module_name}Config">
    <div class="panel-heading">{l s='STATUS' mod={$module_name}}</div>
    <div class="panel-row">
        {if isset($check_configuration.warning) && !empty($check_configuration.warning) && sizeof($check_configuration.warning)}
            {foreach from = $check_configuration.warning item = warning}
                <p class="{$module_name}Alert -warning"><span>{$warning|escape:'htmlall':'UTF-8'}</span></p>
            {/foreach}
        {/if}
        <p>{l s='Version of PayPlug module:' mod={$module_name}} {$pp_version|escape:'htmlall':'UTF-8'}</p>
        {if isset($check_configuration.success) && !empty($check_configuration.success) && sizeof($check_configuration.success)}
            {foreach from = $check_configuration.success item = success}
                <p class="{$module_name}Config_item -success"><span>{$success|escape:'htmlall':'UTF-8'}</span></p>
            {/foreach}
        {/if}
        {if isset($check_configuration.error) && !empty($check_configuration.error) && sizeof($check_configuration.error)}
            {foreach from = $check_configuration.error item = error}
                <p class="{$module_name}Config_item -error"><span>{$error|escape:'htmlall':'UTF-8'}</span></p>
            {/foreach}
        {/if}
        {if isset($check_configuration.other) && !empty($check_configuration.other) && sizeof($check_configuration.other)}
            {foreach from = $check_configuration.other item = other}
                <p class="{$module_name}Config_item -{$other.type|escape:'quotes':'UTF-8'}"><span>{$other.text|escape:'quotes':'UTF-8'}</span></p>
            {/foreach}
        {/if}

        <img class="{$module_name}Loader" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/admin/spinner.gif" />
    </div>
    <div class="panel-footer">
        <button type="button" class="{$module_name}Button {$module_name}Config_check" data-e2e-type="button" data-e2e-action="check">{l s='Check' mod={$module_name}}</button>
    </div>
</div>
