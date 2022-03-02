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
<div class="{$module_name}Installment panel-row separate_margin_block">
    <div class="{$module_name}Panel">
        <div class="{$module_name}Panel_label">{l s='admin.panel.setting.installment.label' mod={$module_name}}</div>
        <div class="{$module_name}Panel_content">{include file='./switch.tpl' switch=$payplug_switch.installment}</div>
    </div>
    <div class="{$module_name}Panel">
        <div class="{$module_name}Panel_content">
            <p>
                {l s='Allow customers to spread out payments over 2, 3 or 4 installments.' mod={$module_name}}
                <a class="{$module_name}Link" href="{$faq_links.installments|escape:'htmlall':'UTF-8'}" data-e2e-link="faq" target="_blank">{l s='Learn more.' mod={$module_name}}</a>
            </p>

            <div class="{$module_name}Tips -{$payplug_switch.installment.name|escape:'htmlall':'UTF-8'}">
                <div class="{$module_name}Tips_item -left {if !$payplug_switch.installment.checked || !$payplug_switch.installment.active} -hide{/if}">
                    <p class="{$module_name}Alert -warning"><span>{l s='Payments by installment are not guaranteed. A default of payment may occur for the upcoming installments.' mod={$module_name}}</span></p>
                    <p>
                        {l s='You can consult all your past and pending installment payments in' mod={$module_name}}
                        <a class="{$module_name}Link" href="{$installments_panel_url|escape:'htmlall':'UTF-8'}" data-e2e-link="installment"> {l s='a dedicated menu' mod={$module_name}}</a>
                        {l s='made accessible from the navigation bar, and in the details of each order within the' mod={$module_name}}
                        <i> {l s='Payment with PayPlug' mod={$module_name}}</i>
                    </p>
                    <p>
                        <a class="{$module_name}Link" href="{$faq_links.installments|escape:'htmlall':'UTF-8'}" data-e2e-link="faq" target="_blank">{l s='Learn more.' mod={$module_name}}</a>
                    </p>

                    <div class="{$module_name}Installment_fieldset payplugPanel">
                        <div class="{$module_name}Panel_label">{l s='Enable payments:' mod={$module_name}}</div>
                        <div class="{$module_name}Panel_content">
                            <label for="payplug_installment_mode_2">
                                <input id="payplug_installment_mode_2" type="radio" name="payplug_inst_mode" value="2" {if $inst_mode == 2}checked="checked"{/if}>
                                {l s='in 2 installments' mod={$module_name}}
                            </label>
                            <label for="payplug_installment_mode_3">
                                <input id="payplug_installment_mode_3" type="radio" name="payplug_inst_mode" value="3" {if $inst_mode == 3}checked="checked"{/if}>
                                {l s='in 3 installments' mod={$module_name}}
                            </label>
                            <label for="payplug_installment_mode_4">
                                <input id="payplug_installment_mode_4" type="radio" name="payplug_inst_mode" value="4" {if $inst_mode == 4}checked="checked"{/if}>
                                {l s='in 4 installments' mod={$module_name}}
                            </label>
                        </div>
                    </div>

                    <div class="{$module_name}Installment_fieldset payplugPanel">
                        <div class="{$module_name}Panel_label">{l s='Enable this option from:' mod={$module_name}}</div>
                        <div class="{$module_name}Panel_content">
                            <div class="{$module_name}Installment_amount">
                                <input type="text" name="payplug_inst_min_amount" value="{$inst_min_amount|escape:'htmlall':'UTF-8'}"> €
                                <span style="display: none;" data-e2e-error="installment_amount">{l s='Amount must be greater than 4€ and lower than 20000€.' mod={$module_name}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="{$module_name}Installment_fieldset payplugPanel">
                        <div class="{$module_name}Panel_label">{l s='Receive:' mod={$module_name}}</div>
                        <div class="{$module_name}Panel_content">
                            <p class="{$module_name}Installment_schedule -x2{if $inst_mode == 2} -select{/if}">
                                50% {l s='of order amount on the first day' mod={$module_name}},<br>
                                50% {l s='of order amount after 30 days' mod={$module_name}}.
                            </p>
                            <p class="{$module_name}Installment_schedule -x3{if $inst_mode == 3} -select{/if}">
                                34% {l s='of order amount on the first day' mod={$module_name}},<br>
                                33% {l s='of order amount after 30 days' mod={$module_name}},<br>
                                33% {l s='of order amount after 60 days' mod={$module_name}}.
                            </p>
                            <p class="{$module_name}Installment_schedule -x4{if $inst_mode == 4} -select{/if}">
                                25% {l s='of order amount on the first day' mod={$module_name}},<br>
                                25% {l s='of order amount after 30 days' mod={$module_name}},<br>
                                25% {l s='of order amount after 60 days' mod={$module_name}},<br>
                                25% {l s='of order amount after 90 days' mod={$module_name}}.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
