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


<div class="payplugUIAccordion
    {if isset($accordionClassName) && $accordionClassName} {$accordionClassName|escape:'htmlall':'UTF-8'}{/if}"
    {if isset($accordionData) && $accordionData} data-e2e-name="{$accordionData|escape:'htmlall':'UTF-8'}"{/if}>
    <input type="checkbox" id="{$accordionIdentifier|escape:'htmlall':'UTF-8'}"/>
        <label for="{$accordionIdentifier|escape:'htmlall':'UTF-8'}">
            {if isset($accordionLabel) && $accordionLabel}
                {$accordionLabel|escape:'htmlall':'UTF-8'}
            {/if}
        </label>

    <div class="payplugUIAccordion_contentWrapper">
        <div class="payplugUIAccordion_content">
            {$accordionContent}
        </div>
    </div>
</div>