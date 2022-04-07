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
    __moduleName__Module = {
        init: function () {
            $document.on('click', '.payplugCard_delete', function (event) {
                event.preventDefault();
                var $card = $(this).parents('.payplugCard').eq(0),
                    id_payplug_card = $card.data('id_card'),
                    url = $(this).attr('href') + '&pc=' + id_payplug_card;

                __moduleName__Module.deleleCard(id_payplug_card, url);
            });
            __moduleName__Module.payment.init();
            __moduleName__Module.popup.init();
        },
        payment: {
            props: {
                pending: false,
            },
            init: function () {
                $document.on('click', '.payment_module a.payplug', __moduleName__Module.payment.pay)
                    .on('submit', '.payplugOneClick form', __moduleName__Module.payment.oneclick);

                $(window).on('load', __moduleName__Module.payment.clean)
                    .on('load', __moduleName__Module.payment.checkerrors)
                    .on('load', __moduleName__Module.payment.handleOPC);

                if (typeof can_use_oney != 'undefined' && can_use_oney) {
                    __moduleName__Module.oney.init();
                }
            },
            send: function (options) {
                if(__moduleName__Module.payment.props.pending) {
                    return false;
                }
                __moduleName__Module.payment.props.pending = true;

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
                        __moduleName__Module.payment.props.pending = false;
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
                                __moduleName__Module.payment.props.pending = false;
                            } else {
                                window.location.href = data.return_url;
                            }

                            __moduleName__Module.oney.payment.form.close();
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

                            __moduleName__Module.payment.props.pending = false;
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

                __moduleName__Module.payment.send({id_card: 'new_card', is_inst: is_inst, is_bancontact: is_bancontact});

                return false;
            },
            oneclick: function (event) {
                event.preventDefault();
                event.stopPropagation();
                var idCard = $('input[name=payplug_card]:checked').val();
                __moduleName__Module.payment.send({id_card: idCard});
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
                            __moduleName__Module.popup.set(data.result);
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
                    __moduleName__Module.payment.clean();
                    if (typeof can_use_oney != 'undefined' && can_use_oney) {
                        __moduleName__Module.oney.load(__moduleName__Module.oney.payment.props.open);
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
                if ($('.oneyCta').length || $('.oneyCta_wrapper').length) {
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
                for (i = 0; i < __moduleName__Module.oney.props.queries.length; i++) {
                    if (typeof __moduleName__Module.oney.props.queries[i] != 'undefined')
                        __moduleName__Module.oney.props.queries[i].abort();
                }
                __moduleName__Module.oney.props.queries = [];
            },
            load: function (with_schedule) {
                var oney = __moduleName__Module.oney,
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

                __moduleName__Module.oney.props.loaded = false;

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
                        $('.oneyOption_wrapper').removeClass('-loading');
                        if (response.result) {
                            if (typeof (response.popin) != 'undefined') {
                                __moduleName__Module.oney.popin.set(response.popin);
                                __moduleName__Module.oney.props.loaded = true;
                            }
                            if (typeof (response.payment) != 'undefined') {
                                __moduleName__Module.oney.payment.set(response.payment);
                            }

                            if (typeof response.error != 'undefined' && response.error) {
                                __moduleName__Module.oney.popin.disable();
                            } else {
                                __moduleName__Module.oney.popin.enable();
                            }
                        } else {
                            if (typeof response.popin != 'undefined') {
                                __moduleName__Module.oney.popin.set(response.popin);
                            } else if (typeof response.error != 'undefined') {
                                var popin_error = '<span class="oneyPopin"><p class="oneyPopin_error">' + response.error + '</p></span>'
                                __moduleName__Module.oney.popin.set(popin_error);
                            }

                            if (typeof response.payment != 'undefined') {
                                __moduleName__Module.oney.payment.set(response.payment);
                            } else if (typeof response.error != 'undefined') {
                                var payment_error = '<span class="oneyPayment_error">' + response.error + '</span>';
                                $('.oneyPayment').addClass('-disabled').removeClass('-open');

                                if ($('.oneyPayment_label').find('.oneyPayment_error').length) {
                                    $('.oneyPayment_label').find('.oneyPayment_error').replaceWith(payment_error);
                                } else {
                                    $('.oneyPayment_label').append(payment_error);
                                }
                                $('.oneyOption_wrapper').remove();
                            }
                            __moduleName__Module.oney.popin.disable();
                            __moduleName__Module.oney.props.loaded = true;
                        }

                        if ($('.oneyPayment .oneyRequired').length) {
                            $('.oneyPayment .oneyRequired').wrap('<div class="oneyRequired_wrapper" />');
                            $('.oneyRequired_wrapper').wrap('<div class="oneyRequired_overlay -disabled" />');
                        }
                    }
                });

                oney.props.queries.push(query);
            },
            loader: {
                set: function (target) {
                    var popin = '<span class="oneyLoader">' +
                        '<span class="oneyLoader_spinner"><span></span></span>' +
                        '<span class="oneyLoader_message">' + loading_msg + ' <i>.</i><i>.</i><i>.</i></span>' +
                        '</span>';
                    $(target).html(popin);
                },
            },
            popin: {
                init: function () {
                    var popin = this;
                    $document.on('click', '.oneyCta_button', popin.toggle)
                        .on('click', '.oneyPopin_close', popin.hide)
                        .on('click', '.oneyPopin_navigation button', popin.select);

                    $document.on('click', function (event) {
                        var $clicked = $(event.target);
                        if ((!$clicked.is('.oneyPopin') && !$clicked.parents('.oneyPopin').length) && $('.oneyCta').is('.-open')) {
                            popin.close();
                        }
                    });

                    popin.handleProductEvent();
                    popin.handleCheckoutEvent();
                },
                set: function (content) {
                    if (!$('.oneyCta').length) {
                        return false;
                    }
                    var is_open = $('.oneyCta').is('.-open');
                    if (!sanitizePopupHtml(content)) {
                        return;
                    }
                    $('.oneyPopin').replaceWith(content).removeClass('-loading');
                    var $button = $('.oneyPopin_navigation button').eq(0);
                    __moduleName__Module.oney.popin.choose($button.data('type'));
                    if (is_open) {
                        setTimeout(__moduleName__Module.oney.popin.open, 0);
                    }
                },
                setLoader: function () {
                    var target = '.oneyPopin';
                    if (!$(target).length) {
                        $('.oneyCta').append('<span class="oneyPopin" />');
                    }
                    __moduleName__Module.oney.loader.set(target);
                    $(target).addClass('-loading');
                },
                toggle: function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    var oney = __moduleName__Module.oney,
                        popin = oney.popin,
                        is_active = $('.oneyCta').is('.-open');

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
                    $('.oneyCta_button').removeClass('-disabled');
                    $('.oneyPopin').removeClass('-error');
                },
                disable: function () {
                    $('.oneyCta_button').addClass('-disabled');
                    $('.oneyPopin').addClass('-error');
                    __moduleName__Module.oney.payment.props.open = false;
                },
                select: function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    var $button = $(this),
                        $li = $button.parents('li');

                    if ($li.is('.selected')) {
                        return false;
                    }

                    __moduleName__Module.oney.popin.choose($button.data('type'));
                },
                choose: function (option) {
                    // nav
                    $('.oneyPopin_navigation li').removeClass('selected');
                    $('.oneyPopin_navigation button[data-type=' + option + ']').parent('li').addClass('selected');

                    // option
                    $('.oneyPopin_option').removeClass('-show');
                    $('.oneyPopin_option[data-type=' + option + ']').addClass('-show');
                },
                open: function () {
                    $('.oneyCta').addClass('-open');
                    $('.oneyPopin').addClass('-open');
                    setTimeout(function () {
                        $('.oneyPopin').addClass('-show');
                    }, 0);
                },
                close: function () {
                    $('.oneyPopin').addClass('-show');
                    $('.oneyPopin').removeClass('-open');
                    setTimeout(function () {
                        $('.oneyCta').removeClass('-open');
                    }, 400);
                },
                hide: function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    __moduleName__Module.oney.popin.close();
                },
                handleProductEvent: function () {
                    $document.on('click', '.product_quantity_down, .product_quantity_up, #attributes a', function () {
                        return __moduleName__Module.oney.load(__moduleName__Module.oney.payment.props.open);
                    });
                    $document.on('change', 'input[name=qty], #attributes', function () {
                        return __moduleName__Module.oney.load(__moduleName__Module.oney.payment.props.open);
                    });
                },
                handleCheckoutEvent: function () {
                    if (typeof updateCartSummary != 'function') {
                        return false;
                    }
                    var original = updateCartSummary;
                    updateCartSummary = function (json) {
                        original.call(this, json);
                        __moduleName__Module.oney.load(__moduleName__Module.oney.payment.props.open);
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

                    if ($('.oneyError').length) {
                        $('.oneyError').stop().fadeOut();
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
                                if ($('.oneyError').length) {
                                    $('.oneyError').html(data.error).fadeIn();
                                } else {
                                    var error_html = '<div class="oneyError">' + data.error + '</div>';
                                    $(error_html).appendTo($('.addresses').find('.row').eq(0));
                                }
                                $('.oneyError').stop().fadeIn();
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
                        .on('click', '.oneyPayment_button', oney_payment.send)
                        .on('click', '.oneyPayment_trigger', oney_payment.trigger);

                    oney_payment.form.init();
                },
                select: function () {
                    $('.oneyOption').removeClass('-selected');
                    var $selected = $('input[name="oney_type"]:checked'),
                        $option = $selected.parents('.oneyOption');
                    $option.addClass('-selected');
                    $('.oneyPayment_button').html($option.find('.oneyOption_title').text());
                    __moduleName__Module.oney.props.type = $selected.val();
                    if ($('.-show').length) {
                        __moduleName__Module.oney.payment.form.close();
                    }
                },
                send: function (event) {
                    event.preventDefault();

                    if ($('.oneyRequired').length) {
                        __moduleName__Module.oney.payment.form.open();
                    } else {
                        __moduleName__Module.payment.send({
                            id_card: 'new_card',
                            is_inst: null,
                            is_oney: __moduleName__Module.oney.props.type,
                        });
                    }
                },
                set: function (content) {
                    if (!$('.oneyPayment').length) {
                        return false;
                    }
                    $('.oneyPayment').replaceWith(content);
                    if ($('input[name="oney_type"]').length) {
                        $('input[name="oney_type"]').eq(0).trigger('click');
                    }

                    if (__moduleName__Module.oney.payment.props.open) {
                        setTimeout(__moduleName__Module.oney.payment.open, 0);
                    }

                    $(window).trigger('resize');
                },
                trigger: function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    if ($('.oneyPayment').is('.-disabled')) {
                        return false;
                    }

                    var oney = __moduleName__Module.oney,
                        payment = oney.payment;

                    if (!oney.props.loaded) {
                        oney.load(true);
                    }

                    if (__moduleName__Module.oney.payment.props.open) {
                        payment.close();
                    } else {
                        payment.open();
                    }
                },
                open: function () {
                    $('.oneyPayment').addClass('-open');
                    if (!__moduleName__Module.oney.payment.props.open) {
                        __moduleName__Module.oney.payment.props.open = true;
                        var oney_position = parseInt($('.oneyPayment').offset().top) - 15;
                        $('html,body').stop().animate({'scrollTop': oney_position});
                    }
                },
                close: function () {
                    __moduleName__Module.oney.payment.props.open = false;
                    $('.oneyPayment').removeClass('-open');
                },
                form: {
                    init: function () {
                        var form = this;
                        $document.on('click', '.oneyRequired_close', form.close)
                            .on('click', '.oneyRequired_submit', form.submit)
                            .on('click', '.-validate', form.submit)
                            .on('submit', '.oneyRequired', form.submit)
                            .on('keyup focusout', '.oneyRequired input', form.check);
                    },
                    open: function () {
                        var is_mobile = $('.oneyRequired_overlay:visible').length;
                        $('.oneyPayment_button').addClass('-disabled');
                        $('.oneyRequired_overlay').removeClass('-disabled');

                        if (is_mobile) {
                            var oney_position = parseInt($('.oneyRequired_overlay').offset().top) - 15;
                            $('html,body').stop().animate({'scrollTop': oney_position});
                        }

                        setTimeout(function () {
                            $('.oneyRequired_overlay').addClass('-show');
                        }, 0);
                    },
                    reset: function () {
                        $('.oneyRequired').find('input').each(function () {
                            var $field = $(this);
                            $field.val('');

                            if ($field.is('.-tocheck')) {
                                $field.addClass('-error');
                            }
                        });
                    },
                    close: function () {
                        __moduleName__Module.popup.close();
                        $('.oneyPayment_button').removeClass('-disabled').removeClass('-validate');
                        $('.oneyRequired_overlay').removeClass('-show');
                        setTimeout(function () {
                            $('.oneyRequired_overlay').addClass('-disabled');
                            __moduleName__Module.oney.payment.form.reset();
                        }, 0);
                    },
                    check: function () {
                        var is_valid = true,
                            $fields = $('.oneyRequired_input');

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
                            $('.oneyPayment_button').removeClass('-disabled').addClass('-validate');
                        } else {
                            $('.oneyPayment_button').addClass('-disabled').removeClass('-validate');
                        }
                    },
                    save: function (payment_data) {
                        var data = {
                            _ajax: 1,
                            savePaymentData: 1,
                            payment_data: payment_data
                        };

                        $('.oneyRequired_message').removeClass('-success').removeClass('-error');

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
                                    $('.oneyRequired_validation').addClass('-show');
                                    window.setTimeout(function () {
                                        $('.oneyRequired_validation').addClass('-appear');
                                    });
                                    window.setTimeout(function () {
                                        __moduleName__Module.popup.close();
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

                                    $('.oneyRequired_message').addClass('-error').html(errors);
                                }
                            }
                        });
                    },
                    submit: function (event) {
                        event.preventDefault();
                        event.stopPropagation();

                        var payment_data = {
                                is_oney: __moduleName__Module.oney.props.type,
                                oney_form: {}
                            },
                            $form = $('.oneyRequired'),
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


                        if ($('.oneyRequired').parents('.payplugPopin').length) {
                            return __moduleName__Module.oney.payment.form.save(payment_data.oney_form);
                        }

                        __moduleName__Module.payment.send(payment_data);
                    },
                },
            },
            setCheckout: function () {
                var $oney_cta = $('.oneyCta'),
                    $total_price = $('#total_price_container'),
                    $tr = $total_price.parents('tr').eq(0),
                    $table = $total_price.parents('table').eq(0),
                    colspan = 0;

                $tr.find('td').each(function () {
                    var $td = $(this);
                    var cs = $td.attr('colspan') ? parseInt($td.attr('colspan')) : 1;
                    colspan += cs;
                });

                var oney_table = '<tr class="oneyCta_row">' +
                        '<td class="oneyCta_field" colspan="' + colspan + '">' +
                            '<div class="oneyCta_wrapper"></div>' +
                        '</td>' +
                    '</tr>';

                var $cart_voucher = $table.find('#cart_voucher');

                if ($cart_voucher.length) {
                    var rs = parseInt($cart_voucher.attr('rowspan'));
                    $cart_voucher.attr('rowspan', rs + 1);
                }


                $(oney_table).insertAfter($tr);
                $oney_cta.appendTo($table.find('.oneyCta_wrapper'));
            },
            sizing: function () {
                var container = $('.oneyPayment'),
                    sizes = __moduleName__Module.oney.props.sizes,
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
                var popup = __moduleName__Module.popup,
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
                var props = __moduleName__Module.popup.props;
                var popin = $('.' + props.mainClass);
                popin.addClass('-open');
                window.setTimeout(function () {
                    popin.addClass('-show');
                }, 0);
            },
            close: function () {
                var props = __moduleName__Module.popup.props;
                var popin = $('.' + props.mainClass);

                popin.removeClass('-show');
                window.setTimeout(function () {
                    popin.removeClass('-open');
                }, 500);
            },
            create: function () {
                var props = __moduleName__Module.popup.props,
                    html = '<div class="' + props.mainClass + '"><button class="' + props.mainClass + '_close"></button><div class="' + props.mainClass + '_content"></div></div>';
                $('body').append(html);
            },
            hydrate: function (content) {
                var props = __moduleName__Module.popup.props;
                $('.' + props.mainClass + '_content').html(content);
            }
        }
    };
$(document).ready(function () {
    $document = $(document);
    $window = $(window);
    __moduleName__Module.init();
});

// })(window.jQuery);
