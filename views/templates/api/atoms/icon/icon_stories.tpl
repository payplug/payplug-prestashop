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
    <p class="_title -sub">Icon component</p>

    <div>
        {include file='./icon.tpl'
            iconName='check'
        }
    </div>
    <div>
        {include file='./icon.tpl'
            iconName='close'
        }
    </div>
    <div>
        {include file='./icon.tpl'
            iconName='lightbulb'
        }
    </div>
    <div>
        {include file='./icon.tpl'
            iconName='link'
        }
    </div>
    <div>
        {include file='./icon.tpl'
            iconName='lock'
        }
    </div>
    <div>
        {include file='./icon.tpl'
            iconName='tooltip'
        }
    </div>
    <div>
        {include file='./icon.tpl'
            iconName='error'
        }
    </div>

    <div class="_props">
        <div>
            props :
            <ul>
                <li>name (mandatory, must be a svg in icons folder)</li>
                <li>data-e2e (optional)</li>
                <li>className (optional)</li>
            </ul>
        </div>

    </div>
</section>

