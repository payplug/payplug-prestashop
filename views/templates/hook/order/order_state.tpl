{*
* 2021 PayPlug
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
*  @copyright 2021 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}

<div class="form-group">

    <div id="tpl" style="display:block">

        <label class="control-label col-lg-3">
            <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="ORDER STATE TOOLTIPS">
                Phrase
            </span>
        </label>

        <div class="col-lg-9">

            <div class="col-lg-9">
                <div class="row">
                    <div class="col-lg-9">
                        {html_options name=order_state_type options=$myOptions selected=$mySelect}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
