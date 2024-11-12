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
<div class="payplugPopin">
    <div class="payplugPopup_row -refund">
        <p>
            {assign "link_to_support_page" "<a target='_blank' href='$support_page_url'>"}
            {l s='admin.modal.refund.text' tags=['<br>',$link_to_support_page] mod='payplug'} <br>
        </p>
        <div class="block-button">
            <input type="button" class="payplugButton -close -green" value="{l s='Ok' mod='payplug'}">
        </div>
    </div>
</div>

