
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

<h2>Radio Button component</h2>

<section>
    <h3>Default: All states</h3>
    <div>
        <label style="text-align:center"></label>
        {assign var=items value=[['value'=>"on", "dataName" =>"dataLeftButton","text" => 'button gauche', "subText" => 'lorem ipsum'],['value'=>"off","dataName" =>"dataRightButton", "text" => 'button droit', "subText" => 'sit amet dolor']]}
        {include file='./radioButton.tpl'
        radioButtonSelected='on'
        radioButtonName='radioButtonName1'
        }
    </div>
</section>
<section>
    <h3>Hover applied</h3>
    <div>
        {assign var=items value=[['value'=>"on", "dataName" =>"dataLeftButton","text" => 'button gauche', "className"=>"-hover"],
        ['value'=>"off","dataName" =>"dataRightButton", "text" => 'button droit', "className"=>"-hover"]]}
        {include file='./radioButton.tpl'
        radioButtonSelected='on'
        radioButtonName='radioButtonName2'
        }
    </div>
</section>
<section>
    <h3>Disabled Radio Button</h3>
    <div>
        {assign var=items value=[['value'=>"off", "dataName" =>"dataLeftButton","text" => 'button gauche', 'disabled'=>true],['value'=>"off","dataName" =>"dataRightButton", "text" => 'button droit','disabled'=>true]]}
        {include file='./radioButton.tpl'
        radioButtonSelected='on'
        radioButtonName='radioButtonName3'
        }
    </div>



</section>

<section>
    props :
    <ul>
        <li>state: string</li>
        <li>text: string</li>
        <li>sub-text: string</li>
        <li>data-e2e</li>
    </ul>
</section>

<section>
    state :
    <ul>
        <li>default (can be selected)</li>
        <li>hover</li>
        <li>selected</li>
        <li>disabled</li>
    </ul>
</section>
