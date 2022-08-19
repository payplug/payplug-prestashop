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


{if 'standard' == $paymentOptionIdentifier}
    <div class="standardPaymentOptions">
        {capture assign="embeddedModeAction"}
            {assign var=items value=$embedded_mode_values}
            {include file='./../../atoms/options/options.tpl'
            optionsClassName='_sandboxRadioButton'
            optionsSelected=$payplug_switch.embedded.checked
            optionsName=$payplug_switch.embedded.name}
        {/capture}
        {capture assign="oneClickAction"}
            {include file='./../../atoms/switch/switch.tpl'
            switchEnabledLabel='On'
            switchDisabledLabel='Off'
            switchDataName='oneClickSwitch'
            switchChecked=$payplug_switch.one_click.checked
            switchClassName="oneClickSwitch"
            switchName=$payplug_switch.one_click.name}
        {/capture}

        {assign var='paymentMethodOptions' value=[
        [
        'title' => {l s='standard.embeddedMode.title' mod='payplug'},
        'description' => {l s='standard.embeddedMode.description' mod='payplug'},
        'link' => $faq_links.support,
        'action' => $embeddedModeAction

        ],
        [
        'title' => {l s='standard.oneClick.title' mod='payplug'},
        'description' => {l s='standard.oneClick.description' mod='payplug'},
        'link' => $faq_links.one_click,
        'action' => $oneClickAction
        ]
        ]}

        {include file='./paymentInformations.tpl'
            paymentMethodOptions=$paymentMethodOptions}

    </div>
    {if $installment_isActivated || $deferred_isActivated}
        {* Advanced Standard Settings (deferred & installments) *}
        {include file='./../standard/standardPaymentAdvanced.tpl'}
    {/if}
{elseif 'bancontact' == $paymentOptionIdentifier}
    <div class="paymentMethodOptions">

        {capture assign="bancontactCountryAction"}
            {include file='./../../atoms/switch/switch.tpl'
            switchEnabledLabel='On'
            switchDisabledLabel='Off'
            switchDataName='bancontactCountrySwitch'
            switchChecked=$payplug_switch.bancontact_country.checked
            switchClassName="bancontactCountrySwitch"
            switchName=$payplug_switch.bancontact_country.name}
        {/capture}
        {assign var='paymentMethodOptions' value=[
        [
        'title' => {l s='bancontact.country.title' mod='payplug'},
        'description' => {l s='bancontact.country.description' mod='payplug'},
        'link' => '',
        'action' => $bancontactCountryAction
        ]
        ]}

        {include file='./paymentInformations.tpl'
        paymentMethodOptions=$paymentMethodOptions}


    </div>
{elseif 'oney' == $paymentOptionIdentifier}
    {$paylaterAdvanced}
{/if}


