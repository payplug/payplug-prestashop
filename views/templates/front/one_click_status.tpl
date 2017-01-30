{*
* 2017 PayPlug
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PayPlug SAS
*  @copyright 2017 PayPlug SAS
*  @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}

<div id="pp_error_one_click">
	<div class="ppOneClickStatus">
		<p class="ppfail"><i class="material-icons">&#xE5CD;</i>{l s='The transaction was not completed and your card was not charged.' mod='payplug'}</p>
		{*<p class="ppfail"><i class="material-icons">&#xE5CD;</i>{l s='The transaction was not completed and your card was not charged.' d='Modules.Payplug.Shop'}</p>*}
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