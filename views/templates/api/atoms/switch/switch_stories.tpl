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
    <p class="_title -sub">Modal component</p>
    <div>
        <p class="_subtitle">Default</p>
        {include file='./switch.tpl'
            switchEnabledLabel='On'
            switchDisabledLabel='Off'
            switchDataName='switchData'
            switchName='test'
            switchChecked=true}

    </div>
    <div>
        <p class="_subtitle">Disabled</p>
        {include file='./switch.tpl'
        switchEnabledLabel='On'
        switchDisabledLabel='Off'
        switchDataName='switchData'
        switchDisabled=true
        switchName='test'
        }
    </div>

</section>


<section>
    props :
    <ul>
        <li>label:text(mandatory)</li>
        <li>data-e2e:text (mandatory)</li>
        <li>className: text (optional)</li>
    </ul>
</section>

<section>
    state :
    <ul>
        <li>disabled</li>
        <li>checked</li>
    </ul>
</section>