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
    <p class="_title -sub">Accordion component</p>

    <div>
        {capture assign="accordionContent"}
            <p>Neque porro quisquam est qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit...</p>
            <p>Je ne sais pas vraiment ce que ça veut dire, mais je trouve cela... approprié</p>
        {/capture}
        {include file='./accordion.tpl'
        accordionIdentifier='payplugUIAccordion.identifier'
        accordionClassName='-test'
        accordionLabel='Default Accordion'
        accordionContent=$accordionContent}
    </div>

    <div class="_props">
        <div>
            props :
            <ul>
                <li>label (optional)</li>
                <li>identifier (mandatory)</li>
                <li>content (mandatory)</li>
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

