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

{if !$connected}
    {* Block general subscribe *}
    {capture assign="generalSubscribe_title"}{l s='general.subscribe.title' mod='payplug'}{/capture}
    {capture assign="generalSubscribe_description"}{l s='general.subscribe.description' mod='payplug'}{/capture}
    {capture assign="generalSubscribe_content"}
        <p>{l s='general.subscribe.createAccountDescription' tags=['<strong>'] mod='payplug'}</p>

        <div class="_buttons">
        {capture assign="generalSubscribe_createAccount"}
            {l s='general.subscribe.createAccount' mod='payplug'}
        {/capture}
        {if 'pspaylater' == $module_name}
            {assign var='payplugRegistrationUrl' value=$portal_url|escape:'htmlall':'UTF-8'|cat:'/signup?sponsor=22101'}
        {else}
            {assign var='payplugRegistrationUrl' value=$portal_url|escape:'htmlall':'UTF-8'}
        {/if}
        {include file='./../atoms/buttonLink/buttonLink.tpl'
            buttonLinkData='payplugRegistration'
            buttonLinkHref=$payplugRegistrationUrl
            buttonLinkName='createAccount'
            buttonLinkText=$generalSubscribe_createAccount}

        {capture assign="generalSubscribe_showLogin"}
            {l s='general.subscribe.showLogin' mod='payplug'}
        {/capture}
        {include file='./../atoms/button/button.tpl'
            buttonData='payplugCustomer'
            buttonName='showLogin'
            buttonStyle='tertiary'
            buttonText=$generalSubscribe_showLogin}
        </div>
    {/capture}
    {if 'pspaylater' == $module_name}
        {assign var='generalSubscribe_className' value='generalBlock -subscribe -center'}
    {else}
        {assign var='generalSubscribe_className' value='generalBlock -subscribe -center -hide'}
    {/if}
    {include file='./../atoms/block/block.tpl'
        blockTitle=$generalSubscribe_title
        blockDescription=$generalSubscribe_description
        blockContent=$generalSubscribe_content
        blockData='generalSubscribe'
        blockDisabled=!$ps_account
        blockClassName=$generalSubscribe_className}

    {* Block general login *}
    {capture assign="generalLogin_title"}{l s='general.login.title' mod='payplug'}{/capture}
    {capture assign="generalLogin_description"}{l s='general.login.description' tags=['<strong>'] mod='payplug'}{/capture}
    {capture assign="generalLogin_content"}
        <form action="#" class="_loginForm">
            <div>
                {capture assign="inputUserEmail"}{l s='general.login.userEmail' mod='payplug'}{/capture}
                {include file='./../atoms/input/input.tpl'
                    inputData='payplugEmail'
                    inputClassName='_email'
                    inputName='userEmail'
                    inputPlaceholder=$inputUserEmail
                    inputLabel=$inputUserEmail}
            </div>
            <div>
                {capture assign="inputUserPassword"}{l s='general.login.userPassword' mod='payplug'}{/capture}
                {include file='./../atoms/input/input.tpl'
                    inputData='payplugPassword'
                    inputClassName='_password'
                    inputType='password'
                    inputName='userPassword'
                    inputPlaceholder=$inputUserPassword
                    inputLabel=$inputUserPassword}
            </div>
            <div>
                {capture assign="generalLogin_button_connect"}{l s='general.login.connexion' mod='payplug'}{/capture}
                {include file='./../atoms/button/button.tpl'
                    buttonData='payplugConnexion'
                    buttonClassName='_connexion'
                    buttonName='login'
                    buttonText=$generalLogin_button_connect}


                {capture assign="generalLogin_createAccount"}{l s='general.login.createAccount' mod='payplug'}{/capture}
                {include file='./../atoms/button/button.tpl'
                    buttonData='payplugShowRegistration'
                    buttonName='hideLogin'
                    buttonStyle='tertiary'
                    buttonClassName='_subscribe'
                    buttonText=$generalLogin_createAccount}
            </div>
            <div>
                {capture assign="generalLogin_link_password"}{l s='general.login.forgottenPassword' mod='payplug'}{/capture}
                {include file='./../atoms/link/link.tpl'
                    linkData='payplugForgotPassword'
                    linkHref=$site_url|escape:'htmlall':'UTF-8'|cat:'/portal/forgot_password'
                    linkClassName='_forgotPassword'
                    linkText=$generalLogin_link_password}
            </div>
        </form>
    {/capture}
    {if 'pspaylater' == $module_name}
        {assign var='generalLogin_className' value='generalBlock -login -hide'}
    {else}
        {assign var='generalLogin_className' value='generalBlock -login'}
    {/if}
    {include file='./../atoms/block/block.tpl'
        blockTitle=$generalLogin_title
        blockDescription=$generalLogin_description
        blockContent=$generalLogin_content
        blockData='generalLogin'
        blockDisabled=!$ps_account
        blockClassName=$generalLogin_className}
{else}
    {* Block general logged *}
    {capture assign="generalLogged_title"}{l s='general.logged.title' mod='payplug'}{/capture}
    {capture assign="generalLogged_description"}{l s='general.logged.description' mod='payplug'}{/capture}
    {capture assign="generalLogged_content"}
        <div class="_user">
            <div class="_userInformations">
                {$payplug_email|escape:'htmlall':'UTF-8'}
                {capture assign="tooltipLogoutContent"}
                    {capture assign='generalLogged_logout'}{l s='general.logged.logout' mod='payplug'}{/capture}
                    {include file='./../atoms/action/action.tpl'
                        actionClassName='_userLogout'
                        actionData='payplugLogout'
                        actionName='logout'
                        actionText=$generalLogged_logout}
                {/capture}
                {include file='./../atoms/tooltip/tooltip.tpl'
                    tooltipData='payplugTooltipLogout'
                    tooltipIcon='tooltip'
                    tooltipContent=$tooltipLogoutContent}
            </div>
            {capture assign="generalLogged_goToPortal"}{l s='general.logged.goToPortal' mod='payplug'}{/capture}
            {include file='./../atoms/buttonLink/buttonLink.tpl'
                buttonLinkClassName='_userPortal'
                buttonLinkHref=$site_url|escape:'htmlall':'UTF-8'|cat:'/portal'
                buttonLinkName='logged_goToPortal'
                buttonLinkText=$generalLogged_goToPortal
                buttonLinkIcon='link'}
        </div>
        <div class="_sandbox">
            {capture assign="generalLogged_modeTitle"}{l s='general.logged.modeTitle' mod='payplug'}{/capture}
            {include file='./../atoms/title/title.tpl' titleText=$generalLogged_modeTitle titleClassName="_sandboxTitle"}

            {if $payplug_switch.sandbox.checked}
                {assign var='generalLogged_sandBoxTestClassName' value='_sandboxDescription -test'}
                {assign var='generalLogged_sandBoxLiveClassName' value='_sandboxDescription -live -hide'}
            {else}
                {assign var='generalLogged_sandBoxTestClassName' value='_sandboxDescription -test -hide'}
                {assign var='generalLogged_sandBoxLiveClassName' value='_sandboxDescription -live'}
            {/if}

            {capture assign='faq_sandboxMode'}
                {include file='./../atoms/link/link.tpl'
                    linkText=''
                    linkHref=$faq_links.sandbox
                    linkData='faq'
                    linkNoTag=true}
            {/capture}
            {capture assign="generalLogged_sandBoxDescription"}
                {l s='general.logged.sandBoxDescriptionTest' tags=[$faq_sandboxMode] mod='payplug'}
            {/capture}
            {include file='./../atoms/paragraph/paragraph.tpl'
                paragraphText=$generalLogged_sandBoxDescription
                paragraphClassName=$generalLogged_sandBoxTestClassName}

            {capture assign="generalLogged_liveDescription"}
                {l s='general.logged.sandBoxDescriptionLive' tags=[$faq_sandboxMode] mod='payplug'}
            {/capture}
            {include file='./../atoms/paragraph/paragraph.tpl'
                paragraphText=$generalLogged_liveDescription
                paragraphClassName=$generalLogged_sandBoxLiveClassName}
            {if 'pspaylater' == $module_name}
                {assign var=allowLiveSwitch value=!$onboardingOneyCompleted}
            {else}
                {assign var=allowLiveSwitch value=!$verified}
            {/if}
            {assign var=items value=[
                [
                    'value' => 1,
                    "dataName" =>"sandboxTest",
                    "text" => $payplug_switch.sandbox.label_left|capitalize|escape:'htmlall':'UTF-8',
                    'disabled'=> !$payplug_switch.sandbox.active
                ],
                [
                    'value' => 0,
                    "dataName" =>"sandboxLive",
                    "text" => $payplug_switch.sandbox.label_right|capitalize|escape:'htmlall':'UTF-8',
                    'disabled' => !$payplug_switch.sandbox.active,
                    'notallowed' => $allowLiveSwitch
                ]
            ]}
            {include file='./../atoms/options/options.tpl'
                optionsItems=$items
                optionsClassName='_sandboxRadioButton'
                optionsSelected=$payplug_switch.sandbox.checked
                optionsName=$payplug_switch.sandbox.name}
        </div>
    {/capture}
    {assign var='generalLogged_className' value='generalBlock -logged'}
    {include file='./../atoms/block/block.tpl'
        blockTitle=$generalLogged_title
        blockDescription=$generalLogged_description
        blockContent=$generalLogged_content
        blockData='generalLogged'
        blockDisabled=!$ps_account || !$payplug_switch.show.checked
        blockClassName=$generalLogged_className}
{/if}
