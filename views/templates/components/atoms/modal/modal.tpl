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
<label for="modal" class="modal-background"></label>

<div id="test" class="payplugUIModal" {if isset($dataName) && $dataName} data-e2e-name="{$dataName|escape:'htmlall':'UTF-8'}"{/if}>
    <div class="payplugUIModal_header">

        <h3 class="modal-title">{if isset($title) && $title} {$title|escape:'htmlall':'UTF-8'}{/if}</h3>
{*        <a href="#" class="modal-close -icon" > {include file="../../_svg/icon-close.tpl"}</a>*}
        <label for="modal" class="modal-close -icon" > {include file="../../_svg/icon-close.tpl"} </label>
    </div>
    <div class="payplugUIModal_body">{$content}</div>
</div>