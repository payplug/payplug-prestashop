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
    <p class="_title -sub">Button component</p>
    <div>
        <p class="_subtitle">Primary Style (default):</p>
        <div>
            {include file='./button.tpl' buttonName='payplugUIButton' buttonText='Default'}
        </div>
        <div>
            {include file='./button.tpl' buttonName='payplugUIButton' buttonText='Hover' buttonClassName='-hover'}
        </div>
        <div>
            {include file='./button.tpl' buttonName='payplugUIButton' buttonText='Disabled' buttonDisabled=true}
        </div>
    </div>
    <div>
        <p class="_subtitle">Secondary Style:</p>
        <div>
            {include file='./button.tpl' buttonName='payplugUIButton' buttonStyle='secondary' buttonText='Default'}
        </div>
        <div>
            {include file='./button.tpl' buttonName='payplugUIButton' buttonStyle='secondary' buttonText='Hover' buttonClassName='-hover'}
        </div>
        <div>
            {include file='./button.tpl' buttonName='payplugUIButton' buttonStyle='secondary' buttonText='Disabled' buttonDisabled=true}
        </div>
    </div>
    <div>
        <p class="_subtitle">Tertiary Style:</p>
        <div>
            {include file='./button.tpl' buttonName='payplugUIButton' buttonStyle='tertiary' buttonText='Default'}
        </div>
        <div>
            {include file='./button.tpl' buttonName='payplugUIButton' buttonStyle='tertiary' buttonText='Hover' buttonClassName='-hover'}
        </div>
        <div>
            {include file='./button.tpl' buttonName='payplugUIButton' buttonStyle='tertiary' buttonText='Disabled' buttonDisabled=true}
        </div>
    </div>
    <div class="_props">
        <div>
            props :
            <ul>
                <li>style (primary:default|secondary|tertiary)</li>
                <li>text (mandatory)</li>
                <li>name (mandatory)</li>
            </ul>
        </div>

        <div>
            state :
            <ul>
                <li>hover</li>
                <li>disabled</li>
            </ul>
        </div>
    </div>
</section>