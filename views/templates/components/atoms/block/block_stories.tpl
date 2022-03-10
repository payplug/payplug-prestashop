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

<section style="flex-direction: column">
    <h2>Block component</h2>

    {capture assign="blockContent"}
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
        <p>Mauris vehicula suscipit neque, nec fringilla est vestibulum faucibus.</p>
        <p>Cras nisi ligula, suscipit quis nunc eget, elementum iaculis velit.</p>
        <p>Donec nunc lorem, consequat ac aliquet vel, porta a leo.</p>
        <ul>
            <li>Maecenas dui ligula, venenatis at magna quis, fringilla sollicitudin nisi.</li>
            <li>Phasellus rhoncus tellus in nisl fringilla facilisis.</li>
        </ul>
        <p>Donec nunc lorem, consequat ac aliquet vel, porta a leo.</p>
        <ol>
            <li>Nunc pretium, turpis eget ultrices feugiat, elit felis convallis risus, quis pulvinar sapien lacus id dolor.</li>
            <li>Donec sed velit non justo placerat tempor ut consequat diam. Nullam mollis eget lacus id consequat.</li>
            <li>Integer at metus at libero aliquet semper sit amet eget dolor.</li>
        </ol>
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

    <hr>
</section>

<section>
    props :
    <ul>
        <li>title</li>
        <li>description</li>
        <li>content</li>
        <li>className</li>
    </ul>
</section>

<section>
    state :
    <ul>
        <li>disabled</li>
    </ul>
</section>