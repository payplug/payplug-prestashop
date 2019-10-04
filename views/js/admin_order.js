/**
 * 2013 - 2019 PayPlug SAS
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
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2019 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

$(document).ready(function() {
    $('input[name=submitPPRefund]').bind('click', function(e) {
        e.preventDefault();
        callRefund();
    });

    $('input[name=submitPPAbort]').bind('click', function(e) {
        e.preventDefault();
        callAbort();
    });

    $('input[name=submitPPUpdate]').bind('click', function(e) {
        e.preventDefault();
        callUpdate();
    });

    $('input[name=submitPPCapture]').bind('click', function(e) {
        e.preventDefault();
        callCapture();
    });
});

function callRefund() {
    $('#pppanel form p.pperror').hide();
    $('#pppanel form p.ppsuccess').hide();
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var amount = $('input[name=pp_amount2refund]').val();
    var id_customer = $('input:hidden[name=id_customer]').val();
    var pay_id = $('input:hidden[name=pay_id]').val();
    var inst_id = $('input:hidden[name=inst_id]').val();
    var id_order = $('input:hidden[name=id_order]').val();
    var id_state = $('#pppanel input[name=change_order_state]').val();
    var pay_mode = $('input:hidden[name=pay_mode]').val();
    var data = {_ajax: 1, refund: 1, amount: amount, id_customer: id_customer, pay_id: pay_id, inst_id: inst_id, id_order: id_order, pay_mode: pay_mode};
    if($('#pppanel input[name=change_order_state]').is(":checked")){
        var data = {_ajax: 1, refund: 1, amount: amount, id_customer: id_customer, pay_id: pay_id, inst_id: inst_id, id_order: id_order, pay_mode: pay_mode, id_state: id_state};
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
            alert('An error occurred while trying to refund. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
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

function callUpdate() {
    $('#pppanel form p.pperror').hide();
    $('#pppanel form p.ppsuccess').hide();
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var pay_id = $('input:hidden[name=pay_id]').val();
    var id_order = $('input:hidden[name=id_order]').val();
    var data = {_ajax: 1, update: 1, pay_id: pay_id, id_order: id_order};

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
            alert('An error occurred while trying to update. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
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

function callAbort() {
    $('.ppoverlay').remove();
    $('#payplug_popin').remove();
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var inst_id = $('input:hidden[name=inst_id]').val();
    var data = {_ajax: 1, popin: 1, type: 'abort', inst_id: inst_id};
    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: data,
        error: function (jqXHR, textStatus, errorThrown) {
            alert('An error occurred while trying to open the popin. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function (result) {
            $('body').append(result.content);
            $('span.ppclose, .ppcancel').bind('click', function () {
                $('#payplug_popin').remove();
                $('.ppoverlay').remove();
            });
            $('#payplug_popin input[type=submit]').bind('click', function (e) {
                e.preventDefault();
                $('#payplug_popin p.pperror').hide();
                var url = $('input:hidden[name=admin_ajax_url]').val();
                var inst_id = $('input:hidden[name=inst_id]').val();
                var id_order = $('input:hidden[name=id_order]').val();
                var submit = 'submitPopin_abort';
                var data = {_ajax: 1, submit: submit, inst_id: inst_id, id_order: id_order};
                $.ajax({
                    type: 'POST',
                    url: url,
                    dataType: 'json',
                    data: data,
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('An error occurred while trying to abort the installment plan. ' +
                            'Maybe you clicked too fast before scripts are fully loaded ' +
                            'or maybe you have a different back-office url than expected.' +
                            'You will find more explanation in JS console.');
                        console.log(jqXHR);
                        console.log(textStatus);
                        console.log(errorThrown);
                    },
                    success: function (response) {
                        if (response.reload) {
                            location.reload();
                        }
                    }
                });
            });
        }
    });
}

function callCapture() {
    $('.pp-capture .pperror').hide();
    $('.pp-capture .ppsuccess').hide();
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var pay_id = $('input:hidden[name=pay_id]').val();
    var id_order = $('input:hidden[name=id_order]').val();
    var data = {_ajax: 1, capture: 1, pay_id: pay_id, id_order: id_order};
    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: data,
        beforeSend: function() {
            $('.pp-capture .loader').show();
            $('input[name=submitPPCapture]').unbind('click');
        },
        complete: function(){
            $('.pp-capture .loader').hide();
            $('input[name=submitPPCapture]').bind('click', function(e) {
                e.preventDefault();
                callCapture();
            });
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert('An error occurred while trying to capture. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function(result)
        {
            if(result.status == 'error') {
                $('.pp-capture .pperror').html(result.data);
                $('.pp-capture .pperror').removeClass('hide');
                $('.pp-capture .pperror').show();
            }
            else {
                $('.pp-capture .ppsuccess').html(result.message);
                $('.pp-capture .ppsuccess').removeClass('hide');
                $('.pp-capture .ppsuccess').show();

                $('.pp-capture form div.pp_list').html(result.data);
                if (result.reload) {
                    location.reload();
                }
            }
        }
    });
}
