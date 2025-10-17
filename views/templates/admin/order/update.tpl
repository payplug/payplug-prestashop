{*
* 2023 Payplug
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
*  @copyright 2023 Payplug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of Payplug SAS
*}
<p><span class="ppbold">{l s='Update this order' mod='payplug'}</p>
<form method="post" action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="admin_ajax_url" value="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="pay_id" value="{$pay_id|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="id_order" value="{$order->id|escape:'htmlall':'UTF-8'}" />
    <input class="{$module_name|escape:'htmlall':'UTF-8'}Button -green" type="submit" name="{$module_name|escape:'htmlall':'UTF-8'}SubmitUpdate" value="{l s='Update' mod='payplug'}" >
    <p class="hide pperror"></p>
    <p class="hide ppsuccess"></p>
    <img class="loader" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/gif/spinner.gif" />
</form>
