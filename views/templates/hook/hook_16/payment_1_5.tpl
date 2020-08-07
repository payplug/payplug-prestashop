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
<script type="text/javascript" src="{$api_url|escape:'htmlall':'UTF-8'}/js/1/form.latest.js"></script>
<script type="text/javascript" data-keepinline="true">
	var spinner_url = '{$spinner_url|escape:'htmlall':'UTF-8'}';
</script>
{if isset($payplug_one_click) AND $payplug_one_click == 1}<div class="payplug-wrapper">{/if}
	<p class="payment_module">
		<a href="{$payment_controller_url|escape:'htmlall':'UTF-8'}" class="payplug{if isset($img_lang)} {$img_lang|escape:'htmlall':'UTF-8'}{/if}" title="{l s='Credit card payment' mod='payplug'}">
			<img alt="{l s='Credit card payment' mod='payplug'}" src="{$this_path|escape:'htmlall':'UTF-8'}views/img/logos_schemes_{$img_lang}.png">
			{l s='Credit card checkout' mod='payplug'}
		</a>
	</p>
	{if isset($payplug_one_click) AND $payplug_one_click == 1}
		<form id="form_payplug_payment" action="">
			<input type="hidden" name="front_ajax_url" value="{$front_ajax_url|escape:'htmlall':'UTF-8'}" />
			<input type="hidden" name="id_cart" value="{$cart->id|escape:'htmlall':'UTF-8'}" />
			{if isset($payplug_cards) AND !empty($payplug_cards) AND sizeof($payplug_cards)}
				{foreach from=$payplug_cards item=card name=ppcards}
					{if !$card.expired}
					<div class="card-wrapper">
						<input type="radio" name="payplug_card" id="payplug_card_{$card.id_payplug_card|escape:'htmlall':'UTF-8'}" value="{$card.id_payplug_card|escape:'htmlall':'UTF-8'}" {if $smarty.foreach.ppcards.first}checked="checked" {/if}/>
						<label for="payplug_card_{$card.id_payplug_card|escape:'htmlall':'UTF-8'}">
							<img src="{$this_path|escape:'htmlall':'UTF-8'}views/img/{$card.brand|escape:'htmlall':'UTF-8'|lower}.png"/>
							<div class="info-wrapper">
								<div>**** **** **** {$card.last4|escape:'htmlall':'UTF-8'} ({if $card.brand == 'none'}{l s='Card' mod='payplug'}{else}{$card.brand|escape:'htmlall':'UTF-8'}{/if})</div>
								<div class="payplug_expiry_date">{l s='Expiry date' mod='payplug'} {$card.expiry_date|escape:'htmlall':'UTF-8'}</div>
							</div>
						</label>
						<br />
					</div>
					{/if}
				{/foreach}
			{/if}
			<div class="card-wrapper">
				<input type="radio" name="payplug_card" id="payplug_card_new" value="new_card">
				<label for="payplug_card_new">
					<img src="{$this_path|escape:'htmlall':'UTF-8'}views/img/none.png"/>
					<div class="info-wrapper">
						<div>{l s='Pay with a different card' mod='payplug'}</div>
					</div>
				</label>
				<br />
			</div>

			<div class="ppOneClickStatus">
				<p class="ppwait">{l s='Please wait...' mod='payplug'}<img class="loader" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/admin/spinner.gif" /></p>
				<p class="ppsuccess">
					<img src="{$this_path|escape:'htmlall':'UTF-8'}views/img/admin/icon-success.png" />
					<span class="ppbold">{l s='Payment succeeded!' mod='payplug'}</span><br />
					{l s='You will be redirected to the confirmation page' mod='payplug'}
				</p>
				{if $version <= 1.3}
					<input class="ppsubmit" type="submit" name="SubmitPayplugOneClick" value="{l s='Pay' mod='payplug'} {convertPrice price=$price2display}" />
				{else}
					<input class="ppsubmit" type="submit" name="SubmitPayplugOneClick" value="{l s='Pay' mod='payplug'} {displayPrice price=$price2display}" />
				{/if}
			</div>
		</form>
	{/if}
{if isset($payplug_one_click) AND $payplug_one_click == 1}</div>{/if}
<p class="ppfail ppfail-default"></p>

{* INSTALLMENT *}
{if isset($payplug_installment) && $payplug_installment == 1}
	<p class="payment_module">
		{if isset($front_ajax_url)}
			<a href="{$installment_controller_url|escape:'htmlall':'UTF-8'}" class="payplug installment installment_{$installment_mode|escape:'htmlall':'UTF-8'}{if isset($img_lang)} {$img_lang|escape:'htmlall':'UTF-8'}{/if} call" title="{l s='Pay by card in' mod='payplug'} {$installment_mode|escape:'htmlall':'UTF-8'} {l s='installments' mod='payplug'}">
				<img alt="{l s='Installments' mod='payplug'}" src="{$this_path|escape:'htmlall':'UTF-8'}views/img/logos_schemes_installment_{$installment_mode}_{$img_lang}.png">
				{l s='Pay by card in' mod='payplug'} {$installment_mode|escape:'htmlall':'UTF-8'} {l s='installments' mod='payplug'}
			</a>
		{else}
			<a href="{$installment_controller_url|escape:'htmlall':'UTF-8'}" class="payplug installment_{$installment_mode|escape:'htmlall':'UTF-8'} {if isset($img_lang)} {$img_lang|escape:'htmlall':'UTF-8'}{/if}" title="{l s='Pay by card in' mod='payplug'} {$installment_mode|escape:'htmlall':'UTF-8'} {l s='installments' mod='payplug'}">
				<img alt="{l s='Installments' mod='payplug'}" src="{$this_path|escape:'htmlall':'UTF-8'}views/img/logos_schemes_installment_{$installment_mode}_{$img_lang}.png">
				{l s='Pay by card in' mod='payplug'} {$installment_mode|escape:'htmlall':'UTF-8'} {l s='installments' mod='payplug'}
			</a>
		{/if}
	</p>
	<p class="ppfail ppfail-installment"></p>
{/if}
<form style="display: none;">
	<input type="hidden" name="front_ajax_url" value="{$front_ajax_url|escape:'htmlall':'UTF-8'}" />
	<input type="hidden" name="id_cart" value="{$cart->id|escape:'htmlall':'UTF-8'}" />
</form>
