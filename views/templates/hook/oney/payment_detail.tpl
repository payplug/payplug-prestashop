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
{if $oney_payment_option}
    <ul>
        <li>
            <span><b>{l s='Amount:' mod='payplug'}</b></span>
            <span><b>{$payplug_oney_amount.value}</b></span>
        </li>
        <li>
            <span>{l s='Contribution: ' mod='payplug'}</span>
            <span><b>{$oney_payment_option.down_payment_amount.value}</b></span>
            <small>
                ({l s='Financing cost:' mod='payplug'} <b>{$oney_payment_option.total_cost.value}</b>
                {l s='TAEG:' mod='payplug'} <b>{$oney_payment_option.effective_annual_percentage_rate|escape:'htmlall':'UTF-8'}%</b>)
            </small>
        </li>
        {foreach $oney_payment_option.installments as $oney_inst_number => $oney_installment}
            {assign var="inst_number" value=$oney_inst_number+1}
            <li>
                <span>{l s='Installment no%d:' mod='payplug' sprintf=[$inst_number]}</span>
                <span><b>{$oney_installment.value}</b></span>
            </li>
        {/foreach}
        <li>
            <span><b>{l s='Total:' mod='payplug'}</b></span>
            <span><b>{$oney_payment_option.total_amount.value}</b></span>
        </li>
    </ul>
{/if}
