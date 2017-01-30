/**
 * 2013 - 2016 PayPlug SAS
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
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2016 PayPlug SAS
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

$(document).ready(function() {
    callInfoRefund();
});

function callInfoRefund(){
    var url = $('.pp_admin_ajax_url').attr('href');
    var data = {_ajax: 1, popinRefund: 1};

    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: data,
        error: function(jqXHR, textStatus, errorThrown) {
            alert('error CALL INFO REFUND');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function(result)
        {
            $('body').append(result.content);
            $('.ppclose, span.ppcancel').bind('click', function(){
                $('#payplug_popin').remove();
                $('.ppoverlay').remove();
            });
        }
    });
}