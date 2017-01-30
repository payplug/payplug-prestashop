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

<div class="panel-heading">{l s='STATUS' mod='payplug'}</div>
{*<div class="panel-heading">{l s='STATUS' d='Modules.Payplug.Admin'}</div>*}
<div class="panel-row separate_margin_block">
    <input type="hidden" name="admin_ajax_url" value="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" />
    {if isset($check_configuration.warning) && !empty($check_configuration.warning) && sizeof($check_configuration.warning)}
        {foreach from = $check_configuration.warning item = warning}
            <p class="ppwarning">{$warning|escape:'quotes':'UTF-8'}</p>
        {/foreach}
    {/if}
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
</div>
<img class="loader" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/admin/spinner.gif" />
<div class="block-button">
    <input type="button" class="white-button submit-btn"
           name="submitCheckConfiguration" value="{l s='Check' mod='payplug'}">
           {*name="submitCheckConfiguration" value="{l s='Check' d='Modules.Payplug.Admin'}">*}
</div>