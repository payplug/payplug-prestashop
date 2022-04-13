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

<div class="payplugUIImage
    {if isset($imageClassName) && $imageClassName} {$imageClassName|escape:'htmlall':'UTF-8'}{/if}"
    {if isset($imageData) && $imageData} data-e2e-name="{$imageData|escape:'htmlall':'UTF-8'}"{/if}>
    <img src="{$imageSrc|escape:'htmlall':'UTF-8'}" alt="{$imageAlt|escape:'htmlall':'UTF-8'}">
</div>