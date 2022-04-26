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

{if !isset($alertType) || !$alertType}
    {assign var='alertType' value='success'}
{/if}

<div class="payplugUIAlert -{$alertType|escape:'htmlall':'UTF-8'}
    {if isset($alertClassName) && $alertClassName} {$alertClassName|escape:'htmlall':'UTF-8'}{/if}"
    {if isset($alertData) && $alertData} data-e2e-name="{$alertData|escape:'htmlall':'UTF-8'}"{/if}>
    {if isset($alertClose) && $alertClose}
        <input type="checkbox" name="alertTriggered" id="alertTriggered" />
        <label for="alertTriggered" class="_close" >
            {include file='./../icon/icon.tpl'
            iconName='close'}
        </label>
    {/if}
    {if isset($alertIcon) && $alertIcon}
        <div class="_icon">
            {include file='./../icon/icon.tpl'
            iconName=$alertIcon}
        </div>
    {/if}
    {if isset($alertTitle) && $alertTitle}
        <div class="_title">{$alertTitle|escape:'htmlall':'UTF-8'}</div>
    {/if}
    <div class="_content">{$alertContent}</div>
</div>
