{*
* 2017 PayPlug
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PayPlug SAS
*  @copyright 2017 PayPlug SAS
*  @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}

<p><span class="ppbold">{l s='Refund your customer on his card directly with Payplug' mod='payplug'}</p>
{*<p><span class="ppbold">{l s='Refund your customer on his card directly with Payplug' d='Modules.Payplug.Admin'}</p>*}
<p>{l s='This transaction was entirely refunded with Payplug' mod='payplug'}</p>
{*<p>{l s='This transaction was entirely refunded with Payplug' d='Modules.Payplug.Admin'}</p>*}
<p class="refunded_amount">{l s='Amount refunded:' mod='payplug'} {$amount_refunded_payplug|escape:'htmlall':'UTF-8'} {$currency->name|escape:'htmlall':'UTF-8'}</p>
{*<p class="refunded_amount">{l s='Amount refunded:' d='Modules.Payplug.Admin'} {$amount_refunded_payplug|escape:'htmlall':'UTF-8'} {$currency->name|escape:'htmlall':'UTF-8'}</p>*}