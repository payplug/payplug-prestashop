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

<div class="form-group">

    <div id="tpl" style="display:block">

        <label class="control-label col-lg-3">
            <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="{l s='hook.order_state.type.tooltips' mod='payplug'}">
                {l s='hook.order_state.type.label' mod='payplug'}
            </span>
        </label>
        <div class="col-lg-9">
            <div class="col-lg-9">
                <div class="row">
                    <div class="col-lg-9">
                        {html_options name=order_state_type options=$order_state_types selected=$current_order_state_type}
                    </div>
                    <div class="clearfix">&nbsp;</div>
                    <br>
                    <div class="col-lg-9">
                        <div class="alert alert-info">
                            {assign "payplug_order_state_link" "<a href='{$payplug_order_state_url|escape:'htmlall':'UTF-8'}' target='_blank'>"}
                            {l s='hook.order_state.type.info' tags=[$payplug_order_state_link] mod='payplug'}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
