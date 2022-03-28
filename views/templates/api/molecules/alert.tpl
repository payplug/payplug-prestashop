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

{* Alert banner *}
{assign "modeTestLink" "<a href='{$faq_links.sandbox|escape:'htmlall':'UTF-8'}' target='_blank' class='alertTestMode'>"}
{assign "sandboxLiveButton" "<button type='button' name='alertLiveButton' class='alertLiveButton'>"}
{capture assign="alertOnboardingTitle"}{l s='alert.onboarding.title' mod='payplug'}{/capture}
{capture assign="alertContent"}
    {l s='alert.onboarding.content' tags=[$modeTestLink, '<br>', $sandboxLiveButton] mod='payplug'}
{/capture}
{include file='./../atoms/alert/alert.tpl'
    alertType='warning'
    alertTitle=$alertOnboardingTitle
    alertClassName='onboardingAlert'
    alertContent=$alertContent}