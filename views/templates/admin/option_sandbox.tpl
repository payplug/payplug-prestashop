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
<div class="panel-row separate_margin_block">
    {include file='./switch.tpl' switch=$switch_sandbox}
    <div class="panel-row">
        <div class="block-right">
            <p class="pptips">
                        <span{if !$PAYPLUG_SANDBOX_MODE} class="hide"{/if} id="mode_live_tips">
{l s='In TEST mode all payments will be simulations and will not generate real transactions.' mod='payplug'}<a href="http://support.payplug.com/customer/portal/articles/1701656" target="_blank">
{l s='Learn more.' mod='payplug'}</a>
                        </span>
                <span{if $PAYPLUG_SANDBOX_MODE} class="hide"{/if} id="mode_sandbox_tips">{l s='In LIVE mode the payments will generate real transactions.' mod='payplug'}</span>
            </p>

        </div>
    </div>
</div>
