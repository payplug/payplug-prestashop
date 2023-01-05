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
*  @copyright 2023 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}

<div class="{$module_name|escape:'htmlall':'UTF-8'}Configuration">
    <div class="{$module_name|escape:'htmlall':'UTF-8'}">
        {if 'pspaylater' == $module_name}
            {* Banner *}
            {include file='./../api/molecules/banner.tpl'}
        {/if}

        {if isset($ps_account_isActivated) && $ps_account_isActivated}
            {include file='./panel/ps_account.tpl'}
        {/if}

        {* description block *}
        {include file='./../api/molecules/description.tpl'}

        {* Alert banner *}
        {include file='./panel/alerts/onboardingalerts.tpl'}

        {* Général block *}
        {include file='./../api/molecules/general.tpl'}

        {if 'pspaylater' != $module_name}
            {* Payment method block *}
            {include file='./../api/molecules/paymentMethods/paymentMethods.tpl'}
        {/if}

        {* Paylater block*}
        {if isset($paymentMethods.oney)}
            {include file='./../api/molecules/paylater/paylater.tpl'}
        {/if}

        {* etat block *}
        {include file='./../api/molecules/state.tpl'}

{*        {include file='./panel/settings.tpl'}*}
    </div>

    {* Configuration footer *}
    {include file='./../api/molecules/footer.tpl'}
</div>