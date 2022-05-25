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

<a href="{$buttonLinkHref|escape:'htmlall':'UTF-8'}"
   title="{if isset($buttonLinkTitle) && $buttonLinkTitle}{$buttonLinkTitle|escape:'htmlall':'UTF-8'}{else}{$buttonLinkText|escape:'htmlall':'UTF-8'}{/if}"
   target="{if isset($buttonLinkTarget) && $buttonLinkTarget}{$buttonLinkTarget|escape:'htmlall':'UTF-8'}{else}_blank{/if}"
    {if isset($buttonLinkData) && $buttonLinkData} data-e2e-name="{$buttonLinkData|escape:'htmlall':'UTF-8'}"{/if}
    class="payplugUIButtonLink
        {if isset($buttonLinkClassName) && $buttonLinkClassName} {$buttonLinkClassName|escape:'htmlall':'UTF-8'}{/if}
        {if isset($buttonLinkStyle) && $buttonLinkStyle} -{$buttonLinkStyle|escape:'htmlall':'UTF-8'}{/if}
        {if isset($buttonLinkIcon) && $buttonLinkIcon} -icon{/if}
        {if isset($buttonLinkDisabled) && $buttonLinkDisabled} -disabled{/if}"
        {if isset($buttonLinkDisabled) && $buttonLinkDisabled} disabled="disabled"{/if}>
    {if isset($buttonLinkIcon) && $buttonLinkIcon}
        {include file='./../icon/icon.tpl'
        iconName=$buttonLinkIcon}
    {/if} {$buttonLinkText|escape:'htmlall':'UTF-8'}
</a>
