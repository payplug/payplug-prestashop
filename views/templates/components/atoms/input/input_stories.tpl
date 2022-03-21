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
    <p class="_title -sub">Input component</p>
    <div>
        <div>
            {include file='./input.tpl'
                inputPlaceholder='Default field text'
                inputValue=''
                inputName='input.default'
                inputLabel='Default field text:'}
        </div>
        <div style="flex-direction: column; align-items: start">
            <p style="height: 24px;margin: 0;">Input without label:</p>
            {include file='./input.tpl'
            inputPlaceholder='Field without label'
            inputValue=''
            inputName='input.withoutLabel'}
        </div>
        <div>
            {include file='./input.tpl'
                inputType='password'
                inputPlaceholder='Default field password'
                inputValue=''
                inputName='input.password'
                inputLabel='Default field password:'}
        </div>
        <div>
            {include file='./input.tpl'
            inputType='number'
            inputPlaceholder='Number'
            inputValue=''
            inputIcon='Euro'
            inputName='input.number'
            inputLabel='Default field number:'}
        </div>
        <div>
            {include file='./input.tpl'
                inputPlaceholder='Focused field'
                inputValue=''
                inputName='input.focus'
                inputLabel='Default field text focused:'
                inputClassName='-focus'}
        </div>
        <div>
            {include file='./input.tpl'
            inputPlaceholder='Hovered field'
            inputValue=''
            inputName='input.hover'
            inputLabel='Default field text hovered:'
            inputClassName='-hover'}
        </div>
        <div>
            {include file='./input.tpl'
                inputPlaceholder='Error field'
                inputValue=''
                inputName='input.error'
                inputLabel='Default field text error'
                inputClassName='-error'}
        </div>
        <div>
            {include file='./input.tpl'
            inputPlaceholder='Disabled fields'
            inputValue=''
            inputName='input.disabled'
            inputLabel='Default field disabled:'
            inputDisabled=true}
        </div>
    </div>
    <div class="_props">
        <div>
            props :
            <ul>
                <li>type (text:default|password|number|hidden)</li>
                <li>placeholder (optional)</li>
                <li>value (optional)</li>
                <li>name (mandatory)</li>
                <li>label (optional)</li>
                <li>icon (optional | allowed values: Euro)</li>
                <li>step (optional | only applies to number type)</li>
                <li>min (optional | only applies to number type)</li>
                <li>max (optional | only applies to number type)</li>
            </ul>
        </div>

        <div>
            state :
            <ul>
                <li>focus</li>
                <li>hover</li>
                <li>error</li>
                <li>disabled</li>
            </ul>
        </div>
    </div>
</section>
