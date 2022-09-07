
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
    <p class="_title -sub">Options component</p>
    <div>
        <p class="_subtitle">Without description:</p>
        <div>
            {assign var=optionsItems value=[
            ['value'=>"0", "text" => 'Selected option'],
            ['value'=>"1", "text" => 'Default option'],
            ['value'=>"2", "text" => 'Hovered option', "className" => "-hover"],
            ['value'=>"3", "text" => 'Disabled option', "disabled" =>  true]
            ]}
            {include file='./options.tpl'
            optionsItems=$optionsItems
            optionsSelected='0'
            optionsName='options_1'}
        </div>
    </div>
    <div>
        <p class="_subtitle">With description:</p>
        <div>
            {assign var=optionsItems value=[
            ['value'=>"0", "text" => 'Selected option', "subText" => 'Lorem ipsum dolor sit amet'],
            ['value'=>"1", "text" => 'Default option', "subText" => 'Consectetur adipiscing elit'],
            ['value'=>"2", "text" => 'Hovered option', "className" => "-hover", "subText" => 'Mauris vehicula suscipit neque'],
            ['value'=>"3", "text" => 'Disabled option', "disabled" =>  true, "subText" => 'Nec fringilla est vestibulum faucibus']
            ]}
            {include file='./options.tpl'
            optionsItems=$optionsItems
            optionsSelected='0'
            optionsName='options_2'}
        </div>
    </div>
    <div class="_props">
        <div>
            props :
            <ul>
                <li>name (mandatory)</li>
                <li>selected (mandatory)</li>
                <li>items (mandatory)</li>
            </ul>
        </div>
        <div>
            optionsItems props :
            <ul>
                <li>value (mandatory)</li>
                <li>text (mandatory)</li>
                <li>subText (optional)</li>
                <li>notallowed (optional)</li>
            </ul>
        </div>

        <div>
            state :
            <ul>
                <li>hover</li>
                <li>selected</li>
                <li>disabled</li>
            </ul>
        </div>
    </div>
</section>

