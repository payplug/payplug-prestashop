{*
* 2019 PayPlug
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
*  @copyright 2019 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
<div class="panel panel-show panel-remove">
    <div class="panel-heading">{l s='Display to customers' mod='payplug'}</div>
    {if !$connected}
        <p class="ppwarning not_verified">{l s='Before being able to display PayPlug to your customers you need to connect your PayPlug account below.' mod='payplug'}</p>
    {/if}
    <div class="panel-row">
        <label>{l s='Show Payplug to my customers' mod='payplug'}</label>
        <div class="block-right">
            <div class="switch switch-show{if !$connected} ppdisabled{/if}{if $PAYPLUG_SHOW} ppon{/if}">
                <input type="radio" class="switch-input{if !$connected} ppdisabled{/if}"
                       name="PAYPLUG_SHOW" value="0" id="payplug_show_off"
                       {if !$PAYPLUG_SHOW}checked="checked"{/if}>
                <label for="payplug_show_off" class="switch-label switch-label-off"></label>
                <input type="radio" class="switch-input{if !$connected} ppdisabled{/if}"
                       name="PAYPLUG_SHOW" value="1" id="payplug_show_on"
                       {if $PAYPLUG_SHOW}checked="checked"{/if}>
                <label for="payplug_show_on" class="switch-label switch-label-on"></label>
                <span class="switch-selection"></span>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-login panel-remove">
    <div class="panel-heading">{l s='CONNECT' mod='payplug'}</div>
    <div class="panel-row">
        {if $connected}
            <div class="panel-row">
                <label>{l s='Account connected' mod='payplug'}</label>
                <div class="block-right">
                    <p class="ppmail">{$PAYPLUG_EMAIL|escape:'htmlall':'UTF-8'}</p>
                    <div class="ppconnectedbuttons">
                        <a class="" target="_blank" href="{$site_url|escape:'htmlall':'UTF-8'}/portal">{l s='Payplug Portal' mod='payplug'}</a>
                        <span class="separate_pipe">|</span>
                        <input type="submit" id="disconnect-button" name="submitDisconnect" value="{l s='Disconnect' mod='payplug'}">
                    </div>
                </div>
            </div>
        {else}
            <div class="panel-row">
                <label class="left-block">{l s='Email' mod='payplug'}</label>
                <div class="block-right ppemail">
                    <input class="validate validate_email" type="text" placeholder="{l s='E-mail address' mod='payplug'}" name="PAYPLUG_EMAIL" value="{if isset($PAYPLUG_EMAIL)}{$PAYPLUG_EMAIL|escape:'htmlall':'UTF-8'}{/if}" />
                                <span class="input-error">
                                    <span class="error-email-input">{$p_error|escape:'htmlall':'UTF-8'}</span>
                                    <span id="error-email-regexp" class="hide">{l s='E-mail address is not valid.' mod='payplug'}</span>
                                </span>
                </div>
            </div>
            <div class="panel-row">
                <label class="left-block">{l s='Password' mod='payplug'}</label>
                <div class="block-right pppassword">
                    <input class="validate validate_password" id="pppwd" type="password" placeholder="{l s='Password' mod='payplug'}" name="PAYPLUG_PASSWORD" value="" />
                                <span class="input-error">
                                    <span class="error-password-input">{$p_error|escape:'htmlall':'UTF-8'}</span>
                                    <span id="error-password-regexp" class="hide">{l s='Password must be a least 8 caracters long.' mod='payplug'}</span>
                                </span>
                </div>
            </div>
            <div class="panel-row">
                <label class="left-block"></label>
                <div class="block-right">
                    <a href="{$site_url|escape:'htmlall':'UTF-8'}/portal/forgot_password" class="forgot_pwd" target="_blank">{l s='Forgot your password?' mod='payplug'}</a>
                </div>
            </div>
            <div class="panel-row">
                <label class="left-block"></label>
                <div class="center-block">
                    <input type="submit" class="green-button" name ="submitAccount" value="{l s='Connect account' mod='payplug'}">
                    <p class="pptips login">{l s='Don\'t have an account?' mod='payplug'} <a href="{$site_url|escape:'htmlall':'UTF-8'}/portal/signup?origin=PrestashopV2Config" target="_blank">{l s='Sign up' mod='payplug'}</a></p>
                </div>
                <span class="block-right"><img class="loader" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/admin/spinner.gif" /></span>
            </div>
        {/if}
    </div>
</div>

<div class="panel panel-remove">
    <div class="panel-heading">{l s='SETTINGS' mod='payplug'}</div>
    {if $connected && !$verified}
        <p class="ppwarning not_verified">{l s='You are able to perform only TEST transactions.' mod='payplug'} {l s='Please activate your account to perform LIVE transactions.' mod='payplug'}<a href="http://support.payplug.com/customer/portal/articles/1438899" target="_blank"><br />{l s='More information' mod='payplug'}</a></p>
    {/if}

    {include file='./option_sandbox.tpl'}

    {include file='./option_embedded.tpl'}

    <div class="panel-row">
        <div class="block-head">{l s='Advanced settings' mod='payplug'}</div>
    </div>

    {include file='./option_one_click.tpl'}

    {include file='./option_installment_plan.tpl'}

    {include file='./option_deferred_payment.tpl'}

    <div class="block-button">
        <input id="submitSettings" class="green-button{if !$connected} ppdisabled{/if}{if $is_active} is_active{/if}" type="submit" name="submitSettings" value="{l s='Update settings' mod='payplug'}">
    </div>
</div>