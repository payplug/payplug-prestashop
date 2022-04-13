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

<section>
    <p class="_title -sub">Loader component</p>
    <div>
        <div style="flex:1;">
            <p class="_subtitle">Loader sized 10</p>
            <div style="position:relative;height:100px;width:100px;">
                {include file='./loader.tpl' loaderSize='10'}
            </div>
        </div>
        <div style="flex:1;">
            <p class="_subtitle">Loader sized 30</p>
            <div style="position:relative;height:100px;width:100px;">
                {include file='./loader.tpl' loaderSize='30'}
            </div>
        </div>
        <div style="flex:1;text-align: center;">
            <p class="_subtitle">Loader sized 50 (default)</p>
            <div style="position:relative;height:100px;width:100px;">
                {include file='./loader.tpl' loaderSize='50'}
            </div>
        </div>
        <div style="flex:1;">
            <p class="_subtitle">Loader sized 70</p>
            <div style="position:relative;height:100px;width:100px;">
                {include file='./loader.tpl' loaderSize='70'}
            </div>
        </div>
        <div style="flex:1;">
            <p class="_subtitle">Loader sized 90</p>
            <div style="position:relative;height:100px;width:100px;">
                {include file='./loader.tpl' loaderSize='90'}
            </div>
        </div>
    </div>

    <div class="_props">
        <div>
            props :
            <ul>
                <li>size</li>
            </ul>
        </div>
    </div>
</section>

