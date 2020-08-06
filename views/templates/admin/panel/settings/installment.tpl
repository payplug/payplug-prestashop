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
<div class="payplugInstallment panel-row separate_margin_block">
    <div class="payplugPanel">
        <div class="payplugPanel_label">{l s='Enable payments by installments' mod='payplug'}</div>
        <div class="payplugPanel_content">{include file='./switch.tpl' switch=$payplug_switch.installment}</div>
    </div>
    <div class="payplugPanel">
        <div class="payplugPanel_content">
            <p>
                {l s='Allow customers to spread out payments over 2, 3 or 4 installments.' mod='payplug'}
                <a class="payplugLink" href="{$faq_links.installments|escape:'htmlall':'UTF-8'}" target="_blank">{l s='Learn more.' mod='payplug'}</a>
            </p>

            <div class="payplugTips payplugTips-{$payplug_switch.installment.name|escape:'htmlall':'UTF-8'}">
                <div class="payplugTips_item payplugTips_item-left"{if !$payplug_switch.installment.checked}style="display: none;"{/if}>
                    <p class="payplugAlert payplugAlert-warning"><span>{l s='Payments by installment are not guaranteed. A default of payment may occur for the upcoming installments.' mod='payplug'}</span></p>
                    <p>
                        {l s='You can consult all your past and pending installment payments in' mod='payplug'}
                        <a class="payplugLink" href="{$installments_panel_url|escape:'htmlall':'UTF-8'}"> {l s='a dedicated menu' mod='payplug'}</a>
                        {l s='made accessible from the navigation bar, and in the details of each order within the' mod='payplug'}
                        <i> {l s='Payment with PayPlug' mod='payplug'}</i> {l s='bloc.' mod='payplug'}
                    </p>
                    <p>
                        {l s='Allow customers to spread out payments over 2, 3 or 4 installments.' mod='payplug'}
                        <a class="payplugLink" href="{$faq_links.installments|escape:'htmlall':'UTF-8'}" target="_blank">{l s='Learn more.' mod='payplug'}</a>
                    </p>

                    <div class="payplugInstallment_fieldset payplugPanel">
                        <div class="payplugPanel_label">{l s='Enable payments:' mod='payplug'}</div>
                        <div class="payplugPanel_content">
                            <label for="payplug_installment_mode_2">
                                <input id="payplug_installment_mode_2" type="radio" name="PAYPLUG_INST_MODE" value="2" {if $PAYPLUG_INST_MODE == 2}checked="checked"{/if}>
                                {l s='in 2 installments' mod='payplug'}
                            </label>
                            <label for="payplug_installment_mode_3">
                                <input id="payplug_installment_mode_3" type="radio" name="PAYPLUG_INST_MODE" value="3" {if $PAYPLUG_INST_MODE == 3}checked="checked"{/if}>
                                {l s='in 3 installments' mod='payplug'}
                            </label>
                            <label for="payplug_installment_mode_4">
                                <input id="payplug_installment_mode_4" type="radio" name="PAYPLUG_INST_MODE" value="4" {if $PAYPLUG_INST_MODE == 4}checked="checked"{/if}>
                                {l s='in 4 installments' mod='payplug'}
                            </label>
                        </div>
                    </div>

                    <div class="payplugInstallment_fieldset payplugPanel">
                        <div class="payplugPanel_label">{l s='Enable this option from:' mod='payplug'}</div>
                        <div class="payplugPanel_content">
                            <div class="payplugInstallment_amount">
                                <input type="text" name="PAYPLUG_INST_MIN_AMOUNT" value="{$PAYPLUG_INST_MIN_AMOUNT|escape:'htmlall':'UTF-8'}"> €
                                <span style="display: none;" data-e2e-error="installment_amount">{l s='Amount must be greater than 4€ and lower than 20000€.' mod='payplug'}</span>
                            </div>
                        </div>
                    </div>

                    <div class="payplugInstallment_fieldset payplugPanel">
                        <div class="payplugPanel_label">{l s='Receive:' mod='payplug'}</div>
                        <div class="payplugPanel_content">
                            <p class="payplugInstallment_schedule payplugInstallment_schedule-x2{if $PAYPLUG_INST_MODE == 2} payplugInstallment_schedule-select{/if}">
                                50% {l s='of order amount on the first day' mod='payplug'},<br>
                                50% {l s='of order amount after 30 days' mod='payplug'}.
                            </p>
                            <p class="payplugInstallment_schedule payplugInstallment_schedule-x3{if $PAYPLUG_INST_MODE == 3} payplugInstallment_schedule-select{/if}">
                                34% {l s='of order amount on the first day' mod='payplug'},<br>
                                33% {l s='of order amount after 30 days' mod='payplug'},<br>
                                33% {l s='of order amount after 60 days' mod='payplug'}.
                            </p>
                            <p class="payplugInstallment_schedule payplugInstallment_schedule-x4{if $PAYPLUG_INST_MODE == 4} payplugInstallment_schedule-select{/if}">
                                25% {l s='of order amount on the first day' mod='payplug'},<br>
                                25% {l s='of order amount after 30 days' mod='payplug'},<br>
                                25% {l s='of order amount after 60 days' mod='payplug'},<br>
                                25% {l s='of order amount after 90 days' mod='payplug'}.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
