/**
 * 2013 - 2020 PayPlug SAS
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
 *  @copyright 2013 - 2020 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */
var $document, $window, payplug = {
    init: function () {
        $document = $(document);
        $window = $(window);

        for (const section in payplug) {
            if (section != 'init') {
                payplug[section]['init']();
            }
        }
    },
    abort: {
        init: function(){
            var {abort} = payplug;
            $document.on('click','input[name=submitPPAbort]', abort.call)
                .on('click','button[name=confirmPayplugAbort]', abort.confirm);
        },
        call: function(event) {
            event.preventDefault();
            var {abort} = payplug;
            var {popup} = payplug;
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
                    popup.set(result.content);
                }
            });
        },
        confirm: function(event){
            event.preventDefault();
            var {abort} = payplug;
            var {popup} = payplug;
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
        }
    },
    popup: {
        props: {
            identifier: 'payplugPopup',
        },
        init: function () {
            var {popup} = payplug,
                {identifier} = popup.props;

            $document.on('click', '.' + identifier + '_close', popup.close)
                .on('click', '.' + identifier + ' .payplugButton-close', popup.close)
                .on('click', function (event) {
                    var $clicked = $(event.target);
                    if ($clicked.is('.' + identifier) && $('.' + identifier).is('.' + identifier + '-open')) {
                        popup.close();
                    }
                });
        },
        set: function (content) {
            console.log('popup set');
            var {popup} = payplug,
                {identifier} = popup.props;

            if (!$('.' + identifier).length) {
                popup.create();
            }
            popup.hydrate(content);
            popup.open();
        },
        open: function () {
            console.log('popup open');
            var {popup} = payplug,
                {identifier} = popup.props,
                $popup = $('.' + identifier);

            $popup.addClass(identifier + '-open');
            window.setTimeout(function () {
                $popup.addClass(identifier + '-show');
            }, 0);
        },
        close: function () {
            console.log('popup close');
            var {popup} = payplug,
                {identifier} = popup.props,
                $popup = $('.' + identifier);

            $popup.removeClass(identifier + '-show');
            window.setTimeout(function () {
                $popup.removeClass(identifier + '-open');
                popup.remove();
            }, 500);
        },
        create: function () {
            console.log('popup create');
            var {popup} = payplug,
                {identifier} = popup.props,
                html = '<div class="' + identifier + '"><button class="' + identifier + '_close"></button><div class="' + identifier + '_content"></div></div>';
            $('body').append(html);
        },
        remove: function () {
            console.log('popup remove');
            var {popup} = payplug,
                {identifier} = popup.props,
                $popup = $('.' + identifier);

            $popup.remove();
        },
        hydrate: function (content) {
            console.log('popup hydrate');
            var {popup} = payplug,
                {identifier} = popup.props;
            $('.' + identifier + '_content').html(content);
        }
    }
};
$(document).ready(function() {
    $('input[name=submitPPRefund]').bind('click', function(e) {
        e.preventDefault();
        callRefund();
    });


    $('input[name=submitPPUpdate]').bind('click', function(e) {
        e.preventDefault();
        callUpdate();
    });

    $('input[name=submitPPCapture]').bind('click', function(e) {
        e.preventDefault();
        callCapture();
    });

    payplug.init();
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
            $('input[name=submitPPRefund]').prop("disabled", true);
        },
        complete: function(){
            $('#pppanel .loader').hide();
            $('input[name=submitPPRefund]').prop("disabled", false);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert('An error occurred while trying to refund. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
            $('input[name=submitPPRefund]').prop("disabled", false);
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
                $('input[name=pp_amount2refund]').val('');
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
