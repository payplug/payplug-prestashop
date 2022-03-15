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

<h2>Action component</h2>

<section style="flex-direction: column">
    <div style="margin: 0 0 8px;">
        {include file='./action.tpl'
            actionType='button'
            actionName='action_name'
            actionClassname=''
            actionDisabled=''
            actionText='Default action example'}
    </div>
    <div style="margin: 0 0 8px;">
        {include file='./action.tpl'
            actionType='button'
            actionName='action_name'
            actionClassname='-hover'
            actionDisabled=''
            actionText='Hover action example'}
    </div>
    <div style="margin: 0 0 8px;">
        {include file='./action.tpl'
            actionType='a'
            actionTitle='Action link'
            actionHref='https://www.google.com'
            actionText='Action link example'}
    </div>
    <div style="margin: 0 0 8px;">
        {include file='./action.tpl'
        actionType='button'
        actionName='action_name'
        actionDisabled='true'
        actionText='Disabled action example'}
    </div>
</section>

<section>
    props :
    <ul>
        <li>type(button:default|a)</li>
        <li>title</li>
        <li>href</li>
        <li>name</li>
        <li>classname</li>
        <li>data</li>
        <li>disabled</li>
        <li>text</li>
    </ul>
</section>

<section>
    state :
    <ul>
        <li>disabled</li>
    </ul>
</section>