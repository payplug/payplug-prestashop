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

<button type="button" name="{$buttonName|escape:'htmlall':'UTF-8'}" class="payplugUIButton
    {if isset($buttonClassName) && $buttonClassName} {$buttonClassName|escape:'htmlall':'UTF-8'}{/if}
    {if isset($buttonStyle) && $buttonStyle} -{$buttonStyle|escape:'htmlall':'UTF-8'}{/if}
    {if isset($buttonDisabled) && $buttonDisabled} -disabled{/if}"
    {if isset($buttonData) && $buttonData} data-e2e-name="{$buttonData|escape:'htmlall':'UTF-8'}"{/if}
    {if isset($buttonDisabled) && $buttonDisabled} disabled="disabled"{/if}>
    {$buttonText|escape:'htmlall':'UTF-8'}
</button>
