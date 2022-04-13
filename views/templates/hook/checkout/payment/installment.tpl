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

<div class="row">
	<div class="col-xs-12">
		<p class="payment_module {$module_name|escape:'htmlall':'UTF-8'}Payment">
			{if isset($front_ajax_url)}
				<a data-e2e-type="payment" data-e2e-method="installment" href="{$installment_controller_url|escape:'htmlall':'UTF-8'}" class="{$module_name|escape:'htmlall':'UTF-8'} installment installment_{$installment_mode|escape:'htmlall':'UTF-8'}{if isset($img_lang)} {$img_lang|escape:'htmlall':'UTF-8'}{/if}" title="{l s='Pay by card in' mod='payplug'} {$installment_mode|escape:'htmlall':'UTF-8'} {l s='installments' mod='payplug'}">
					<img class="payment_option_installment_3_logo" src="{$payplug_payment_option.logo_url|escape:'htmlall':'UTF-8'}" alt="{l s='Pay by card in' mod='payplug'} {$installment_mode|escape:'htmlall':'UTF-8'} {l s='installments' mod='payplug'}" />{$payplug_payment_option.label|escape:'htmlall':'UTF-8'}
				</a>
			{else}
				<a data-e2e-type="payment" data-e2e-method="installment" href="{$installment_controller_url|escape:'htmlall':'UTF-8'}" class="{$module_name|escape:'htmlall':'UTF-8'} installment_{$installment_mode|escape:'htmlall':'UTF-8'}{if isset($img_lang)} {$img_lang|escape:'htmlall':'UTF-8'}{/if}" title="{l s='Pay by card in' mod='payplug'} {$installment_mode|escape:'htmlall':'UTF-8'} {l s='installments' mod='payplug'}">
					<img class="payment_option_installment_3_logo" src="{$payplug_payment_option.logo_url|escape:'htmlall':'UTF-8'}" alt="{l s='Pay by card in' mod='payplug'} {$installment_mode|escape:'htmlall':'UTF-8'} {l s='installments' mod='payplug'}" />{$payplug_payment_option.label|escape:'htmlall':'UTF-8'}
				</a>
			{/if}
		</p>
		<p class="{$module_name|escape:'htmlall':'UTF-8'}Payment_error{if isset($method) && $method} -{$method|escape:'htmlall':'UTF-8'}{/if}"></p>
	</div>
</div>
