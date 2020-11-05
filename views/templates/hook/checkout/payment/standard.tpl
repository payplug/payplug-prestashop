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
<div class="row">
	<div class="col-xs-12">
		<p class="payment_module payplugPayment">
			<a href="{$payplug_payment_option.payment_url|escape:'html'}" class="{$payplug_payment_option.extra_classes|escape:'html'}" title="{$payplug_payment_option.label|escape:'html'}" data-e2e-type="payment" data-e2e-method="standard">
				<img class="payment_option_standard_payment_logo" src="{$payplug_payment_option.logo_url|escape:'html'}" alt="{$payplug_payment_option.label|escape:'html'}" />{$payplug_payment_option.label|escape:'html'}
			</a>
		</p>
		<p class="ppfail ppfail-default"></p>
	</div>
</div>
