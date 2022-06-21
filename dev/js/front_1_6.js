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
 *  @version   2.26.0
 *  International Registered Trademark & Property of PayPlug SAS
 */

// (function ($) {
var $document,
    $window,
    payplugModule = {
        init: function () {
            $document.on('click', '.payplugCard_delete', function (event) {
                event.preventDefault();
                var $card = $(this).parents('.payplugCard').eq(0),
                    id_payplug_card = $card.data('id_card'),
                    url = $(this).attr('href') + '&pc=' + id_payplug_card;

                payplugModule.deleleCard(id_payplug_card, url);
            });
            payplugModule.payment.init();
            payplugModule.popup.init();
        },
        payment: {
            props: {
                pending: false,
            },
            init: function () {
                $document.on('click', '.payment_module a.payplug', payplugModule.payment.pay)
                    .on('submit', '.payplugOneClick form', payplugModule.payment.oneclick);

                $(window).on('load', payplugModule.payment.clean)
                    .on('load', payplugModule.payment.checkerrors)
                    .on('load', payplugModule.payment.handleOPC);

                if (typeof can_use_oney != 'undefined' && can_use_oney) {
                    payplugModule.oney.init();
                }
            },
            send: function (options) {
                if(payplugModule.payment.props.pending) {
                    return false;
                }
                payplugModule.payment.props.pending = true;

                var default_options = {
                    id_card: 'new_card',
                    is_inst: null,
                    is_oney: null,
                    oney_form: null,
                    is_bancontact: null
                };

                options = $.extend(default_options, options);

                var url = $('input:hidden[name=front_ajax_url]').val();
                var id_cart = $('input:hidden[name=id_cart]').val();
                var data = {_ajax: 1, pc: options['id_card'], pay: 1, cart: id_cart};
                if (options['is_inst'] === true) {
                    data.i = 1;
                }
                if (options['is_bancontact'] === true) {
                    data.bancontact = 1;
                }

                if (options['is_oney']) {
                    data.io = options['is_oney'];
                    if (options['oney_form']) {
                        data.oney_form = options['oney_form'];
                    }
                }

                var $submitOneClick = $('input[name=SubmitPayplugOneClick]');

                $.ajax({
                    url: url + '?timestamp=' + new Date().getTime(),
                    headers: {"cache-control": "no-cache"},
                    type: 'POST',
                    async: true,
                    cache: false,
                    dataType: 'json',
                    data: data,
                    beforeSend: function () {
                        if (options['id_card'] != 'new_card') {
                            $('.payplugOneClick_message').addClass('-show');
                        }

                        if ($submitOneClick.length) {
                            $submitOneClick.addClass('disable').attr('disabled', 'disabled');
                        }
                    },
                    complete: function () {
                        if (options['id_card'] != 'new_card') {
                            $('.payplugOneClick_message').removeClass('-show');
                        }

                        if ($submitOneClick.length) {
                            $submitOneClick.removeClass('disable').removeAttr('disabled');
                        }
                    },
                    error: function () {
                        payplugModule.payment.props.pending = false;
                    },
                    success: function (data) {
                        if (data.result) {
                            // redirect to success url
                            if (data.embedded && !data.redirect) {
                                // If Internet Explorer, redirect instead
                                if(!!window.MSInputMethodContext && !!document.documentMode){
                                    window.location.href = data.return_url;
                                    return false;
                                }
                                var is_one_click = options['id_card'] != 'new_card';
                                Payplug.showPayment(data.return_url, is_one_click);
                                payplugModule.payment.props.pending = false;
                            } else {
                                window.location.href = data.return_url;
                            }

                            payplugModule.oney.payment.form.close();
                        } else if (typeof data.response != 'undefined') {
                            var $errorWrapper;
                            $('p.ppfail').hide();
                            if (options['is_inst']) {
                                $errorWrapper = $('.payplugPayment_error.-installment');
                            } else if (options['is_oney']) {
                                $errorWrapper = $('.payplugPayment_error.-oney-oney3x');
                            } else if (options['id_card'] != 'new_card') {
                                $errorWrapper = $('.payplugPayment_error.-one_click');
                            } else {
                                $errorWrapper = $('.payplugPayment_error.-standard');
                            }

                            var errors;
                            if (typeof data.response == 'string') {
                                errors = data.response;
                            } else {
                                errors = [];
                                for ($i = 0;$i < data.response.length; $i++) {
                                    errors.push(data.response[$i]);
                                }
                                errors = errors.join('<br />');
                            }

                            $errorWrapper.html(errors).stop().fadeIn();

                            //Support of opcps
                            if (typeof Fronted !== 'undefined' && $.isFunction(Fronted.showModal)) {
                                Fronted.showModal({
                                    type: 'error',
                                    message: errors
                                });
                            }

                            var delay = 9000;
                            setTimeout(function () {
                                    $errorWrapper.stop().fadeOut();
                                }, delay
                            );

                            payplugModule.payment.props.pending = false;
                        }
                    }
                });

                return;
            },
            pay: function (event) {
                event.preventDefault();
                event.stopPropagation();

                var $link = $(this),
                    is_inst = $link.is('.installment'),
                    is_bancontact = $link.is('.bancontact'),
                    spinner_url= $('input:hidden[name=spinner_url]').val();

                if (($('#form_payplug_payment').length && !is_inst)) {
                    return false;
                }

                payplugModule.payment.send({id_card: 'new_card', is_inst: is_inst, is_bancontact: is_bancontact});

                return false;
            },
            oneclick: function (event) {
                event.preventDefault();
                event.stopPropagation();
                var idCard = $('input[name=payplug_card]:checked').val();
                payplugModule.payment.send({id_card: idCard});
            },
            clean: function () {
                var $links = $('.payment_module a.payplug'),
                    replace_url = 'javascript:void(0);';
                if ($links.length) {
                    $links.each(function () {
                        $(this).attr('href', replace_url);
                    })
                }
            },
            checkerrors: function () {
                if (typeof check_errors == 'undefined' || !check_errors) {
                    return;
                }

                var data = {_ajax: 1, getPaymentErrors: 1};

                $.ajax({
                    url: payplug_ajax_url + '?timestamp=' + new Date().getTime(),
                    headers: {"cache-control": "no-cache"},
                    type: 'POST',
                    async: true,
                    cache: false,
                    dataType: 'json',
                    data: data,
                    success: function (data) {
                        if (data.result) {
                            payplugModule.popup.set(data.result);
                        }
                    }
                });
            },
            handleOPC: function () {
                if (typeof updatePaymentMethods != 'function') {
                    return false;
                }

                var original = updatePaymentMethods;
                updatePaymentMethods = function (json) {
                    original.call(this, json);
                    payplugModule.payment.clean();
                    if (typeof can_use_oney != 'undefined' && can_use_oney) {
                        payplugModule.oney.load(payplugModule.oney.payment.props.open);
                    }
                };
            },
        },
        deleleCard: function (id_card, url) {
            $.ajax({
                url: url,
                headers: {"cache-control": "no-cache"},
                type: 'POST',
                async: true,
                cache: false,
                dataType: 'json',
                success: function (result) {
                    if (result) {
                        $('.payplugCard[data-id_card=' + id_card + ']').remove();
                        $('#module-payplug-cards p.message').show();
                    }
                }
            });
        },
        oney: {
            props: {
                type: '3x',
                queries: [],
                sizes: [
                    {format: 'mobile', limit: 735},
                    {format: 'desktop', limit: 9999},
                ],
                loaded: false
            },
            init: function () {
                var oney = this;
                if ($('.payplugOneyCta').length || $('.payplugOneyCta_wrapper').length) {
                    oney.setCheckout();
                }

                if (($('body').is('.order')) && $('.addresses').length) {
                    oney.address.init();
                }

                oney.popin.init();
                oney.payment.init();
                oney.load();

                $(window).on('resize', oney.sizing).trigger('resize');
            },
            cleanQueries: function () {
                for (i = 0; i < payplugModule.oney.props.queries.length; i++) {
                    if (typeof payplugModule.oney.props.queries[i] != 'undefined')
                        payplugModule.oney.props.queries[i].abort();
                }
                payplugModule.oney.props.queries = [];
            },
            load: function (with_schedule) {
                var oney = payplugModule.oney,
                    is_product = $('body').is('.product') || $('body').is('#product'),
                    data = {
                        _ajax: 1,
                    };

                if (with_schedule) {
                    data['getOneyPriceAndPaymentOptions'] = 1;
                } else {
                    data['isOneyElligible'] = 1;
                }

                // check if context is product page
                if (is_product) {
                    data['id_product'] = id_product;
                    data['quantity'] = parseInt($('#quantity_wanted').val());
                    data['id_product_attribute'] = $('#idCombination').val() ? parseInt($('#idCombination').val()) : 0;
                }

                payplugModule.oney.props.loaded = false;

                oney.popin.setLoader();

                oney.cleanQueries();

                var query = $.ajax({
                    url: payplug_ajax_url + '?timestamp=' + new Date().getTime(),
                    headers: {"cache-control": "no-cache"},
                    type: 'POST',
                    async: true,
                    cache: false,
                    dataType: 'json',
                    data: data,
                    success: function (response) {
                        $('.payplugOneyOption_wrapper').removeClass('-loading');
                        if (response.result) {
                            if (typeof (response.popin) != 'undefined') {
                                payplugModule.oney.popin.set(response.popin);
                                payplugModule.oney.props.loaded = true;
                            }
                            if (typeof (response.payment) != 'undefined') {
                                payplugModule.oney.payment.set(response.payment);
                            }

                            if (typeof response.error != 'undefined' && response.error) {
                                payplugModule.oney.popin.disable();
                            } else {
                                payplugModule.oney.popin.enable();
                            }
                        } else {
                            if (typeof response.popin != 'undefined') {
                                payplugModule.oney.popin.set(response.popin);
                            } else if (typeof response.error != 'undefined') {
                                var popin_error = '<span class="payplugOneyPopin"><p class="payplugOneyPopin_error">' + response.error + '</p></span>'
                                payplugModule.oney.popin.set(popin_error);
                            }

                            if (typeof response.payment != 'undefined') {
                                payplugModule.oney.payment.set(response.payment);
                            } else if (typeof response.error != 'undefined') {
                                var payment_error = '<span class="payplugOneyPayment_error">' + response.error + '</span>';
                                $('.payplugOneyPayment').addClass('-disabled').removeClass('-open');

                                if ($('.payplugOneyPayment_label').find('.payplugOneyPayment_error').length) {
                                    $('.payplugOneyPayment_label').find('.payplugOneyPayment_error').replaceWith(payment_error);
                                } else {
                                    $('.payplugOneyPayment_label').append(payment_error);
                                }
                                $('.payplugOneyOption_wrapper').remove();
                            }
                            payplugModule.oney.popin.disable();
                            payplugModule.oney.props.loaded = true;
                        }

                        if ($('.payplugOneyPayment .payplugOneyRequired').length) {
                            $('.payplugOneyPayment .payplugOneyRequired').wrap('<div class="payplugOneyRequired_wrapper" />');
                            $('.payplugOneyRequired_wrapper').wrap('<div class="payplugOneyRequired_overlay -disabled" />');
                        }
                    }
                });

                oney.props.queries.push(query);
            },
            loader: {
                set: function (target) {
                    var popin = '<span class="payplugOneyLoader">' +
                        '<span class="payplugOneyLoader_spinner"><span></span></span>' +
                        '<span class="payplugOneyLoader_message">' + loading_msg + ' <i>.</i><i>.</i><i>.</i></span>' +
                        '</span>';
                    $(target).html(popin);
                },
            },
            popin: {
                init: function () {
                    var popin = this;
                    $document.on('click', '.payplugOneyCta_button', popin.toggle)
                        .on('click', '.payplugOneyPopin_close', popin.hide)
                        .on('click', '.payplugOneyPopin_navigation button', popin.select);

                    $document.on('click', function (event) {
                        var $clicked = $(event.target);
                        if ((!$clicked.is('.payplugOneyPopin') && !$clicked.parents('.payplugOneyPopin').length) && $('.payplugOneyCta').is('.-open')) {
                            popin.close();
                        }
                    });

                    popin.handleProductEvent();
                    popin.handleCheckoutEvent();
                },
                set: function (content) {
                    if (!$('.payplugOneyCta').length) {
                        return false;
                    }
                    var is_open = $('.payplugOneyCta').is('.-open');
                    if (!sanitizePopupHtml(content)) {
                        return;
                    }
                    $('.payplugOneyPopin').replaceWith(content).removeClass('-loading');
                    var $button = $('.payplugOneyPopin_navigation button').eq(0);
                    payplugModule.oney.popin.choose($button.data('type'));
                    if (is_open) {
                        setTimeout(payplugModule.oney.popin.open, 0);
                    }
                },
                setLoader: function () {
                    var target = '.payplugOneyPopin';
                    if (!$(target).length) {
                        $('.payplugOneyCta').append('<span class="payplugOneyPopin" />');
                    }
                    payplugModule.oney.loader.set(target);
                    $(target).addClass('-loading');
                },
                toggle: function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    var oney = payplugModule.oney,
                        popin = oney.popin,
                        is_active = $('.payplugOneyCta').is('.-open');

                    if (!oney.props.loaded) {
                        oney.load(true);
                    }

                    if (is_active) {
                        popin.close();
                    } else {
                        popin.open();
                    }
                },
                enable: function () {
                    $('.payplugOneyCta_button').removeClass('-disabled');
                    $('.payplugOneyPopin').removeClass('-error');
                },
                disable: function () {
                    $('.payplugOneyCta_button').addClass('-disabled');
                    $('.payplugOneyPopin').addClass('-error');
                    payplugModule.oney.payment.props.open = false;
                },
                select: function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    var $button = $(this),
                        $li = $button.parents('li');

                    if ($li.is('.selected')) {
                        return false;
                    }

                    payplugModule.oney.popin.choose($button.data('type'));
                },
                choose: function (option) {
                    // nav
                    $('.payplugOneyPopin_navigation li').removeClass('selected');
                    $('.payplugOneyPopin_navigation button[data-type=' + option + ']').parent('li').addClass('selected');

                    // option
                    $('.payplugOneyPopin_option').removeClass('-show');
                    $('.payplugOneyPopin_option[data-type=' + option + ']').addClass('-show');
                },
                open: function () {
                    $('.payplugOneyCta').addClass('-open');
                    $('.payplugOneyPopin').addClass('-open');
                    setTimeout(function () {
                        $('.payplugOneyPopin').addClass('-show');
                    }, 0);
                },
                close: function () {
                    $('.payplugOneyPopin').addClass('-show');
                    $('.payplugOneyPopin').removeClass('-open');
                    setTimeout(function () {
                        $('.payplugOneyCta').removeClass('-open');
                    }, 400);
                },
                hide: function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    payplugModule.oney.popin.close();
                },
                handleProductEvent: function () {
                    $document.on('click', '.product_quantity_down, .product_quantity_up, #attributes a', function () {
                        return payplugModule.oney.load(payplugModule.oney.payment.props.open);
                    });
                    $document.on('change', 'input[name=qty], #attributes', function () {
                        return payplugModule.oney.load(payplugModule.oney.payment.props.open);
                    });
                },
                handleCheckoutEvent: function () {
                    if (typeof updateCartSummary != 'function') {
                        return false;
                    }
                    var original = updateCartSummary;
                    updateCartSummary = function (json) {
                        original.call(this, json);
                        payplugModule.oney.load(payplugModule.oney.payment.props.open);
                    };
                }
            },
            address: {
                init: function () {
                    var address = this;
                    $document.on('change', 'select[name=id_address_delivery]', address.check)
                        .on('change', 'select[name=id_address_invoice]', address.check)
                        .on('change', 'input[name=same]', address.check);
                    $('input[name=same]').trigger('change');
                },
                check: function () {
                    var data = {
                        _ajax: 1,
                        checkOneyAddresses: 1,
                        id_address_delivery: $('select[name=id_address_delivery]').val(),
                    };

                    if ($('input[name=same]').prop('checked')) {
                        data['id_address_invoice'] = data['id_address_delivery'];
                    } else if ($('select[name=id_address_invoice]').length) {
                        data['id_address_invoice'] = $('select[name=id_address_invoice]').val();
                    }

                    if ($('.payplugOneyError').length) {
                        $('.payplugOneyError').stop().fadeOut();
                    }

                    $.ajax({
                        url: payplug_ajax_url + '?timestamp=' + new Date().getTime(),
                        headers: {"cache-control": "no-cache"},
                        type: 'POST',
                        async: true,
                        cache: false,
                        dataType: 'json',
                        data: data,
                        success: function (data) {
                            if (!data.result && data.error) {
                                if ($('.payplugOneyError').length) {
                                    $('.payplugOneyError').html(data.error).fadeIn();
                                } else {
                                    var error_html = '<div class="payplugOneyError">' + data.error + '</div>';
                                    $(error_html).appendTo($('.addresses').find('.row').eq(0));
                                }
                                $('.payplugOneyError').stop().fadeIn();
                            }
                        }
                    });
                },
            },
            payment: {
                props: {
                    open: false,
                },
                init: function () {
                    var oney_payment = this;
                    $document.on('change', 'input[name="oney_type"]', oney_payment.select)
                        .on('click', '.payplugOneyPayment_button', oney_payment.send)
                        .on('click', '.payplugOneyPayment_trigger', oney_payment.trigger);

                    oney_payment.form.init();
                },
                select: function () {
                    $('.payplugOneyOption').removeClass('-selected');
                    var $selected = $('input[name="oney_type"]:checked'),
                        $option = $selected.parents('.payplugOneyOption');
                    $option.addClass('-selected');
                    $('.payplugOneyPayment_button').html($option.find('.payplugOneyOption_title').text());
                    payplugModule.oney.props.type = $selected.val();
                    if ($('.-show').length) {
                        payplugModule.oney.payment.form.close();
                    }
                },
                send: function (event) {
                    event.preventDefault();

                    if ($('.payplugOneyRequired').length) {
                        payplugModule.oney.payment.form.open();
                    } else {
                        payplugModule.payment.send({
                            id_card: 'new_card',
                            is_inst: null,
                            is_oney: payplugModule.oney.props.type,
                        });
                    }
                },
                set: function (content) {
                    if (!$('.payplugOneyPayment').length) {
                        return false;
                    }
                    $('.payplugOneyPayment').replaceWith(content);
                    if ($('input[name="oney_type"]').length) {
                        $('input[name="oney_type"]').eq(0).trigger('click');
                    }

                    if (payplugModule.oney.payment.props.open) {
                        setTimeout(payplugModule.oney.payment.open, 0);
                    }

                    $(window).trigger('resize');
                },
                trigger: function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    if ($('.payplugOneyPayment').is('.-disabled')) {
                        return false;
                    }

                    var oney = payplugModule.oney,
                        payment = oney.payment;

                    if (!oney.props.loaded) {
                        oney.load(true);
                    }

                    if (payplugModule.oney.payment.props.open) {
                        payment.close();
                    } else {
                        payment.open();
                    }
                },
                open: function () {
                    $('.payplugOneyPayment').addClass('-open');
                    if (!payplugModule.oney.payment.props.open) {
                        payplugModule.oney.payment.props.open = true;
                        var oney_position = parseInt($('.payplugOneyPayment').offset().top) - 15;
                        $('html,body').stop().animate({'scrollTop': oney_position});
                    }
                },
                close: function () {
                    payplugModule.oney.payment.props.open = false;
                    $('.payplugOneyPayment').removeClass('-open');
                },
                form: {
                    init: function () {
                        var form = this;
                        $document.on('click', '.payplugOneyRequired_close', form.close)
                            .on('click', '.payplugOneyRequired_submit', form.submit)
                            .on('click', '.-validate', form.submit)
                            .on('submit', '.payplugOneyRequired', form.submit)
                            .on('keyup focusout', '.payplugOneyRequired input', form.check);
                    },
                    open: function () {
                        var is_mobile = $('.payplugOneyRequired_overlay:visible').length;
                        $('.payplugOneyPayment_button').addClass('-disabled');
                        $('.payplugOneyRequired_overlay').removeClass('-disabled');

                        if (is_mobile) {
                            var oney_position = parseInt($('.payplugOneyRequired_overlay').offset().top) - 15;
                            $('html,body').stop().animate({'scrollTop': oney_position});
                        }

                        setTimeout(function () {
                            $('.payplugOneyRequired_overlay').addClass('-show');
                        }, 0);
                    },
                    reset: function () {
                        $('.payplugOneyRequired').find('input').each(function () {
                            var $field = $(this);
                            $field.val('');

                            if ($field.is('.-tocheck')) {
                                $field.addClass('-error');
                            }
                        });
                    },
                    close: function () {
                        payplugModule.popup.close();
                        $('.payplugOneyPayment_button').removeClass('-disabled').removeClass('-validate');
                        $('.payplugOneyRequired_overlay').removeClass('-show');
                        setTimeout(function () {
                            $('.payplugOneyRequired_overlay').addClass('-disabled');
                            payplugModule.oney.payment.form.reset();
                        }, 0);
                    },
                    check: function () {
                        var is_valid = true,
                            $fields = $('.payplugOneyRequired_input');

                        $fields.each(function () {
                            var $input = $(this),
                                type = $input.data('type'),
                                value = $input.val(),
                                valid_input = value.length;

                            switch (type) {
                                case 'email' :
                                    var at = value.indexOf('@', 1),
                                        point = value.indexOf('.', at + 1),
                                        plus = value.indexOf('+', 1),
                                        is_email = at > 0 && point > 0 && plus < 0;
                                    valid_input = valid_input && is_email;
                                    break;
                                case 'mobile_phone_number' :
                                    valid_input = valid_input && value.length < 16 && value.length > 8;
                                    break;
                                case 'address1' :
                                    valid_input = valid_input && value.length < 129;
                                    break;
                                case 'postcode' :
                                    valid_input = valid_input && value.length < 6;
                                    break;
                                case 'city' :
                                case 'first_name' :
                                case 'last_name' :
                                    valid_input = valid_input && value.length < 33;
                                    break;
                                default :
                                    break;
                            }

                            if (valid_input) {
                                $input.removeClass('-error');
                            } else {
                                $input.addClass('-error');
                            }

                            is_valid = is_valid && valid_input;
                        });

                        if (is_valid) {
                            $('.payplugOneyPayment_button').removeClass('-disabled').addClass('-validate');
                        } else {
                            $('.payplugOneyPayment_button').addClass('-disabled').removeClass('-validate');
                        }
                    },
                    save: function (payment_data) {
                        var data = {
                            _ajax: 1,
                            savePaymentData: 1,
                            payment_data: payment_data
                        };

                        $('.payplugOneyRequired_message').removeClass('-success').removeClass('-error');

                        $.ajax({
                            url: payplug_ajax_url + '?timestamp=' + new Date().getTime(),
                            headers: {"cache-control": "no-cache"},
                            type: 'POST',
                            async: true,
                            cache: false,
                            dataType: 'json',
                            data: data,
                            success: function (data) {
                                if (data.result) {
                                    $('.payplugOneyRequired_validation').addClass('-show');
                                    window.setTimeout(function () {
                                        $('.payplugOneyRequired_validation').addClass('-appear');
                                    });
                                    window.setTimeout(function () {
                                        payplugModule.popup.close();
                                    }, 5000);
                                } else {
                                    var errors = '';

                                    if (typeof data.message == 'string') {
                                        errors = data.message;
                                    } else {
                                        for (var error in data.message)
                                            if (error !== 'indexOf')
                                                errors += $('<p />').html(data.message[error]).text() + "\n";
                                    }

                                    $('.payplugOneyRequired_message').addClass('-error').html(errors);
                                }
                            }
                        });
                    },
                    submit: function (event) {
                        event.preventDefault();
                        event.stopPropagation();

                        var payment_data = {
                                is_oney: payplugModule.oney.props.type,
                                oney_form: {}
                            },
                            $form = $('.payplugOneyRequired'),
                            $fields = $form.find('input');

                        $fields.each(function () {
                            var $el = $(this), name = $el.attr('name'), value = null;
                            if ($el.is('input[type=radio]')) {
                                value = $('input[name="' + name + '"]:selected').val();
                            } else if ($el.is('input[type=checkbox]')) {
                                value = $('input[name="' + name + '"]:checked').val();
                            } else {
                                value = $el.val()
                            }
                            if (value) {
                                payment_data.oney_form[name] = value;
                            }
                        });


                        if ($('.payplugOneyRequired').parents('.payplugPopin').length) {
                            return payplugModule.oney.payment.form.save(payment_data.oney_form);
                        }

                        payplugModule.payment.send(payment_data);
                    },
                },
            },
            setCheckout: function () {
                var $oney_cta = $('.payplugOneyCta'),
                    $total_price = $('#total_price_container'),
                    $tr = $total_price.parents('tr').eq(0),
                    $table = $total_price.parents('table').eq(0),
                    colspan = 0;

                $tr.find('td').each(function () {
                    var $td = $(this);
                    var cs = $td.attr('colspan') ? parseInt($td.attr('colspan')) : 1;
                    colspan += cs;
                });

                var oney_table = '<tr class="payplugOneyCta_row">' +
                        '<td class="payplugOneyCta_field" colspan="' + colspan + '">' +
                            '<div class="payplugOneyCta_wrapper"></div>' +
                        '</td>' +
                    '</tr>';

                var $cart_voucher = $table.find('#cart_voucher');

                if ($cart_voucher.length) {
                    var rs = parseInt($cart_voucher.attr('rowspan'));
                    $cart_voucher.attr('rowspan', rs + 1);
                }


                $(oney_table).insertAfter($tr);
                $oney_cta.appendTo($table.find('.payplugOneyCta_wrapper'));
            },
            sizing: function () {
                var container = $('.payplugOneyPayment'),
                    sizes = payplugModule.oney.props.sizes,
                    width = container.outerWidth(),
                    current = false;

                sizes.map(function (size, key) {
                    container.removeClass('-' + size.format);
                    if (width < size.limit && !current) {
                        current = size.format;
                    }
                });

                container.addClass('-' + current);
            },
        },
        popup: {
            props: {
                mainClass: 'payplugPopin',
            },
            init: function () {
                var popup = this,
                    props = popup.props;

                $document.on('click', '.payplugPopin_close, .payplugMsg_button', popup.close)
                    .on('click', function (event) {
                        var $clicked = $(event.target);
                        if ($clicked.is('.' + props.mainClass) && $('.' + props.mainClass).is('.-open')) {
                            popup.close();
                        }
                    });
            },
            set: function (content) {
                var popup = payplugModule.popup,
                    props = popup.props;
                if (!sanitizePopupHtml(content)) {
                    return;
                }
                if ($('.' + props.mainClass).length) {
                    popup.close();
                } else {
                    popup.create();
                }
                popup.hydrate(content);
                popup.open();
            },
            open: function () {
                var props = payplugModule.popup.props;
                var popin = $('.' + props.mainClass);
                popin.addClass('-open');
                window.setTimeout(function () {
                    popin.addClass('-show');
                }, 0);
            },
            close: function () {
                var props = payplugModule.popup.props;
                var popin = $('.' + props.mainClass);

                popin.removeClass('-show');
                window.setTimeout(function () {
                    popin.removeClass('-open');
                }, 500);
            },
            create: function () {
                var props = payplugModule.popup.props,
                    html = '<div class="' + props.mainClass + '"><button class="' + props.mainClass + '_close"></button><div class="' + props.mainClass + '_content"></div></div>';
                $('body').append(html);
            },
            hydrate: function (content) {
                var props = payplugModule.popup.props;
                $('.' + props.mainClass + '_content').html(content);
            }
        }
    };
$(document).ready(function () {
    $document = $(document);
    $window = $(window);
    payplugModule.init();
});

// })(window.jQuery);
