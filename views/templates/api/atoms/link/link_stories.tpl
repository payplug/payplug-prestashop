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
    <p class="_title -sub">Link component</p>
    <div>
        {include
            file='./link.tpl'
            linkText='component link'
            linkHref='https://www.w3schools.com/tags/tag_a.asp'
            linkTarget='_blank'
            linkData='data-link'
        }
    </div>
    <div>
        {include
            file='./link.tpl'
            linkText='component link on hover'
            linkHref='https://www.w3schools.com/tags/tag_a.asp'
            linkTarget='_blank'
            linkData='data-link'
            linkClassName='-hover'
        }
    </div>
    <div>
        {include
            file='./link.tpl'
            linkText='component link disabled'
            linkHref='https://www.w3schools.com/tags/tag_a.asp'
            linkTarget='_blank'
            linkData='data-link'
            linkDisabled=true
        }
    </div>
    <div class="_props">
        <div>
            props :
            <ul>
                <li>text (mandatory)</li>
                <li>href (mandatory)</li>
                <li>linkTarget (optional)</li>
                <li>noTag (optional for translation)</li>
            </ul>
        </div>
        <div>
            state :
            <ul>
                <li>disabled</li>
                <li>hover</li>
            </ul>
        </div>
    </div>
</section>