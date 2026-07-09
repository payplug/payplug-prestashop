{*
* 2026 Payplug
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
*  @copyright 2026 Payplug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of Payplug SAS
*}
<div id="{$oney_checkout_placeholder|escape:'htmlall':'UTF-8'}"
     class="{$module_name|escape:'htmlall':'UTF-8'}OneyCheckout"
     data-e2e-oney="checkout"
     data-business-transaction-code="{$oney_checkout_business_transaction_code|escape:'htmlall':'UTF-8'}"
     data-payment-amount="{$oney_checkout_amount|escape:'htmlall':'UTF-8'}"
     data-country="{$oney_checkout_country|escape:'htmlall':'UTF-8'}"
     data-merchant-guid="{$oney_checkout_merchant_guid|escape:'htmlall':'UTF-8'}">
</div>
