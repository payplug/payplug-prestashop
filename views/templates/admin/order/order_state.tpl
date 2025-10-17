{*
* 2023 Payplug
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
*  @author Payplug SAS
*  @copyright 2023 Payplug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of Payplug SAS
*}

{if isset($undefined_history_states) && $undefined_history_states}
    {foreach $undefined_history_states as $state}
        <div class="alert alert-danger">
            {assign "orderStateUpdateLink" $state.updateLink}
            {assign "payplug_order_state_link" "<a href='{$payplug_order_state_url|escape:'htmlall':'UTF-8'}' target='_blank'>"}
            {assign "linkToUpdate" "<a href ='$orderStateUpdateLink' target='_blank'>"}
            <p>
                {l s='admin.order.order_state.payplugOrderStateAlert.text' sprintf=[$state['name']] tags=['<strong>','<strong>',$linkToUpdate] mod='payplug'}
            </p><br>
            <p>{l s='admin.order.order_state.payplugOrderStateAlert.faq' tags=[$payplug_order_state_link]  mod='payplug'}</p>
        </div>
    {/foreach}
{/if}
