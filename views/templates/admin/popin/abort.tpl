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

<input type="hidden" name="inst_id" value="{$inst_id|escape:'htmlall':'UTF-8'}"/>
<p>{l s='Are you sure you want to suspend the installment plan on this order?' mod='payplug'}</p>
<p>{l s='Your customer won’t be charged on the due dates.' mod='payplug'}</p>
<div class="payplugPopup_footer">
    <button type="button" class="payplugButton payplugButton-close">{l s='Cancel' mod='payplug'}</button>
    <button type="button" class="payplugButton payplugButton-green" name="confirmPayplugAbort">{l s='Suspend' mod='payplug'}</button>
</div>
