{*
* 2019 PayPlug
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
*  @copyright 2019 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
{if isset($switch_oney) && $switch_oney}
    <div class="panel-row separate_margin_block">
        {include file=$payplug_module_dir|cat:'payplug/views/templates/admin/switch.tpl' switch=$switch_oney}

        <div class="panel-row">
            <div class="block-right">
                <p class="pptips desc">
                    <span class="ppinline">
                        {l s='Allow customers to spread out payments over 3 or 4 installments from %d € to %d €.' mod='payplug' sprintf=[$oney_min_amounts, $oney_max_amounts]}
                        <a href="{$faq_links['oney']|escape:'htmlall':'UTF-8'}"
                           target="_blank">{l s='Learn more.' mod='payplug'}</a>
                    </span>
                </p>
            </div>
        </div>

        <div class="panel-row panel-oney pponeychecked">
            <label class="left-block">&nbsp;</label>
            <div class="block-right">
                <div class="switch switch-oney-tos{if $payplug_oney_tos} ppon{/if}">
                    <input type="radio" class="switch-input"
                           name="payplug_oney_tos" value="0" id="payplug_oney_tos_off"
                           {if !$payplug_oney_tos}checked="checked"{/if}>
                    <label id="payplug_label_oney_tos_off" for="payplug_oney_tos_off" class="switch-label switch-label-off"></label>
                    <input type="radio" class="switch-input"
                           name="payplug_oney_tos" value="1" id="payplug_oney_tos_on"
                           {if $payplug_oney_tos}checked="checked"{/if}>
                    <label id="payplug_label_oney_tos_on" for="payplug_oney_tos_on" class="switch-label switch-label-on"></label>
                    <span class="switch-selection"></span>
                </div>
                <span>{l s='I integrated the mandatory fields to my terms and conditions on my website.' mod='payplug'}
                    <a href="{$faq_links['oney']|escape:'htmlall':'UTF-8'}"
                       target="_blank">{l s='Learn more.' mod='payplug'}</a>
                </span>
            </div>
        </div>


        <div class="panel-row pponeychecked">
            <div class="block-right">
                <p class="pptips">
                    {if isset($carriers) && !empty($carriers)}
                    <span class="ppinline">
                        {l s='To qualify the payment and avoid fraud, Oney must know your carriers and the average delivery time.' mod='payplug'}<br>
                        {l s='Warning: The Store Pick-up and Network Pick-up shipping are conflicting with the Oney payment method. To use Oney, your customers will need to choose one of the other shipping method.' mod='payplug'}
                    </span>
                </p>
                <table class="pptips table">
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
                <p class="pptips">
                    <span id="oney_config_error"
                          class="hide">{l s='You must enter a number of day in integer.' mod='payplug'}</span>
                </p>
                {else}
                </p>
                {/if}
            </div>
        </div>
        <div class="panel-row panel-oney pponeychecked">
            <label class="left-block"></label>
            <div class="block-right">
                <div class="switch switch-oney-optimized{if $payplug_oney_optimized} ppon{/if}">
                    <input type="radio" class="switch-input"
                           name="payplug_oney_optimized" value="0" id="payplug_oney_optimized_off"
                           {if !$payplug_oney_optimized}checked="checked"{/if}>
                    <label id="payplug_label_oney_optimized_off" for="payplug_oney_optimized_off" class="switch-label switch-label-off"></label>
                    <input type="radio" class="switch-input"
                           name="payplug_oney_optimized" value="1" id="payplug_oney_optimized_on"
                           {if $payplug_oney_optimized}checked="checked"{/if}>
                    <label id="payplug_label_oney_optimized_on" for="payplug_oney_optimized_on" class="switch-label switch-label-on"></label>
                    <span class="switch-selection"></span>
                </div>
                <span class="oney-optimized"><strong>{l s='Go further' mod='payplug'}</strong>
                    {l s='Maximise your customers\' experience with dynamic calculations of the 3 and 4 instalments by switching on to the advanced configuration. Please follow our' mod='payplug'}
                    <a href="{$faq_links['guide']|escape:'htmlall':'UTF-8'}"
                       target="_blank"> {l s='personalisation guide' mod='payplug'} </a>
                    {l s='if you are using other plug-ins or themes. ' mod='payplug'}
                </span>
            </div>
        </div>
    </div>
{/if}
