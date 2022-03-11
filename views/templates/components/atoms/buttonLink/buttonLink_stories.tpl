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

<h2>Button Link component</h2>

<section>
    <h3>Style par défaut</h3>
    <div>
        {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkText='Default' buttonLinkLink='https://payplug.com' buttonLinkIcon='link'}
    </div>
    <div>
        {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkText='Default' buttonLinkLink='https://payplug.com'}
    </div>
    <div>
        {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkText='Hover' buttonLinkLink='https://payplug.com' buttonLinkClassName='-hover'}
    </div>
    <div>
        {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkText='Disabled' buttonLinkLink='https://payplug.com' buttonLinkDisabled=true}
    </div>
</section>

<section>
    <h3>Style secondaire</h3>
    <div>
        {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='secondary' buttonLinkText='Default' buttonLinkLink='https://payplug.com' buttonLinkIcon='link'}
    </div>
    <div>
        {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='secondary' buttonLinkText='Default' buttonLinkLink='https://payplug.com'}
    </div>
    <div>
        {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='secondary' buttonLinkText='Hover' buttonLinkLink='https://payplug.com' buttonLinkClassName='-hover'}
    </div>
    <div>
        {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='secondary' buttonLinkText='Disabled' buttonLinkLink='https://payplug.com' buttonLinkDisabled=true}
    </div>
</section>

<section>
    <h3>Style tertiaire</h3>
    <div>
        {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='tertiary' buttonLinkText='Default' buttonLinkLink='https://payplug.com' buttonLinkIcon='link'}
    </div>
    <div>
        {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='tertiary' buttonLinkText='Default' buttonLinkLink='https://payplug.com'}
    </div>
    <div>
        {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='tertiary' buttonLinkText='Hover' buttonLinkLink='https://payplug.com' buttonLinkClassName='-hover'}
    </div>
    <div>
        {include file='./buttonLink.tpl' buttonLinkName='payplugUIButtonLink' buttonLinkStyle='tertiary' buttonLinkText='Disabled' buttonLinkLink='https://payplug.com' buttonLinkDisabled=true}
    </div>

</section>

<section>
    props :
    <ul>
        <li>style</li>
        <li>text</li>
        <li>target</li>
        <li>className</li>
        <li>name</li>
        <li>datae2e</li>
    </ul>
</section>

<section>
    state :
    <ul>
        <li>default</li>
        <li>hover</li>
        <li>disabled</li>
    </ul>
</section>

<section>
    icon :
    <ul>
        <li>icon</li>
    </ul>
</section>