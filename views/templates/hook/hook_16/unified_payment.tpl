{*
* 2020 PayPlug
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
*  @copyright 2020 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
{assign var=parse_3x_4x value="_"|explode:$payplug_payment_option.logo_url}
{*{$parse_3x_4x[0]|substr:-1}*}
<div class="row">
    <div class="col-xs-12">
        <p class="payment_module">
            <a href="{$payplug_payment_option.payment_url|escape:'html'}" title="{$payplug_payment_option.label|escape:'html'}">
                <img class="payment_option_oney_3_4" src="{$payplug_payment_option.logo_url|escape:'html'}" alt="{$payplug_payment_option.label|escape:'html'}"/>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                {$payplug_payment_option.label|escape:'html'}
            </a>
        </p>
    </div>
</div>
