/**
 * 2013 - 2022 PayPlug SAS
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
 *  @copyright 2013 - 2022 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */
var $document, $window, __moduleName__Module = {
    init: function () {
        $document = $(document);
        $window = $(window);

        for (const section in this) {
            if (section != 'init') {
                this[section]['init']();
            }
        }
    },
    abort: {
        init: function () {
            var {abort} = __moduleName__Module;
            $document.on('click', 'input[name=__moduleName__SubmitAbort]', abort.call)
                .on('click', 'button[name=__moduleName__ConfirmAbort]', abort.confirm);
        },
        call: function (event) {
            event.preventDefault();
            var {popup} = __moduleName__Module;
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
                    console.log(jqXHR, textStatus, errorThrown);
                },
                success: function (result) {
                    popup.set(result.content);
                }
            });
        },
        confirm: function (event) {
            event.preventDefault();
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
                    console.log(jqXHR, textStatus, errorThrown);
                },
                success: function (response) {
                    if (response.reload) {
                        location.reload();
                    }
                }
            });
        }
    },
    capture: {
        init: function () {
            var {capture} = __moduleName__Module;
            $document.on('click', 'input[name=__moduleName__SubmitCapture]', capture.call);
        },
        call: function (event) {
            event.preventDefault();
            event.stopPropagation();

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
                beforeSend: function () {
                    $('.pp-capture .loader').show();
                    $('input[name=__moduleName__SubmitCapture]').unbind('click');
                },
                complete: function () {
                    $('.pp-capture .loader').hide();
                    $('input[name=__moduleName__SubmitCapture]').bind('click', function (e) {
                        e.preventDefault();
                        callCapture();
                    });
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('An error occurred while trying to capture. ' +
                        'Maybe you clicked too fast before scripts are fully loaded ' +
                        'or maybe you have a different back-office url than expected.' +
                        'You will find more explanation in JS console.');
                    console.log(jqXHR, textStatus, errorThrown);
                },
                success: function (result) {
                    if (result.status == 'error') {
                        $('.pp-capture .pperror').html(result.data);
                        $('.pp-capture .pperror').removeClass('hide');
                        $('.pp-capture .pperror').show();
                    } else {
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
    },
    refund: {
        init: function () {
            var {refund} = __moduleName__Module;
            $document.on('click', 'input[name=__moduleName__SubmitRefund]', refund.call);
        },
        call: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var url = $('input:hidden[name=admin_ajax_url]').val(),
                data = {
                    _ajax: 1,
                    refund: 1,
                    amount: $('input[name=pp_amount2refund]').val(),
                    id_customer: $('input:hidden[name=id_customer]').val(),
                    pay_id: $('input:hidden[name=pay_id]').val(),
                    inst_id: $('input:hidden[name=inst_id]').val(),
                    id_order: $('input:hidden[name=id_order]').val(),
                    pay_mode: $('input:hidden[name=pay_mode]').val()
                };

            $('#pppanel form p.pperror').hide();
            $('#pppanel form p.ppsuccess').hide();

            if ($('#pppanel input[name=change_order_state]').is(":checked")) {
                data['id_state'] = $('#pppanel input[name=change_order_state]').val();
            }

            $.ajax({
                type: 'POST',
                url: url,
                dataType: 'json',
                data: data,
                beforeSend: function () {
                    $('#pppanel .loader').show();
                    $('input[name=submitPPRefund]').prop("disabled", true);
                },
                complete: function () {
                    $('#pppanel .loader').hide();
                    $('input[name=submitPPRefund]').prop("disabled", false);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('An error occurred while trying to refund. ' +
                        'Maybe you clicked too fast before scripts are fully loaded ' +
                        'or maybe you have a different back-office url than expected.' +
                        'You will find more explanation in JS console.');
                    console.log(jqXHR, textStatus, errorThrown);
                    $('input[name=submit__moduleName__Refund]').prop("disabled", false);
                },
                success: function (result) {
                    if (result.status == 'error') {
                        $('#pppanel form p.pperror').html(result.data)
                            .removeClass('hide')
                            .show();
                    } else {
                        $('.__moduleName__Order').replaceWith(result.template);
                        $('#pppanel form p.ppsuccess').html(result.message)
                            .removeClass('hide')
                            .show();
                        if (result.reload) {
                            location.reload();
                        }
                        $('input[name=pp_amount2refund]').val('');
                    }
                }
            });
        }
    },
    update: {
        init: function () {
            var {update} = __moduleName__Module;
            $document.on('click', 'input[name=__moduleName__SubmitUpdate]', update.call);
        },
        call: function (event) {
            event.preventDefault();
            event.stopPropagation();

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
                beforeSend: function () {
                    $('#pppanel .loader').show();
                },
                complete: function () {
                    $('#pppanel .loader').hide();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('An error occurred while trying to update. ' +
                        'Maybe you clicked too fast before scripts are fully loaded ' +
                        'or maybe you have a different back-office url than expected.' +
                        'You will find more explanation in JS console.');
                    console.log(jqXHR, textStatus, errorThrown);
                },
                success: function (result) {
                    if (result.status == 'error') {
                        $('#pppanel form p.pperror').html(result.data);
                        $('#pppanel form p.pperror').removeClass('hide');
                        $('#pppanel form p.pperror').show();
                    } else {
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
    },
    popup: {
        props: {
            identifier: '__moduleName__Popup',
        },
        init: function () {
            var {popup} = __moduleName__Module,
                {identifier} = popup.props;

            $document.on('click', '.' + identifier + '_close', popup.close)
                .on('click', '.' + identifier + ' .-close', popup.close)
                .on('click', function (event) {
                    var $clicked = $(event.target);
                    if ($clicked.is('.' + identifier) && $('.' + identifier).is('.-open')) {
                        popup.close();
                    }
                });
        },
        set: function (content) {
            var {popup} = __moduleName__Module,
                {identifier} = popup.props;
            if (!sanitizePopupHtml(content)) {
                return;
            }
            if (!$('.' + identifier).length) {
                popup.create();
            }
            popup.hydrate(content);
            popup.open();
        },
        open: function () {
            var {popup} = __moduleName__Module,
                {identifier} = popup.props,
                $popup = $('.' + identifier);

            $popup.addClass('-open');
            window.setTimeout(function () {
                $popup.addClass('-show');
            }, 0);
        },
        close: function () {
            var {popup} = __moduleName__Module,
                {identifier} = popup.props,
                $popup = $('.' + identifier);

            $popup.removeClass('-show');
            window.setTimeout(function () {
                $popup.removeClass('-open');
                popup.remove();
            }, 500);
        },
        create: function () {
            var {popup} = __moduleName__Module,
                {identifier} = popup.props,
                html = '<div class="' + identifier + '"><button class="' + identifier + '_close"></button><div class="' + identifier + '_content"></div></div>';
            $('body').append(html);
        },
        remove: function () {
            var {popup} = __moduleName__Module,
                {identifier} = popup.props,
                $popup = $('.' + identifier);

            $popup.remove();
        },
        hydrate: function (content) {
            var {popup} = __moduleName__Module,
                {identifier} = popup.props;
            $('.' + identifier + '_content').html(content);
        },
    }
};

$(document).ready(function () {
    $('.open_payment_information').unbind('click').click(function (e) {
        if ($(this).parent().parent().next('tr').is(':visible')) {
            $(this).parent().parent().next('tr').hide();
        } else {
            $(this).parent().parent().next('tr').show();
        }
        e.preventDefault();
    });

    __moduleName__Module.init();
});
