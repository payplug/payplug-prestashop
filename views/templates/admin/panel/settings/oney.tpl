{*
* 2021 PayPlug
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
*  @copyright 2021 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
<div class="panel-row separate_margin_block">
    <div class="payplugPanel">
        <div class="payplugPanel_label">{l s='admin.panel.setting.oney.label' mod='payplug'}</div>
        <div class="payplugPanel_content">{include file='./switch.tpl' switch=$payplug_switch.oney}</div>
    </div>
    <div class="payplugPanel">
        <div class="payplugPanel_content">
            <p>
                {l s='Allow customers to spread out payments over 3 or 4 installments from %d € to %d €.' mod='payplug' sprintf=[$oney_min_amounts, $oney_max_amounts]}
                <a class="payplugLink" href="{$faq_links.oney|escape:'htmlall':'UTF-8'}" data-e2e-link="faq" target="_blank">{l s='Learn more.' mod='payplug'}</a>
            </p>
            <div class="payplugTips -{$payplug_switch.oney.name|escape:'htmlall':'UTF-8'}">
                <div class="payplugTips_item -left {if !$payplug_switch.oney.checked || !$payplug_switch.oney.active} -hide{/if}">
                    <div class="payplugOney">
                        <div class="payplugOneyFees">
                            <label class="payplugOneyFees_option">
                                <span class="payplugOneyFees_title">
                                    {l s='admin.panel.setting.oney.withFees' mod='payplug'}
                                </span>
                                <span class="payplugOneyFees_content">
                                    {l s='admin.panel.setting.oney.withFeesText' mod='payplug'}
                                </span>
                                <span class="payplugOneyFees_state">
                                    {l s='admin.panel.setting.oney.state' mod='payplug'}
                                </span>
                                <input type="radio" id="{$payplug_switch.oney_fees.name}_left" name="{$payplug_switch.oney_fees.name}" value="1"{if $payplug_switch.oney_fees.checked} checked="checked"{/if}/>
                            </label>
                            <label class="payplugOneyFees_option">
                                <span class="payplugOneyFees_title">
                                    {l s='admin.panel.setting.oney.withoutFees' mod='payplug'}
                                </span>
                                <span class="payplugOneyFees_content">
                                    {l s='admin.panel.setting.oney.withoutFeesText' mod='payplug'}
                                </span>
                                <span class="payplugOneyFees_state">
                                    {l s='admin.panel.setting.oney.state' mod='payplug'}
                                </span>
                                <input type="radio" id="{$payplug_switch.oney_fees.name}_right" name="{$payplug_switch.oney_fees.name}" value="0"{if !$payplug_switch.oney_fees.checked} checked="checked"{/if}/>
                            </label>
                        </div>
                        <div class="payplugPanel_section">
                            {include file='./switch.tpl' switch=$payplug_switch.oney_tos}
                            <p>{l s='I integrated the mandatory fields to my terms and conditions on my website.' mod='payplug'}
                                <a class="payplugLink" href="{$faq_links.oney|escape:'htmlall':'UTF-8'}#h_f9ffbbdb-e5f2-487f-a709-854eb852e480" data-e2e-link="faq" target="_blank">{l s='Learn more.' mod='payplug'}</a>
                            </p>
                        </div>
                        <div class="payplugPanel_section -nowrap">
                            {include file='./switch.tpl' switch=$payplug_switch.oney_optimized}
                            <p>
                                <strong>{l s='Go further' mod='payplug'}</strong>
                                {l s='Maximise your customers\' experience with dynamic calculations of the 3 and 4 instalments by switching on to the advanced configuration.' mod='payplug'}
                                <a class="payplugLink" href="{$faq_links.oney|escape:'htmlall':'UTF-8'}#h_2595dd3d-a281-43ab-a51a-4986fecde5ee" data-e2e-link="faq" target="_blank">{l s='Learn more.' mod='payplug'}</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
