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

<ul>
    <li>{l s='Amount already refunded with Payplug : ' mod='payplug'}<span id="amount_refunded_payplug">
    {*<li>{l s='Amount already refunded with Payplug : ' d='Modules.Payplug.Admin'}<span id="amount_refunded_payplug">*}
            {displayPrice price=$amount_refunded_payplug}</span></li>
    <li>{l s='Amount still refundable with Payplug : ' mod='payplug'}<span id="amount_available">
    {*<li>{l s='Amount still refundable with Payplug : ' d='Modules.Payplug.Admin'}<span id="amount_available">*}
            {displayPrice price=$amount_available}</span></li>
</ul>
