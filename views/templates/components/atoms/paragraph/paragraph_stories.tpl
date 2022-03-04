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

<h2>Paragraph component</h2>

<section style="flex-direction: column">
    <h3>Style par défaut</h3>
    <div>
        {include file='./paragraph.tpl' paragraphText='component paragraph'}
    </div>
    <div style="margin:0;">
        {include file='./paragraph.tpl' paragraphText='component paragraph disabled' paragraphDisabled=true}
    </div>
</section>

<section>
    props :
    <ul>
        <li>text</li>
        <li>style</li>
        <li>className</li>
    </ul>
</section>

<section>
    state :
    <ul>
        <li>disabled</li>
    </ul>
</section>