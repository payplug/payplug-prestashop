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

<p>
    {l s='deferred.description' mod='payplug'}
    {if isset($faq_links.deferred) && $faq_links.deferred}
        {include file='./../../../atoms/link/link.tpl'
            linkText={l s='deferred.textLink' mod='payplug'}
            linkHref=$faq_links.deferred
            linkTarget='_blank'
            linkData='data-faqdeferredLink'}
    {/if}
</p>
<div class="_inputs">
    <p>{l s='deferred.beforeText' mod='payplug'}</p>
    {include file='./../../../atoms/select/select.tpl'
        selectDisabled=!$paymentMethodAdvancedOptionChecked
        selectClassName='-deferred'
        selectName='payplug_deferred_state'
        selectData='deferredSelect'
        selectScrollbar=true
        selectOptions=$order_states_values}
</div>