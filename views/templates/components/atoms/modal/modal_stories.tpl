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
        <label class="payplugUIButton" for="modalTriggered">Open Modal</label>
        {capture assign="modalContent"}
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
        {/capture}
        {include file='./modal.tpl'
            modalContent=$modalContent
            modalData='modalData'
            modalTitle='Modal Title'}
    </div>

    <div class="_props">
        <div>
            props:
            <ul>
                <li>title: string (optional)</li>
                <li>content: html (mandatory)</li>
            </ul>
        </div>
        <div>
            state :
            <ul>
                <li>default</li>
                <li>open</li>
            </ul>
        </div>
    </div>
</section>
