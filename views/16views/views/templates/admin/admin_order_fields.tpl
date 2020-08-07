{*
* 2019 PayPlug
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
*  @copyright 2019 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
<input type="hidden" name="id_order" value="{$order->id|escape:'htmlall':'UTF-8'}" />
<input type="hidden" id="pay_mode" name="pay_mode" value="{$pay_mode|escape:'htmlall':'UTF-8'}" />
<input type="hidden" name="admin_ajax_url" value="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" />
<input type="hidden" name="id_customer" value="{$order->id_customer|escape:'htmlall':'UTF-8'}" />

{if isset($pay_id)}
    <input type="hidden" name="pay_id" value="{$pay_id|escape:'htmlall':'UTF-8'}" />
{elseif isset($inst_id)}
    <input type="hidden" name="inst_id" value="{$inst_id|escape:'htmlall':'UTF-8'}" />
{/if}
