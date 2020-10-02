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
<div class="panel-row separate_margin_block">
    <div class="payplugPanel">
        <div class="payplugPanel_label">{l s='Activate Payments Oney' mod='payplug'}</div>
        <div class="payplugPanel_content">{include file='./switch.tpl' switch=$payplug_switch.oney}</div>
    </div>
    <div class="payplugPanel">
        <div class="payplugPanel_content">
            <p>
                {l s='Allow customers to spread out payments over 3 or 4 installments from %d € to %d €.' mod='payplug' sprintf=[$oney_min_amounts, $oney_max_amounts]}
                <a class="payplugLink" href="{$faq_links.oney|escape:'htmlall':'UTF-8'}" target="_blank">{l s='Learn more.' mod='payplug'}</a>
            </p>
            <div class="payplugTips payplugTips-{$payplug_switch.oney.name|escape:'htmlall':'UTF-8'}">
                <div class="payplugTips_item payplugTips_item-left" {if !$payplug_switch.oney.checked || !$payplug_switch.oney.active}style="display: none;"{/if}>
                    <div class="payplugOney">
                        <div class="payplugPanel_section payplugOneyTOS">
                            {include file='./switch.tpl' switch=$payplug_switch.oney_tos}
                            <p>{l s='I integrated the mandatory fields to my terms and conditions on my website.' mod='payplug'}
                                <a class="payplugLink" href="{$faq_links.oney|escape:'htmlall':'UTF-8'}#h_f9ffbbdb-e5f2-487f-a709-854eb852e480" target="_blank">{l s='Learn more.' mod='payplug'}</a>
                            </p>
                            <div class="payplugTips payplugTips-{$payplug_switch.oney_tos.name|escape:'htmlall':'UTF-8'}">
                                <div class="payplugTips_item payplugTips_item-left" {if !$payplug_switch.oney_tos.checked || !$payplug_switch.oney_tos.active}style="display: none;"{/if}>
                                    <div class="payplugOneyTOS" >
                                        <p class="payplugOneyTOS_text">{l s='Please enter your General terms and conditions URL here: ' mod='payplug'}</p>
                                        <input  type="text"
                                                class="payplugOneyTOS_input"
                                                name="payplug_oney_tos_url"
                                                placeholder="ex : http://monsite.fr/mes-cgv"
                                                {if $PAYPLUG_ONEY_TOS_URL}
                                                    value="{$PAYPLUG_ONEY_TOS_URL}"
                                                {/if}
                                        >
                                        <div class="payplugOneyTOS_error">{l s='Error : Please enter a valid URL.' mod='payplug'}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {if isset($carriers) && !empty($carriers)}
                            <div class="payplugPanel_section">
                                <p>
                                    {l s='To qualify the payment and avoid fraud, Oney must know your carriers and the average delivery time.' mod='payplug'}<br>
                                </p>
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th><span class="title_box">{l s='Carrier' mod='payplug'}</span>
                                        </td>
                                        <th><span class="title_box">{l s='Delivery type' mod='payplug'}</span></th>
                                        <th><span class="title_box">{l s='Expected delivery time' mod='payplug'}</span></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    {foreach from=$carriers item=carrier}
                                        <tr>
                                            <td>{$carrier->name|escape:'htmlall':'UTF-8'}</td>
                                            <td>
                                                <select name="payplug_carrier_{$carrier->id|escape:'htmlall':'UTF-8'}_delivery_type">
                                                    <option value="">{l s='Select a delivery type' mod='payplug'}</option>
                                                    <option value="storepickup"{if $carrier->delivery_type == 'storepickup'} selected{/if}>{l s='Store Pick-up' mod='payplug'}</option>
                                                    <option value="networkpickup"{if $carrier->delivery_type == 'networkpickup'} selected{/if}>{l s='Network Pick-up' mod='payplug'}</option>
                                                    <option value="carrier"{if $carrier->delivery_type == 'carrier'} selected{/if}>{l s='Carrier' mod='payplug'}</option>
                                                    <option value="edelivery"{if $carrier->delivery_type == 'edelivery'} selected{/if}>{l s='eDelivery' mod='payplug'}</option>
                                                </select>
                                            </td>
                                            <td class="payplug_carrier_delay">
                                                <input type="number" min="0" step="1" pattern="\d+"
                                                       name="payplug_carrier_{$carrier->id|escape:'htmlall':'UTF-8'}_delay"
                                                       value="{$carrier->delay|escape:'htmlall':'UTF-8'}"/>
                                                {l s='days' mod='payplug'}
                                            </td>
                                        </tr>
                                    {/foreach}
                                    </tbody>
                                </table>
                                <div class="payplugOney_error" data-e2e-error="oney_delay">{l s='You must enter a number of day in integer.' mod='payplug'}</div>
                            </div>
                        {/if}
                        <div class="payplugPanel_section payplugPanel_section-nowrap">
                            {include file='./switch.tpl' switch=$payplug_switch.oney_optimized}
                            <p>
                                <strong>{l s='Go further' mod='payplug'}</strong>
                                {l s='Maximise your customers\' experience with dynamic calculations of the 3 and 4 instalments by switching on to the advanced configuration.' mod='payplug'}
                                <a class="payplugLink" href="{$faq_links.oney|escape:'htmlall':'UTF-8'}#h_2595dd3d-a281-43ab-a51a-4986fecde5ee" target="_blank">{l s='Learn more.' mod='payplug'}</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
