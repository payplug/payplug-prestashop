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

<h2>Button component</h2>

<section>
    <h3>Style par défaut</h3>
    <div>
        {include file='./button.tpl' buttonName='payplugUIButton' buttonText='Default'}
    </div>
    <div>
        {include file='./button.tpl' buttonName='payplugUIButton' buttonText='Hover' buttonClassName='hover'}
    </div>
    <div>
        {include file='./button.tpl' buttonName='payplugUIButton' buttonText='Disabled' buttonDisabled=true}
    </div>
</section>

<section>
    <h3>Style secondaire</h3>
    <div>
        {include file='./button.tpl' buttonName='payplugUIButton' buttonStyle='secondary' buttonText='Default'}
    </div>
    <div>
        {include file='./button.tpl' buttonName='payplugUIButton' buttonStyle='secondary' buttonText='Hover' buttonClassName='hover'}
    </div>
    <div>
        {include file='./button.tpl' buttonName='payplugUIButton' buttonStyle='secondary' buttonText='Disabled' buttonDisabled=true}
    </div>
</section>

<section>
    <h3>Style tertiaire</h3>
    <div>
        {include file='./button.tpl' buttonName='payplugUIButton' buttonStyle='tertiary' buttonText='Default'}
    </div>
    <div>
        {include file='./button.tpl' buttonName='payplugUIButton' buttonStyle='tertiary' buttonText='Hover' buttonClassName='hover'}
    </div>
    <div>
        {include file='./button.tpl' buttonName='payplugUIButton' buttonStyle='tertiary' buttonText='Disabled' buttonDisabled=true}
    </div>
</section>

<section>
    props :
    <ul>
        <li>style</li>
        <li>text</li>
        <li>className</li>
        <li>name: string</li>
        <li>data-e2e</li>
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