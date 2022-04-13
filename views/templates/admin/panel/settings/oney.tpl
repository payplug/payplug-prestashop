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
<div class="panel-row separate_margin_block">
    <div class="{$module_name|escape:'htmlall':'UTF-8'}Panel">
        <div class="{$module_name|escape:'htmlall':'UTF-8'}Panel_label">{l s='admin.panel.setting.oney.label' mod='payplug'}</div>
        <div class="{$module_name|escape:'htmlall':'UTF-8'}Panel_content">{include file='./switch.tpl' switch=$payplug_switch.oney}</div>
    </div>
    <div class="{$module_name|escape:'htmlall':'UTF-8'}Panel">
        <div class="{$module_name|escape:'htmlall':'UTF-8'}Panel_content">
            <p>
                {l s='Allow customers to spread out payments over 3 or 4 installments from %d € to %d €.'  sprintf=[$oney_min_amounts, $oney_max_amounts] mod='payplug'}
                <a class="{$module_name|escape:'htmlall':'UTF-8'}Link" href="{$faq_links.oney|escape:'htmlall':'UTF-8'}" data-e2e-link="faq" target="_blank">{l s='Learn more.' mod='payplug'}</a>
            </p>
            <div class="{$module_name|escape:'htmlall':'UTF-8'}Tips -{$payplug_switch.oney.name|escape:'htmlall':'UTF-8'}">
                <div class="{$module_name|escape:'htmlall':'UTF-8'}Tips_item -left {if !$payplug_switch.oney.checked || !$payplug_switch.oney.active} -hide{/if}">
                    <div class="{$module_name|escape:'htmlall':'UTF-8'}Oney">
                        {include file='./oney_fees.tpl'}
                        <div class="{$module_name|escape:'htmlall':'UTF-8'}OneyOptimized">
                            {include file='./switch.tpl' switch=$payplug_switch.oney_optimized}
                            <p>
                                <strong>{l s='Go further' mod='payplug'}</strong>
                                {l s='Maximise your customers\' experience with dynamic calculations of the 3 and 4 instalments by switching on to the advanced configuration.' mod='payplug'}
                                <a class="{$module_name|escape:'htmlall':'UTF-8'}Link" href="{$faq_links.oney|escape:'htmlall':'UTF-8'}#h_2595dd3d-a281-43ab-a51a-4986fecde5ee" data-e2e-link="faq" target="_blank">{l s='Learn more.' mod='payplug'}</a>
                            </p>

                        </div>

                    </div>
                    <div class="flex-container">
                        <div class="{$module_name|escape:'htmlall':'UTF-8'}Oney_thresholds">
                            <label for="text">{l s='admin.panel.settings.oney.thresholds' mod='payplug'}</label>
                        </div>
                        <div class="{$module_name|escape:'htmlall':'UTF-8'}Oney_thresholdsInputs">
                            <input type="text" id= "oney_min" name="payplug_oney_custom_min_amounts" value="{$oney_custom_min_amounts|escape:'htmlall':'UTF-8'}">
                            <label>{l s='admin.panel.settings.oney.thresholds.and'  mod='payplug'}</label>
                            <input type="text" id= "oney_max" name="payplug_oney_custom_max_amounts" value="{$oney_custom_max_amounts|escape:'htmlall':'UTF-8'}">
                            <label for="text">€.</label>
                            <div class="{$module_name|escape:'htmlall':'UTF-8'}Oney_statement"><span data-e2e-error="oney_amount"></span></div>
                        </div>


                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>