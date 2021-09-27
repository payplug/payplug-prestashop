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
{if isset($oney_payment_options) && $oney_payment_options}
    {foreach $oney_payment_options as $oney_type=>$oney_payment_option}
        <label class="oneyOption -{$oney_type|escape:'htmlall':'UTF-8'}{if !isset($oney_payment_option.installments) || !$oney_payment_option.installments} -withoutSchedule{/if}">
            <div class="oneyOption_title">
                <span class="oneyOption_logo oneyLogo -x{$oney_payment_option.split|escape:'htmlall':'UTF-8'}{if isset($use_fees) && !$use_fees} -withoutFees{/if} {if isset($merchant_company_iso) && $merchant_company_iso == 'IT' } -isItalian{/if}"></span>
                   {$oney_payment_option.title|escape:'htmlall':'UTF-8'}
            </div>
            {if isset($oney_payment_option.installments) && $oney_payment_option.installments}
                <div class="oneyOption_prices">
                    {include file="./detail.tpl" oney_payment_option=$oney_payment_option}
                </div>
            {/if}
            <div class="oneyOption_radio"><input data-e2e-type="payment" data-e2e-method="{$oney_type|escape:'htmlall':'UTF-8'}" type="radio" name="oney_type" value="{$oney_type|escape:'htmlall':'UTF-8'}"></div>
        </label>
    {/foreach}
{else}
    <span class="oneyError">
        {l s='hook.oney.payment.options.unavailable' mod='payplug'}
    </span>
{/if}
