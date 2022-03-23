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

{capture assign="modalContent"}


    {if !isset($isOnboardedCompleted)}

        {capture assign="popinCheckonboardingText"}{l s='popin.checkonboarding.text' mod='payplug'}{/capture}
        {include file='./../../atoms/paragraph/paragraph.tpl'
        paragraphText=$popinCheckonboardingText}

        <div class="modalSandboxInput">
        {capture assign="popinCheckonboardingPassword"}{l s='popin.checkonboarding.password' mod='payplug'}{/capture}
        {include file='./../../atoms/input/input.tpl'
        inputType='password'
        inputName='password'
        inputLabel=$popinCheckonboardingPassword
        inputClassName=$errorClass}

        {if isset($errorMessage)}<div class="errorMessage">
            {include file='./../../_svg/icon_error.tpl'
            inputType='password'
            inputName='password'
            inputLabel=$popinCheckonboardingPassword
            inputClassName=$errorClass}

            {$errorMessage|escape:'htmlall':'UTF-8'}</div>
        {/if}
        </div>
        <div class="modalSandboxFooter">
            {capture assign="popinCheckonboardingCancel"}{l s='popin.checkonboarding.cancel' mod='payplug'}{/capture}
            {include file='./../../atoms/button/button.tpl'
            buttonData='cancel'
            buttonName='closePopin'
            buttonText=$popinCheckonboardingCancel
            buttonStyle='tertiary'}
            {capture assign="popinCheckonboardingSubmit"}{l s='popin.checkonboarding.submit' mod='payplug'}{/capture}
            {include file='./../../atoms/button/button.tpl'
            buttonData='submit'
            buttonName='submitSandbox'
            buttonText=$popinCheckonboardingSubmit}
        </div>


    {else}
    <div class="modalSandboxBody">
        {if $isOnboardedCompleted == false}

            {capture assign="modalSandboxOnboardingProcessingTitle"}{l s='modal.sandbox.onboarding.processing.title' mod='payplug'}{/capture}
            {include file='./../../atoms/title/title.tpl'
            titleText=$modalSandboxOnboardingProcessingTitle}

            {assign "supportLink" "<a href='mailto:support@payplug.com' target='_blank'>"}
            {capture assign="modalSandboxOnboardingProcessingText"}{l s='modal.sandbox.onboarding.processing.text' tags=[$supportLink] mod='payplug'}{/capture}
            {include file='./../../atoms/paragraph/paragraph.tpl'
            paragraphText=$modalSandboxOnboardingProcessingText}

            {capture assign="popinCheckonboardingCancel"}{l s='popin.checkonboarding.submit' mod='payplug'}{/capture}
            {include file='./../../atoms/button/button.tpl'
            buttonData='cancel'
            buttonName='closePopin'
            buttonText=$popinCheckonboardingCancel}

        {else}

            {capture assign="modalSandboxOnboardingProcessedTitle"}{l s='modal.sandbox.onboarding.processed.title' mod='payplug'}{/capture}
            {include file='./../../atoms/title/title.tpl'
            titleText=$modalSandboxOnboardingProcessedTitle}
            {capture assign="modalSandboxOnboardingProcessedText"}{l s='modal.sandbox.onboarding.processed.text' mod='payplug'}{/capture}
            {include file='./../../atoms/paragraph/paragraph.tpl'
            paragraphText=$modalSandboxOnboardingProcessedText}

            {capture assign="popinCheckonboardingSuccess"}{l s='popin.checkonboarding.submit' mod='payplug'}{/capture}
            {include file='./../../atoms/button/button.tpl'
            buttonData='success'
            buttonName='validateLive'
            buttonText=$popinCheckonboardingSuccess}

        {/if}
    </div>
    {/if}


{/capture}

{capture assign="modalTitle"}{l s='popin.checkonboarding.title' mod='payplug'}{/capture}

{include file='./../../atoms/modal/modal.tpl'
    modalTitle=$modalTitle
    modalClassName='modalCheckonboarding'
    modalContent=$modalContent
    modalData='popinCheckonboarding'}