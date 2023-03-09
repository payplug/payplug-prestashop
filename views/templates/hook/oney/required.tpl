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
{if isset($oney_required_fields) && $oney_required_fields}
    <form class="{$module_name|escape:'htmlall':'UTF-8'}OneyRequired"{if isset($oney_type) && $oney_type} data-oney_type="{$oney_type|escape:'htmlall':'UTF-8'}"{/if}>
        {if isset($is_popin_tpl) && $is_popin_tpl}
            <p class="{$module_name|escape:'htmlall':'UTF-8'}OneyRequired_title">{l s='Missing information(s)' mod='payplug'}</p>
        {/if}
        <div class="{$module_name|escape:'htmlall':'UTF-8'}OneyRequired_content">
            {foreach $oney_required_fields as $fieldset_type => $fieldset}
                <div class="{$module_name|escape:'htmlall':'UTF-8'}OneyRequired_fieldset -{$fieldset_type|escape:'htmlall':'UTF-8'}">
                    {if $oney_required_fields|count > 1}
                        <p class="{$module_name|escape:'htmlall':'UTF-8'}OneyRequired_sectionName">
                            {if $fieldset_type == 'billing'}
                                {l s='Your billing address:' mod='payplug'}
                            {else}
                                {l s='Your shipping address:' mod='payplug'}
                            {/if}
                        </p>
                    {/if}
                    {foreach $fieldset as $name => $field}
                        <div class="{$module_name|escape:'htmlall':'UTF-8'}OneyRequired_group -{$name|escape:'htmlall':'UTF-8'}">
                            <p>{$field.text|escape:'htmlall':'UTF-8'}</p>
                            {foreach $field.input as $input}
                                <div class="{$module_name|escape:'htmlall':'UTF-8'}OneyRequired_field -{$input.name|escape:'htmlall':'UTF-8'}">
                                    <input data-type="{$input.name|escape:'htmlall':'UTF-8'}" class="{$module_name|escape:'htmlall':'UTF-8'}OneyRequired_input{if $name==$input.name} -tocheck -error{/if}" type="{$input.type|escape:'htmlall':'UTF-8'}" name="{$fieldset_type|escape:'htmlall':'UTF-8'}-{$input.name|escape:'htmlall':'UTF-8'}" placeholder="{$input.value|escape:'htmlall':'UTF-8'}" />
                                </div>
                            {/foreach}
                        </div>
                    {/foreach}
                </div>
            {/foreach}
        </div>
        {if isset($is_popin_tpl) && $is_popin_tpl}
            <p class="{$module_name|escape:'htmlall':'UTF-8'}OneyRequired_message"></p>
            <button type="submit" class="{$module_name|escape:'htmlall':'UTF-8'}OneyRequired_submit">{l s='Validate and restart' mod='payplug'}</button>
            <div class="{$module_name|escape:'htmlall':'UTF-8'}OneyRequired_validation">
                <span>{l s='Informations saved' mod='payplug'}</span>
                <span>{l s='Click Oney again to continue' mod='payplug'}</span>
                <button type="button" class="{$module_name|escape:'htmlall':'UTF-8'}OneyRequired_close -button">{l s='Ok' mod='payplug'}</button>
            </div>
        {/if}
        <button type="button" class="{$module_name|escape:'htmlall':'UTF-8'}OneyRequired_close">{l s='Cancel' mod='payplug'}</button>
    </form>
{/if}
