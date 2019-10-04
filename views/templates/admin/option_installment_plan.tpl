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
<div class="panel-row separate_margin_block">
    {include file='./switch.tpl' switch=$switch_installment_plan}
    <div class="panel-row">
        <div class="block-right">
            <p class="ppinstallmentchecked" id="installment_config_warning">{l s='Payments by installment are not guaranteed. A default of payment may occur for the upcoming installments.' mod='payplug'}</p>
            <p class="pptips">
                    <span class="ppinstallmentchecked">{l s='You can consult all your past and pending installment payments in' mod='payplug'}
                        <a href="{$installments_panel_url|escape:'htmlall':'UTF-8'}"> {l s='a dedicated menu' mod='payplug'} </a>
                        {l s='made accessible from the navigation bar, and in the details of each order within the' mod='payplug'}
                        <span class="italic"> {l s='Payment with PayPlug' mod='payplug'} </span>
                        {l s='bloc.' mod='payplug'}
                    </span>

                <span class="ppinline">
                        {l s='Allow customers to spread out payments over 2, 3 or 4 installments.' mod='payplug'}
                        <a href="http://support.payplug.com/customer/{$iso|escape:'htmlall':'UTF-8'}/portal/articles/2966107" target="_blank">{l s='Learn more.' mod='payplug'}</a>
                    </span>

                <span class="ppinstallmentchecked ppinline">
                        {l s='Enable payments' mod='payplug'}
                        <input type="radio" name="PAYPLUG_INST_MODE" value="2" id="payplug_installment_mode_2" {if $PAYPLUG_INST_MODE == 2}checked="checked"{/if}>
                        <label for="payplug_installment_mode_2">{l s='in 2 installments' mod='payplug'}</label>
                        <input type="radio" name="PAYPLUG_INST_MODE" value="3" id="payplug_installment_mode_3" {if $PAYPLUG_INST_MODE == 3}checked="checked"{/if}>
                        <label for="payplug_installment_mode_3">{l s='in 3 installments' mod='payplug'}</label>
                        <input type="radio" name="PAYPLUG_INST_MODE" value="4" id="payplug_installment_mode_4" {if $PAYPLUG_INST_MODE == 4}checked="checked"{/if}>
                        <label for="payplug_installment_mode_4">{l s='in 4 installments' mod='payplug'}</label>
                    </span>

                <span class="ppinstallmentchecked ppinline">
                        <label for="payplug_installment_min_amount">{l s='Enable this option from' mod='payplug'}</label>
                        <input class="ppminamount" type="text" name="PAYPLUG_INST_MIN_AMOUNT" value="{$PAYPLUG_INST_MIN_AMOUNT|escape:'htmlall':'UTF-8'}" id="payplug_installment_min_amount"> €.
                        <span id="installment_config_error" class="hide">{l s='Amount must be greater than 4€ and lower than 20000€.' mod='payplug'}</span>
                    </span>
            </p>
            <table class="pptips ppinstallmentchecked">
                <tbody>
                <tr>
                    <td>{l s='Receive' mod='payplug'} :&nbsp;</td>
                    <td class="ppinstallments pp2installments">50% {l s='of order amount on the first day' mod='payplug'},</td>
                    <td class="ppinstallments pp3installments">34% {l s='of order amount on the first day' mod='payplug'},</td>
                    <td class="ppinstallments pp4installments">25% {l s='of order amount on the first day' mod='payplug'},</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="ppinstallments pp2installments">50% {l s='of order amount after 30 days' mod='payplug'}.</td>
                    <td class="ppinstallments pp3installments">33% {l s='of order amount after 30 days' mod='payplug'},</td>
                    <td class="ppinstallments pp4installments">25% {l s='of order amount after 30 days' mod='payplug'},</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="ppinstallments pp3installments">33% {l s='of order amount after 60 days' mod='payplug'}.</td>
                    <td class="ppinstallments pp4installments">25% {l s='of order amount after 60 days' mod='payplug'},</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="ppinstallments pp4installments">25% {l s='of order amount after 90 days' mod='payplug'}.</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
