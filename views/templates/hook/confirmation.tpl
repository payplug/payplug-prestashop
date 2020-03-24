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

<p><strong>
{if $state == 'pending'}
    {l s='Your payment is pending, it should validated by Payplug in a few seconds.' mod='payplug'}<br>
    {*{l s='Your payment is pending, it should validated by Payplug in a few seconds.' d='Modules.Payplug.Shop'}<br>*}
    {l s='An email will be sent to your email address to confirm payment.' mod='payplug'}
    {*{l s='An email will be sent to your email address to confirm payment.' d='Modules.Payplug.Shop'}*}
{elseif $state == 'paid'}
    {l s='Your payment has been validated !' mod='payplug'}<br>
    {*{l s='Your payment has been validated !' d='Modules.Payplug.Shop'}<br>*}
    {l s='An email has been sent to your email address to confirm payment.' mod='payplug'}
    {*{l s='An email has been sent to your email address to confirm payment.' d='Modules.Payplug.Shop'}*}
{/if}

</strong></p>
<p>
{l s='Order summary :' mod='payplug'}<br>
{*{l s='Order summary :' d='Modules.Payplug.Shop'}<br>*}
<ul>
{if isset($reference)}
    <li>{l s='Reference : ' mod='payplug'}<span id="pp_ref">{$reference|escape:'htmlall':'UTF-8'}</span></li>
    {*<li>{l s='Reference : ' d='Modules.Payplug.Shop'}<span id="pp_ref">{$reference|escape:'htmlall':'UTF-8'}</span></li>*}
{/if}
    <li>{l s='Total amount : ' mod='payplug'}<span id="pp_amount">{$totalPaid|escape:'htmlall':'UTF-8'} €</span></li>
    {*<li>{l s='Total amount : ' d='Modules.Payplug.Shop'}<span id="pp_amount">{$totalPaid|escape:'htmlall':'UTF-8'} €</span></li>*}
</ul>
</p>
