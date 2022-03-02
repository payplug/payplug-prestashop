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
<div class="{$module_name}Panel">
    <div class="{$module_name}Panel_label">{l s='Account connected' mod={$module_name}}</div>
    <div class="{$module_name}Panel_content">
        <p class="{$module_name}Login_email">{$payplug_email|escape:'htmlall':'UTF-8'}</p>
    </div>
</div>
<div class="{$module_name}Panel">
    <div class="{$module_name}Panel_content">
        <a class="{$module_name}Link" target="_blank" href="{$site_url|escape:'htmlall':'UTF-8'}/portal" data-e2e-link="portal">{l s='Payplug Portal' mod={$module_name}}</a>
        <span class="{$module_name}Pipe">|</span>
        <button type="button" class="{$module_name}Link {$module_name}Login_logout" data-e2e-type="button" data-e2e-action="logout">{l s='Disconnect' mod={$module_name}}</button>
    </div>
</div>
