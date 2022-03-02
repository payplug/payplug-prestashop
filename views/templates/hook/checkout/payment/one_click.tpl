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
{if isset($payplug_cards) && $payplug_cards}
    <div class="row">
        <div class="col-xs-12">
            <div class="payment_module {$module_name}Payment {$module_name}OneClick">
                <button>
                    <img src="{$payplug_payment_option.logo_url|escape:'htmlall':'UTF-8'|replace:'none.png':'logos_schemes_default.png'}"
                         alt="{$payplug_payment_option.label|escape:'htmlall':'UTF-8'}"/>
                    {l s='Credit card checkout' mod={$module_name}}
                </button>
                <form action="">
                    <input type="hidden" name="front_ajax_url" value="{$front_ajax_url|escape:'htmlall':'UTF-8'}"/>
                    <input type="hidden" name="id_cart" value="{$cart->id|escape:'htmlall':'UTF-8'}"/>
                    {foreach from=$payplug_cards item=card name=ppcards}
                        {if !$card.expired}
                            <label>
                                <input data-e2e-type="payment" data-e2e-method="oneclick" type="radio" name="payplug_card" id="payplug_card_{$card.id_payplug_card|escape:'htmlall':'UTF-8'}" value="{$card.id_payplug_card|escape:'htmlall':'UTF-8'}" {if $smarty.foreach.ppcards.first}checked="checked" {/if}/>
                                {if $card.brand != 'none'}
                                <img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/{$card.brand|escape:'htmlall':'UTF-8'|lower}.svg"/>
                                <span>
                                    {$payplug_payment_option.label|escape:'htmlall':'UTF-8'}
                                    <span>{l s='Expiry date' mod={$module_name}} {$card.expiry_date|escape:'htmlall':'UTF-8'}</span>
                                </span>
                                    {else}
                                <span class="noimg">
                                    {$payplug_payment_option.label|escape:'htmlall':'UTF-8'}
                                    <span>{l s='Expiry date' mod={$module_name}} {$card.expiry_date|escape:'htmlall':'UTF-8'}</span>
                                </span>
                                {/if}

                            </label>
                        {/if}
                    {/foreach}
                    <label class="oneClickPayment_card">
                        <input type="radio" name="payplug_card" id="payplug_card_new" value="new_card" />
                        <span class="noimg">  {l s='Pay with a different card' mod={$module_name}}</span>
                    </label>

                    <div class="{$module_name}OneClick_submit">
                        <p class="{$module_name}OneClick_message">
                            {l s='Please wait...' mod={$module_name}}
                            <img class="{$module_name}OneClick_loader" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/admin/spinner.gif"/>
                        </p>
                        <button class="{$module_name}Button -green -payment" type="submit" name="SubmitPayplugOneClick">{l s='Pay' mod={$module_name}} {displayPrice price=$price2display}</button>
                    </div>
                </form>
            </div>
            <p class="{$module_name}Payment_error{if isset($method) && $method} -{$method|escape:'htmlall':'UTF-8'}{/if}"></p>
        </div>
    </div>
{/if}
