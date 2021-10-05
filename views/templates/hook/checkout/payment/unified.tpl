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
{assign var=parse_3x_4x value="_"|explode:$payplug_payment_option.logo_url}
<div class="row">
    <div class="col-xs-12">
        <p class="payment_module payplugPayment">
            <a href="{$payplug_payment_option.payment_url|escape:'htmlall':'UTF-8'}" title="{$payplug_payment_option.label|escape:'htmlall':'UTF-8'}">
                <img src="{$payplug_payment_option.logo_url|escape:'htmlall':'UTF-8'}" alt="{$payplug_payment_option.label|escape:'htmlall':'UTF-8'}"
                     class="oneyLogo {if $payplug_payment_option.oney_error}{$payplug_payment_option.oney_error|escape:'htmlall':'UTF-8'}{/if}"/>
                {$payplug_payment_option.label|escape:'htmlall':'UTF-8'}
            </a>
        </p>
        <p class="payplugPayment_error{if isset($method) && $method} -{$method|escape:'htmlall':'UTF-8'}{/if}"></p>
    </div>
</div>
