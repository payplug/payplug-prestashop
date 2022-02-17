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
<div class="{$module_name}Bancontact panel-row separate_margin_block{if ${$module_name}_switch.sandbox.checked} -hide{/if}">
    <div class="{$module_name}Panel">
        <div class="{$module_name}Panel_label">{l s='admin.panel.settings.bancontact.label' mod={$module_name}}</div>
        <div class="{$module_name}Panel_content">{include file='./switch.tpl' switch=$payplug_switch.bancontact}</div>
    </div>
    <div class="{$module_name}Panel">
        <div class="{$module_name}Panel_content">
            <p>
                {l s='admin.panel.settings.bancontact.content' mod={$module_name}}
                <a class="{$module_name}Link" href="{$faq_links.bancontact|escape:'htmlall':'UTF-8'}" data-e2e-link="faq" target="_blank">
                    {l s='admin.panel.settings.bancontact.link' mod={$module_name}}
                </a>
            </p>
        </div>
    </div>
</div>
