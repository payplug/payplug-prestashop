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

{if $connected && (!$verified || ('pspaylater' == $module_name && !$onboardingOneyCompleted) || $onBoardingCheck) }
    {if isset($onBoardingCheck) && $onBoardingCheck == true}
        {if $onboardingOneyCompleted == true}
            {capture assign="alertOnboardingCompletedTitle"}{l s='alert.onboarding.completed.title' mod='pspaylater'}{/capture}
            {capture assign="alertOnboardingCompletedContent"}{l s='alert.onboarding.completed.content' mod='pspaylater'}{/capture}
            {assign var="alertOnboardingType" value="success"}
            {assign var="alertOnboardingIcon" value="check"}
        {else}
            {capture assign="supportLink"}<a href="mailto:support@payplug.com">{/capture}
            {capture assign="alertOnboardingCompletedTitle"}{l s='alert.onboarding.notcompleted.title' mod='pspaylater'}{/capture}
            {capture assign="alertOnboardingCompletedContent"}{l s='alert.onboarding.notcompleted.content' tags=[$supportLink] mod='pspaylater'}{/capture}
            {assign var="alertOnboardingType" value="error"}
            {assign var="alertOnboardingIcon" value="timer"}
        {/if}

        {include file='./../../../api/atoms/alert/alert.tpl'
            alertType=$alertOnboardingType
            alertClose=true
            alertIcon=$alertOnboardingIcon
            alertTitle=$alertOnboardingCompletedTitle
            alertContent=$alertOnboardingCompletedContent}
    {/if}

    {if !$onboardingOneyCompleted || ('payplug' == $module_name && !$verified)}

        {assign "modeTestLink" "<a href='{$faq_links.sandbox|escape:'htmlall':'UTF-8'}' target='_blank' class='alertTestMode'>"}
        {assign "sandboxLiveButton" "<button type='button' name='alertLiveButton' class='alertLiveButton'>"}
        {capture assign="alertOnboardingTitle"}{l s='alert.onboarding.title' mod='pspaylater'}{/capture}
        {capture assign="alertContent"}
            {l s='alert.onboarding.content' tags=[$modeTestLink, '<br>', $sandboxLiveButton] mod='pspaylater'}
        {/capture}
        {include file='./../../../api/atoms/alert/alert.tpl'
            alertType='warning'
            alertIcon='lightbulb'
            alertTitle=$alertOnboardingTitle
            alertClassName='onboardingAlert'
            alertContent=$alertContent}
    {/if}

{/if}