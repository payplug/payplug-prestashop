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
<div class="payplugUIModal">
    <input type="checkbox" name="modalTriggered" id="modalTriggered" />
    <label class="_overlay" for="modalTriggered"></label>
    <div class="_modal
        {if !isset($modalTitle) || !$modalTitle} -noTitle{/if}
        {if isset($modalClassName) && $modalClassName} {$modalClassName|escape:'htmlall':'UTF-8'}{/if}"
        {if isset($modalData) && $modalData} data-e2e-name="{$modalData|escape:'htmlall':'UTF-8'}"{/if}>

        <label for="modalTriggered" class="_close" >
            {include file='./../icon/icon.tpl'
            iconName='close'}
        </label>

        {if isset($modalTitle) && $modalTitle}
            <div class="_header">
                {if isset($modalTitle) && $modalTitle} {$modalTitle|escape:'htmlall':'UTF-8'}{/if}
            </div>
        {/if}

        <div class="_body">
            {$modalContent}
        </div>
    </div>
</div>