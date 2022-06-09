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
    <p class="_title -sub">Select component</p>
    {assign var='selectOptions' value=[
            ['key' => '0', 'value' => 'Option 1', 'selected' => true],
            ['key' => '1', 'value' => 'Option 2'],
            ['key' => '2', 'value' => 'Option 3']
        ]}
    <div>
        <p class="_subtitle">Select enabled:</p>
        <div>
            {include file='./select.tpl'
                selectName='select_component'
                selectOptions=$selectOptions}
        </div>
    </div>
    <div>
        <p class="_subtitle">Select disabled:</p>
        <div>
            {include file='./select.tpl'
                selectName='select_component2'
                selectOptions=$selectOptions
                selectDisabled=true}
        </div>
    </div>
    <div class="_props">
        <div>
            props :
            <ul>
                <li>name: string (mandatory)</li>
                <li>options: collection (mandatory)</li>
            </ul>
        </div>
        <div>
            options :
            <ul>
                <li>key: string (mandatory)</li>
                <li>value: string (mandatory)</li>
                <li>selected: boolean (optional)</li>
                <li>scrollbar: boolean (optional)</li>
            </ul>
        </div>
        <div>
            state :
            <ul>
                <li>disabled</li>
            </ul>
        </div>
    </div>
</section>