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

{if 'pspaylater' == $module_name}
    <div class="{$module_name|escape:'htmlall':'UTF-8'}_content">
        {* Banner *}
        {include file='./../api/molecules/banner.tpl'}

        {if isset($ps_account_isActivated) && $ps_account_isActivated}
            {include file='./panel/ps_account.tpl'}
        {/if}
    </div>
{/if}

<div id="payplug_admin"></div>

<script type="text/javascript" src="{$lib_url|escape:'htmlall':'UTF-8'}js/app-1.7.5.js"></script>
<script type="text/javascript" src="{$lib_url|escape:'htmlall':'UTF-8'}js/chunk-vendors-1.7.5.js"></script>