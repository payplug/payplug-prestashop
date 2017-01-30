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
    $('input[name=submitPPRefund]').bind('click', function(e) {
        e.preventDefault();
        callRefund();
    })
});

function callRefund() {
    $('#pppanel form p.pperror').hide();
    $('#pppanel form p.ppsuccess').hide();
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var amount = $('input[name=pp_amount2refund]').val();
    var id_customer = $('input:hidden[name=id_customer]').val();
    var pay_id = $('input:hidden[name=pay_id]').val();
    var id_order = $('input:hidden[name=id_order]').val();
    var id_state = $('#pppanel input[name=change_order_state]').val();
    var data = {_ajax: 1, refund: 1, amount: amount, id_customer: id_customer, pay_id: pay_id, id_order: id_order};
    if($('#pppanel input[name=change_order_state]').is(":checked")){
        var data = {_ajax: 1, refund: 1, amount: amount, id_customer: id_customer, pay_id: pay_id, id_order: id_order, id_state: id_state};
    }

    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: data,
        beforeSend: function() {
            $('#pppanel .loader').show();
        },
        complete: function(){
            $('#pppanel .loader').hide();
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert('error CALL REFUND');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function(result)
        {
            if(result.status == 'error') {
                $('#pppanel form p.pperror').html(result.data);
                $('#pppanel form p.pperror').removeClass('hide');
                $('#pppanel form p.pperror').show();
            }
            else {
                $('#pppanel form p.ppsuccess').html(result.message);
                $('#pppanel form p.ppsuccess').removeClass('hide');
                $('#pppanel form p.ppsuccess').show();

                $('#pppanel form div.pp_list').html(result.data);
                if (result.reload) {
                    location.reload();
                }
            }
        }
    });
}