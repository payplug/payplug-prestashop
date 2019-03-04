{*
* 2019 PayPlug
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
*  @copyright 2019 PayPlug SAS
*  @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
<p><span class="ppbold">{l s='Update this order' mod='payplug'}</p>
<form method="post" action="{$admin_ajax_url|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="admin_ajax_url" value="{$admin_ajax_url|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="pay_id" value="{$pay_id|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="id_order" value="{$order->id|escape:'htmlall':'UTF-8'}" />
    <input class="btn green-button" type="submit" name="submitPPUpdate" value="{l s='Update' mod='payplug'}" >
    <p class="hide pperror"></p>
    <p class="hide ppsuccess"></p>
    <img class="loader" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/admin/spinner.gif" />
</form>