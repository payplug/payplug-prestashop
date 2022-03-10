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

<h2>Input component</h2>

<section style="justify-content: space-between;">
    <h3>Style par défaut</h3>
    <div style="width:30%;margin:0 0 8px;">
        {include file='./input.tpl'
            inputPlaceholder='Input par défaut'
            inputValue=''
            inputName='input.default'
            inputLabel='Champs texte par défaut'}
    </div>
    <div style="width:30%;margin:0 0 8px;">
        <p style="height: 24px;margin: 0;">Champs sans label :</p>
        {include file='./input.tpl'
            inputPlaceholder='Input par défaut sans label'
            inputValue=''
            inputName='input.withoutLabel'}
    </div>
    <div style="width:30%;margin:0 0 8px;">
        {include file='./input.tpl'
            inputType='password'
            inputPlaceholder='Input par défaut'
            inputValue=''
            inputName='input.password'
            inputLabel='Champs mot de passe par défaut : '}
    </div>
    <div style="width:30%;margin:0 0 8px;">
        {include file='./input.tpl'
            inputPlaceholder='Input focus'
            inputValue=''
            inputName='input.focus'
            inputLabel='Champs texte focus :'
            inputClassName='-focus'}
    </div>
    <div style="width:30%;margin:0 0 8px;">
        {include file='./input.tpl'
            inputPlaceholder='Input hover'
            inputValue=''
            inputName='input.hover'
            inputLabel='Champs texte hover :'
            inputClassName='-hover'}
    </div>
    <div style="width:30%;margin:0 0 8px;">
        {include file='./input.tpl'
            inputPlaceholder='Input error'
            inputValue=''
            inputName='input.error'
            inputLabel='Champs texte error :'
            inputClassName='-error'}
    </div>
    <div style="width:30%;margin:0 0 8px;">
        {include file='./input.tpl'
            inputPlaceholder='Input disabled'
            inputValue=''
            inputName='input.disabled'
            inputLabel='Champs texte disabled :'
            inputDisabled=true}
    </div>
</section>

<section>
    props :
    <ul>
        <li>type</li>
        <li>placeholder</li>
        <li>value</li>
        <li>name</li>
        <li>label</li>
        <li>className</li>
    </ul>
</section>

<section>
    state :
    <ul>
        <li>focus</li>
        <li>hover</li>
        <li>error</li>
        <li>disabled</li>
    </ul>
</section>