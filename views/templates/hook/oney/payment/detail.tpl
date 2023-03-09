{*
* 2023 Payplug
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
*  @author Payplug SAS
*  @copyright 2023 Payplug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of Payplug SAS
*}
{if $oney_payment_option}
    <ul>
        <li>
            <span><b>{l s='hook.oney.popin.payment.detail.amount' mod='payplug'}</b></span>
            <span><b>{$payplug_oney_amount.value|escape:'htmlall':'UTF-8'}</b></span>
        </li>
        <li>
            <span>{l s='hook.oney.popin.payment.detail.contribution' mod='payplug'}</span>
            <span><b>{$oney_payment_option.down_payment_amount.value|escape:'htmlall':'UTF-8'}</b></span>
            {if isset($use_fees) && $use_fees}
                <small>
                    ({l s='hook.oney.popin.payment.detail.cost' mod='payplug'}
                    <b>{$oney_payment_option.total_cost.value|escape:'htmlall':'UTF-8'}</b>
                    {l s='hook.oney.popin.payment.detail.taeg' mod='payplug'}
                    <b>{$oney_payment_option.effective_annual_percentage_rate|escape:'htmlall':'UTF-8'}%</b>)
                </small>
            {/if}
        </li>
        {foreach $oney_payment_option.installments as $oney_inst_number => $oney_installment}
            <li>
                <span>
                    {if $oney_inst_number == 0}
                        {l s='hook.oney.payment.detail.installmentFirst' mod='payplug'}
                    {elseif $oney_inst_number == 1}
                        {l s='hook.oney.payment.detail.installmentSecond' mod='payplug'}
                    {elseif $oney_inst_number == 2}
                        {l s='hook.oney.payment.detail.installmentThird' mod='payplug'}
                    {/if}
                </span>
                <span>{$oney_installment.value|escape:'htmlall':'UTF-8'}</span>
            </li>
        {/foreach}
        {if isset($use_fees) && $use_fees}
            <li>
                <span><b>{l s='hook.oney.popin.payment.detail.total' mod='payplug'}</b></span>
                <span><b>{$oney_payment_option.total_amount.value|escape:'htmlall':'UTF-8'}</b></span>
            </li>
        {else}
            <li>
                <span>
                    {l s='hook.oney.popin.payment.detail.cost' mod='payplug'}
                    {$oney_payment_option.total_cost.value|escape:'htmlall':'UTF-8'}
                    {l s='hook.oney.popin.payment.detail.taeg' mod='payplug'}
                    {$oney_payment_option.effective_annual_percentage_rate|escape:'htmlall':'UTF-8'}%
                </span>
            </li>
        {/if}
    </ul>
{/if}
