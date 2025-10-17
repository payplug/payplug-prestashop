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
{if isset($messages) && $messages}
    <div class="{$module_name|escape:'htmlall':'UTF-8'}Msg_wrapper">
        {foreach $messages as $message}
            {if $message.type == 'string'}
                <p {if isset($is_error_message) && $is_error_message} class="{$module_name|escape:'htmlall':'UTF-8'}Msg_error" {else} class="{$module_name|escape:'htmlall':'UTF-8'}Msg_text" {/if}>{$message.value|escape:'htmlall':'UTF-8'}</p>
            {elseif $message.type == 'template'}
                {include file="../"|cat:$message.value}
            {/if}
        {/foreach}
        {if isset($with_msg_button) && $with_msg_button}
            <button type="button" class="{$module_name|escape:'htmlall':'UTF-8'}Msg_button" name="card_deleted">{l s='hook.partials.message.ok' mod='payplug'}</button>
        {/if}

        {if isset($with_yes_no_buttons) && $with_yes_no_buttons}
        <div class="{$module_name|escape:'htmlall':'UTF-8'}Popup_footer">
            <button type="button" class="{$module_name|escape:'htmlall':'UTF-8'}Msg_button {$module_name|escape:'htmlall':'UTF-8'}Msg_confirmButton" name="{$module_name|escape:'htmlall':'UTF-8'}ConfirmDelete">{l s='hook.partials.message.yes' mod='payplug'}</button>
            <button type="button" class="{$module_name|escape:'htmlall':'UTF-8'}Msg_button {$module_name|escape:'htmlall':'UTF-8'}Msg_declineButton">{l s='hook.partials.message.no' mod='payplug'}</button>
        </div>
        {/if}
    </div>
{/if}
