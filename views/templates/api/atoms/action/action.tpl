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
{if !isset($actionType) || !$actionType}
    {assign var='actionType' value='button'}
{/if}

<{$actionType|escape:'htmlall':'UTF-8'} class="payplugUIAction
    {if isset($actionDisabled) && $actionDisabled} -disabled{/if}
    {if isset($actionClassName) && $actionClassName} {$actionClassName|escape:'htmlall':'UTF-8'}{/if}"
    {if isset($actionTitle) && $actionTitle} title="{$actionTitle|escape:'htmlall':'UTF-8'}"{/if}
    {if isset($actionHref) && $actionHref} href="{$actionHref|escape:'htmlall':'UTF-8'}"{/if}
    {if isset($actionName) && $actionName} name="{$actionName|escape:'htmlall':'UTF-8'}"{/if}
    {if isset($actionData) && $actionData} data-e2e-name="{$actionData|escape:'htmlall':'UTF-8'}"{/if}
    {if isset($actionDisabled) && $actionDisabled} disabled="disabled"{/if}>
    {$actionText|escape:'htmlall':'UTF-8'}
</{$actionType|escape:'htmlall':'UTF-8'}>
