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

<div id="payplug_admin_form">
    <form action="{$form_action|escape:'htmlall':'UTF-8'}" method="post">
        <div class="panel">
            <div class="panel-heading">{l s='Uninstall configuration' mod='payplug'}</div>
            {*<div class="panel-heading">{l s='Uninstall configuration' d='Modules.Payplug.Admin'}</div>*}
            <div class="panel-row">
                <label class="left-block">{l s='Keep cards' mod='payplug'}</label>
                {*<label class="left-block">{l s='Keep cards' d='Modules.Payplug.Admin'}</label>*}
                <div class="block-right">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" class="switch-input" name="PAYPLUG_KEEP_CARDS" value="1"
                               id="payplug_keep_card_on" {if $PAYPLUG_KEEP_CARDS}checked="checked"{/if}>
                        <label title="{l s='Enable Debug mode' mod='payplug'}" for="payplug_keep_card_on"
                        {*label title="{l s='Enable Debug mode' d='Modules.Payplug.Admin'}" for="payplug_keep_card_on"*}
                               class="switch-label switch-label-on">{l s='YES' mod='payplug'}</label>
                               {*class="switch-label switch-label-on">{l s='YES' d='Modules.Payplug.Admin'}</label>*}
                        <input type="radio" class="switch-input" name="PAYPLUG_KEEP_CARDS" value="0" id="payplug_keep_card_off"
                               {if !$PAYPLUG_KEEP_CARDS}checked="checked"{/if}>
                        <label title="{l s='Disable one click payment' mod='payplug'}" for="payplug_keep_card_off"
                        {*<label title="{l s='Disable one click payment' d='Modules.Payplug.Admin'}" for="payplug_keep_card_off"*}
                               class="switch-label switch-label-off">{l s='NO' mod='payplug'}</label>
                               {*class="switch-label switch-label-off">{l s='NO' d='Modules.Payplug.Admin'}</label>*}
                        <span class="switch-selection"></span>
                        <a class="slide-button btn" {if !$PAYPLUG_KEEP_CARDS}style="left: 50%"{/if}></a>
                    </span>
                </div>
            </div>
            <div class="block-button">
                <input id="submitUninstallSettings" class="green-button" type="submit" name="submitUninstallSettings" value="{l s='Update settings' mod='payplug'}">
                {*<input id="submitUninstallSettings" class="green-button" type="submit" name="submitUninstallSettings" value="{l s='Update settings' d='Modules.Payplug.Admin'}">*}
            </div>
        </div>
    </form>
</div>