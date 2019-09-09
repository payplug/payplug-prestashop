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

<div id="pp_error_one_click">
	<div class="ppOneClickStatus">
		<p class="ppfail"><i class="material-icons">&#xE5CD;</i>{l s='The transaction was not completed and your card was not charged.' mod='payplug'}</p>
	</div>
</div>
{if isset($error) && $error == 1}
{literal}
	<script type="text/javascript">
		$(document).ready(function() {
			$('#payment-confirmation').before($('#pp_error_one_click').html());
			$('#pp_error_one_click').remove();
		});
	</script>
{/literal}
{/if}