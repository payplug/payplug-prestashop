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
    <p class="_title -sub">Action component</p>
    <div>
        {include file='./action.tpl'
            actionType='button'
            actionName='action_name'
            actionClassname=''
            actionDisabled=''
            actionText='Default action example'}
    </div>
    <div>
        {include file='./action.tpl'
            actionType='button'
            actionName='action_name'
            actionClassName='-hover'
            actionDisabled=''
            actionText='Hovered action example'}
    </div>
    <div>
        {include file='./action.tpl'
            actionType='a'
            actionTitle='Action link'
            actionHref='https://www.google.com'
            actionText='Action link example'}
    </div>
    <div>
        {include file='./action.tpl'
            actionType='button'
            actionName='action_name'
            actionDisabled='true'
            actionText='Disabled action example'}
    </div>

    <div class="_props">
        <div>
            props :
            <ul>
                <li>type (button:default|a)</li>
                <li>title (optional) </li>
                <li>href (optional) </li>
                <li>name (optional) </li>
                <li>text (mandatory)</li>
            </ul>
        </div>

        <div>
            state :
            <ul>
                <li>hover</li>
                <li>disabled</li>
            </ul>
        </div>
    </div>
</section>