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
<div class="payplugMsg_wrapper">
    {foreach $payment_messages as $payment_message}
        {if $payment_message.type == 'string'}
            <p class="payplugMsg_error">{$payment_message.value|escape:'htmlall':'UTF-8'}</p>
        {elseif $payment_message.type == 'template'}
            {include file="./"|cat:$payment_message.value}
        {/if}
    {/foreach}

    {if isset($with_msg_button) && $with_msg_button}
        <button type="button" class="payplugMsg_button">{l s='Ok' mod='payplug'}</button>
    {/if}
</div>
