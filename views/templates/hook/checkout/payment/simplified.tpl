{*
* 2022 PayPlug
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
*  @copyright 2022 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}

{if isset($payplug_options) && $payplug_options}
    {foreach $payplug_options as $payplug_option}
        <div class="row">
            <div class="col-xs-12">
                <p class="payment_module {$module_name|escape:'htmlall':'UTF-8'}Payment">
                    <a href="{$payplug_option.url|escape:'htmlall':'UTF-8'}" class="{$module_name|escape:'htmlall':'UTF-8'}{if isset($extra_class) && $extra_class} {$payplug_option.extra_class|escape:'htmlall':'UTF-8'}{/if}" title="{$payplug_option.label|escape:'htmlall':'UTF-8'}">
                        {$payplug_option.label|escape:'htmlall':'UTF-8'}
                    </a>
                </p>
                <p class="{$module_name|escape:'htmlall':'UTF-8'}Payment_error{if isset($method) && $method} -{$method|escape:'htmlall':'UTF-8'}{/if}"></p>
            </div>
        </div>
    {/foreach}
{/if}
