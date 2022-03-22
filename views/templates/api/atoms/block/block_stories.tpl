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
    <p class="_title -sub">Block component</p>

    {capture assign="blockContent"}
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
        <p>Mauris vehicula suscipit neque, nec fringilla est vestibulum faucibus.</p>
        <p>Cras nisi ligula, suscipit quis nunc eget, elementum iaculis velit.</p>
        <p>Donec nunc lorem, consequat ac aliquet vel, porta a leo.</p>
    {/capture}

    <div>
        {include file='./block.tpl'
            blockTitle='Compenent block'
            blockDescription='This is a default block'
            blockContent=$blockContent
            blockData='block-default'}
    </div>

    <div>
        {include file='./block.tpl'
            blockTitle='Compenent block disabled'
            blockDescription='This is a disabled block'
            blockContent=$blockContent
            blockData='block-disabled'
            blockDisabled=true}
    </div>

    <div class="_props">
        <div>
            props :
            <ul>
                <li>title (optional)</li>
                <li>description (optional)</li>
                <li>content (mandatory)</li>
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