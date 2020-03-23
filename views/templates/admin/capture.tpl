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
<form class="pp-capture" method="post" action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="admin_ajax_url" value="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="id_order" value="{$order->id|escape:'htmlall':'UTF-8'}" />
    {if isset($payment.id)}
        <input type="hidden" name="pay_id" value="{$payment.id|escape:'htmlall':'UTF-8'}" />
    {/if}
    <div class="form-group">
        {include file='./button_with_loader.tpl' submitName='submitPPCapture' submitValue={l s='Capture' mod='payplug'}}
    </div>
</form>
