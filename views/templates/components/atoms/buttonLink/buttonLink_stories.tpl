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
    <p class="_title -sub">Button Link component</p>
    <div>
        <p class="_subtitle">Primary Style (default):</p>
        <div>
            {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkText='Default' buttonLinkHref='https://payplug.com' buttonLinkIcon='link'}
        </div>
        <div>
            {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkText='Default' buttonLinkHref='https://payplug.com'}
        </div>
        <div>
            {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkText='Hover' buttonLinkHref='https://payplug.com' buttonLinkClassName='-hover'}
        </div>
        <div>
            {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkText='Disabled' buttonLinkHref='https://payplug.com' buttonLinkDisabled=true}
        </div>
    </div>
    <div>
        <p class="_subtitle">Secondary Style:</p>
        <div>
            {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='secondary' buttonLinkText='Default' buttonLinkHref='https://payplug.com' buttonLinkIcon='link'}
        </div>
        <div>
            {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='secondary' buttonLinkText='Default' buttonLinkHref='https://payplug.com'}
        </div>
        <div>
            {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='secondary' buttonLinkText='Hover' buttonLinkHref='https://payplug.com' buttonLinkClassName='-hover'}
        </div>
        <div>
            {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='secondary' buttonLinkText='Disabled' buttonLinkHref='https://payplug.com' buttonLinkDisabled=true}
        </div>
    </div>
    <div>
        <p class="_subtitle">Tertiary Style:</p>
        <div>
            {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='tertiary' buttonLinkText='Default' buttonLinkHref='https://payplug.com' buttonLinkIcon='link'}
        </div>
        <div>
            {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='tertiary' buttonLinkText='Default' buttonLinkHref='https://payplug.com'}
        </div>
        <div>
            {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='tertiary' buttonLinkText='Hover' buttonLinkHref='https://payplug.com' buttonLinkClassName='-hover'}
        </div>
        <div>
            {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='tertiary' buttonLinkText='Disabled' buttonLinkHref='https://payplug.com' buttonLinkDisabled=true}
        </div>
    </div>
    <div class="_props">

        <div>
            props :
            <ul>
                <li>style (primary:default|secondary|tertiary)</li>
                <li>href (mandatory)</li>
                <li>target (optional _blank:default)</li>
                <li>text (mandatory)</li>
                <li>title (optional)</li>
                <li>icon (optional)</li>
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