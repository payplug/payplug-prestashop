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
{if isset($payplug_cards) && $payplug_cards}
    <div class="row">
        <div class="col-xs-12">
            <div class="payplug-wrapper">
                <p class="payment_module">
                    <a href="{$payment_controller_url|escape:'htmlall':'UTF-8'}"
                       class="payplug{if isset($img_lang)} {$img_lang|escape:'htmlall':'UTF-8'}{/if}"
                       title="{l s='Credit card payment' mod='payplug'}">
                        <img class="payment_option_standard_payment_logo"
                             src="{$payplug_payment_option.logo_url|escape:'html'|replace:'none.png':'logos_schemes_default.png'}"
                             alt="{$payplug_payment_option.label|escape:'html'}"/>
                        {l s='Credit card checkout' mod='payplug'}
                    </a>
                </p>
                <form id="form_payplug_payment" action="">
                    <input type="hidden" name="front_ajax_url" value="{$front_ajax_url|escape:'htmlall':'UTF-8'}"/>
                    <input type="hidden" name="id_cart" value="{$cart->id|escape:'htmlall':'UTF-8'}"/>
                    {foreach from=$payplug_cards item=card name=ppcards}
                        {if !$card.expired}
                            <div class="card-wrapper">
                                <input type="radio" name="payplug_card"
                                       id="payplug_card_{$card.id_payplug_card|escape:'htmlall':'UTF-8'}"
                                       value="{$card.id_payplug_card|escape:'htmlall':'UTF-8'}"
                                       {if $smarty.foreach.ppcards.first}checked="checked" {/if}/>
                                <label for="payplug_card_{$card.id_payplug_card|escape:'htmlall':'UTF-8'}">
                                    <img src="{$this_path|escape:'htmlall':'UTF-8'}views/img/{$card.brand|escape:'htmlall':'UTF-8'|lower}.png"/>
                                    <div class="info-wrapper">
                                        <div>
                                            {$payplug_payment_option.label|escape:'html'}
                                        </div>
                                        <div class="payplug_expiry_date">{l s='Expiry date' mod='payplug'} {$card.expiry_date|escape:'htmlall':'UTF-8'}</div>
                                    </div>
                                </label>
                                <br/>
                            </div>
                        {/if}
                    {/foreach}
                    <div class="card-wrapper">
                        <input type="radio" name="payplug_card" id="payplug_card_new" value="new_card">
                        <label for="payplug_card_new">
                            <img src="{$this_path|escape:'htmlall':'UTF-8'}views/img/none.png"/>
                            <div class="info-wrapper">
                                <div>{l s='Pay with a different card' mod='payplug'}</div>
                            </div>
                        </label>
                    </div>
                    <div class="ppOneClickStatus">
                        <p class="ppwait">{l s='Please wait...' mod='payplug'}<img class="loader"
                                                                                   src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/admin/spinner.gif"/>
                        </p>
                        <p class="ppsuccess">
                            <img src="{$this_path|escape:'htmlall':'UTF-8'}views/img/admin/icon-success.png"/>
                            <span class="ppbold">{l s='Payment succeeded!' mod='payplug'}</span><br/>
                            {l s='You will be redirected to the confirmation page' mod='payplug'}
                        </p>
                        <input class="ppsubmit" type="submit" name="SubmitPayplugOneClick"
                               value="{l s='Pay' mod='payplug'} {displayPrice price=$price2display}"/>
                    </div>
                </form>
            </div>
            <p class="ppfail ppfail-default"></p>
        </div>
    </div>
{/if}
