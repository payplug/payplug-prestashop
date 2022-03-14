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

<div class="payplugUITooltip">
    <input type="checkbox" id="tooltip-modal">
    <label for="tooltip-modal" class="tooltip-label">{include file="../../_svg/icon-tooltip.tpl"}</label>
    <div class="tooltip-modal">
        {*{include file="../../atoms/block/block.tpl" blockContent="tooltip content"}*}
        <div class="tooltip-modal-content">
            {$tooltipContent}
        </div>
    </div>
</div>