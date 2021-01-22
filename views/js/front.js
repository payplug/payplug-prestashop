/**
 * 2013 - 2021 PayPlug SAS
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
 *  @copyright 2013 - 2021 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */
var allow_debug = true, debug = function (str) {
    if (allow_debug) {
        console.log(str);
    }
};
var $document, $window, payplugModule = {
    init: function () {
        this.card.init();
        this.order.init();
        this.oney.init();
        this.popup.init();
    },
    order: {
        init: function () {
            // Styling
            var $options = $('input[data-module-name="payplug"]');
            $options.parents('.payment-option').addClass('payplugPaymentOption')

            this.checkErrors();

            $document.on('click', '.payplugMsg_button', payplugModule.popup.close);
        },
        checkErrors: function () {
            if (typeof payment_errors == 'undefined' || !payment_errors) {
                return;
            }

            var data = {_ajax: 1, getPaymentErrors: 1};

            $.ajax({
                url: payplug_ajax_url + '?rand=' + new Date().getTime(),
                headers: {"cache-control": "no-cache"},
                type: 'POST',
                async: true,
                cache: false,
                dataType: 'json',
                data: data,
                success: function (data) {
                    if (data.result) {
                        payplugModule.popup.set(data.template);

                        // Select Oney Option
                        var $required = $('.' + payplugModule.oney.required.props.identifier);
                        if ($required.length) {
                            var oney_type = $required.data('oney_type'),
                                paymentOption = $('input[value="' + oney_type + '"]')
                                    .parent('form')
                                    .find('button[type=submit]')
                                    .attr('id')
                                    .replace('pay-with-', '');
                            $('#' + paymentOption).trigger('click');
                        }
                    }
                }
            });
        },
    },
    card: {
        props: {
            identifier: 'payplugCard',
            query: null,
        },
        init: function () {
            var card = payplugModule.card,
                identifier = card.props.identifier;
            $document.on('click', '.' + identifier + '_delete', payplugModule.card.delete);
        },
        delete: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var $elem = $(this),
                id_card = $elem.data('id_card'),
                url = $(this).attr('href') + '&pc=' + id_card,
                card = payplugModule.card,
                identifier = card.props.identifier;

            $.ajax({
                type: 'POST',
                url: url,
                dataType: 'json',
                data: {
                    delete: 1,
                    pc: id_card
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('error CALL DELETE CARD');
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                },
                success: function (result) {
                    if (result) {
                        $('.' + identifier + '[data-id_card=' + id_card + ']').remove();
                        payplugModule.popup.set(card_deleted_msg);
                    }
                }
            });
        },
    },
    oney: {
        props: {
            query: null,
            sizes: [
                {format: 'mobile', limit: 735},
                {format: 'desktop', limit: 9999},
            ]
        },
        clear: function () {
            for (i = 0; i < payplugModule.oney.props.queries.length; i++) {
                if (typeof payplugModule.oney.props.queries[i] != 'undefined')
                    payplugModule.oney.props.queries[i].abort();
            }
            payplugModule.oney.props.queries = [];
        },
        init: function () {
            if (typeof payplug_oney == 'undefined' || !payplug_oney) {
                return;
            }
            var oney = payplugModule.oney;

            this.cta.init();
            this.required.init();

            $window.on('load', function () {
                oney.load();
            });

            var popin = oney.cta.popin;
            prestashop.on('updatedCart', popin.check).on('updatedProduct', popin.check);
        },
        load: function (with_schedule) {
            var oney = payplugModule.oney,
                data = {
                    _ajax: 1,
                };

            if (with_schedule) {
                data['getOneyPriceAndPaymentOptions'] = 1;
            } else {
                data['isOneyElligible'] = 1;
            }

            // check if context is product page
            if ($('#product_page_product_id').length) {
                var $product_form = $('#add-to-cart-or-refresh'),
                    form_data = $product_form.serializeArray();
                form_data.map(function (field) {
                    data[field.name] = field.value;
                })
            }

            // clear current query if exists
            if (oney.props.query !== null) {
                oney.props.query.abort();
            }

            oney.cta.popin.reset();

            oney.props.query = $.ajax({
                url: payplug_ajax_url + '?rand=' + new Date().getTime(),
                headers: {"cache-control": "no-cache"},
                type: 'POST',
                async: true,
                cache: false,
                dataType: 'json',
                data: data,
                success: function (data) {
                    if (data.result) {
                        if (typeof data.popin != 'undefined' && data.popin && oney.cta.props.loaded) {
                            oney.cta.popin.hydrate(data.popin);
                            if (typeof data.error != 'undefined' && data.error) {
                                oney.cta.disable();
                            } else {
                                oney.cta.enable();
                            }
                        }
                    } else if (oney.cta.props.loaded) {
                        if (typeof data.popin != 'undefined') {
                            oney.cta.popin.hydrate(data.popin);
                        } else if (typeof data.error != 'undefined') {
                            var popin_error = '<span class="' + oney.cta.popin.props.identifier + '"><p class="' + oney.cta.popin.props.identifier + '_error">' + data.error + '</p></span>'
                            oney.cta.popin.hydrate(popin_error);
                        }
                        oney.cta.disable();
                    }
                }
            });
        },
        loader: {
            props: {
                identifier: 'oneyLoader',
            },
            set: function (target) {
                if (typeof target == 'undefined' || !target) {
                    return;
                }
                var loader = '<span class="' + this.props.identifier + '">' +
                    '<span class="' + this.props.identifier + '_spinner"><span></span></span>' +
                    '<span class="' + this.props.identifier + '_message">' + payplug_oney_loading_msg + ' <i>.</i><i>.</i><i>.</i></span>' +
                    '</span>';
                $(target).html(loader);
            },
        },
        cta: {
            props: {
                identifier: 'oneyCta',
                loaded: false
            },
            init: function () {
                var cta = this;
                cta.props.loaded = true;
                $document.on('click', '.' + cta.props.identifier + '_button', cta.popin.toggle);
                cta.popin.init();
            },
            enable: function () {
                var popin = payplugModule.oney.cta.popin.props.identifier,
                    cta = payplugModule.oney.cta.props.identifier;
                $('.' + cta + '_button').removeClass('-disabled');
                $('.' + popin).removeClass('-error');
            },
            disable: function () {
                var popin = payplugModule.oney.cta.popin.props.identifier,
                    cta = payplugModule.oney.cta.props.identifier;
                $('.' + cta + '_button').addClass('-disabled');
                $('.' + popin).addClass('-error');
            },
            popin: {
                props: {
                    identifier: 'oneyPopin',
                    open: false,
                    loaded: false,
                },
                init: function () {
                    var oney = payplugModule.oney,
                        cta = oney.cta,
                        popin = cta.popin;

                    $document.on('click', '.' + popin.props.identifier + '_close', popin.hide)
                        .on('click', '.' + popin.props.identifier + '_navigation button', popin.select)
                        .on('click', function (event) {
                            var $clicked = $(event.target);
                            if ((!$clicked.is('.' + popin.props.identifier) && !$clicked.parents('.' + popin.props.identifier).length) && $('.' + cta.props.identifier).is('.-open')) {
                                popin.close();
                            }
                        });
                    popin.reset();
                },
                reset: function () {
                    var oney = payplugModule.oney,
                        cta = oney.cta,
                        popin = cta.popin;
                    if (!$('.' + popin.props.identifier).length) {
                        $('.' + cta.props.identifier).append('<span class="' + popin.props.identifier + '" />');
                    }
                    oney.loader.set('.' + popin.props.identifier);
                    $('.' + popin.props.identifier).addClass('-loading');
                },
                hydrate: function (content) {
                    if (typeof content == 'undefined' || !content) {
                        return false;
                    }

                    var oney = payplugModule.oney,
                        popin = oney.cta.popin,
                        identifier = popin.props.identifier,
                        open = popin.props.open;

                    $('.' + identifier).replaceWith(content).removeClass('-loading');
                    oney.props.loaded = true;

                    var $button = $('.' + identifier + '_navigation button').eq(0);
                    popin.choose($button.data('type'));

                    if (open) {
                        setTimeout(popin.open, 0);
                    }
                },
                select: function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    var $button = $(this),
                        $li = $button.parents('li');

                    if ($li.is('.selected')) {
                        return false;
                    }
                    payplugModule.oney.cta.popin.choose($button.data('type'));
                },
                choose: function (option) {
                    var identifier = payplugModule.oney.cta.popin.props.identifier;
                    // nav
                    $('.' + identifier + '_navigation li').removeClass('selected');
                    $('.' + identifier + '_navigation button[data-type=' + option + ']').parent('li').addClass('selected');

                    // option
                    $('.' + identifier + '_option').removeClass('-show');
                    $('.' + identifier + '_option[data-type=' + option + ']').addClass('-show');
                },
                toggle: function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    var popin = payplugModule.oney.cta.popin;

                    var is_open = $('-open').length > 0;
                    if (is_open) {
                        popin.close();
                    } else {
                        popin.open();
                    }
                },
                check: function () {
                    var oney = payplugModule.oney,
                        popin = oney.cta.popin,
                        open = popin.props.open;

                    oney.props.loaded = false;

                    if (open) {
                        popin.open();
                    }
                },
                open: function () {
                    var oney = payplugModule.oney,
                        cta = oney.cta,
                        popin = cta.popin;

                    if (!oney.props.loaded) {
                        oney.load(true);
                    }

                    $('.' + cta.props.identifier).addClass('-open');
                    $('.' + popin.props.identifier).addClass('-open');

                    setTimeout(function () {
                        $('.' + popin.props.identifier).addClass('-show');
                        popin.props.open = true;
                    }, 0);
                },
                close: function () {
                    var oney = payplugModule.oney,
                        cta = oney.cta,
                        popin = cta.popin;

                    $('.' + popin.props.identifier).removeClass('-show');
                    $('.' + popin.props.identifier).removeClass('-open');

                    setTimeout(function () {
                        $('.' + cta.props.identifier).removeClass('-open');
                        popin.props.open = false;
                    }, 0);
                },
                show: function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    payplugModule.oney.cta.popin.open();
                },
                hide: function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    payplugModule.oney.cta.popin.close();
                },
            }
        },
        required: {
            props: {
                identifier: 'oneyRequired'
            },
            init: function () {
                var required = this,
                    identifier = required.props.identifier;
                $document
                    .on('click', '.' + identifier + '_close', required.close)
                    .on('submit', '.' + identifier, required.submit)
                    .on('keyup focusout', '.' + identifier + ' input', required.check);
            },
            check: function () {
                var required = this,
                    identifier = required.props.identifier,
                    is_valid = true,
                    $fields = $('.' + identifier + '_input');

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
            },
            close: function (event) {
                event.preventDefault();
                event.stopPropagation();
                payplugModule.oney.required.reset();
                payplugModule.popup.close();
            },
            reset: function () {
                var required = this,
                    identifier = required.props.identifier;
                $('.' + identifier).find('input').each(function () {
                    var $field = $(this);
                    $field.val('');

                    if ($field.is('.-tocheck')) {
                        $field.addClass('-error');
                    }
                });
            },
            save: function (payment_data) {
                var required = this,
                    identifier = required.props.identifier,
                    data = {
                        _ajax: 1,
                        savePaymentData: 1,
                        payment_data: payment_data
                    };

                $('.' + identifier + '_message').removeClass('-success').removeClass('-error');

                $.ajax({
                    url: payplug_ajax_url + '?rand=' + new Date().getTime(),
                    headers: {"cache-control": "no-cache"},
                    type: 'POST',
                    async: true,
                    cache: false,
                    dataType: 'json',
                    data: data,
                    success: function (data) {
                        if (data.result) {
                            $('.' + identifier + '_validation').addClass('-show');
                            window.setTimeout(function () {
                                $('.' + identifier + '_validation').addClass('-appear');
                            });
                            window.setTimeout(function () {
                                payplugModule.popup.close();
                            }, 5000);
                        } else {
                            var errors = '';
                            for (var error in data.message)
                                if (error !== 'indexOf')
                                    errors += $('<p />').html(data.message[error]).text() + "\n";

                            $('.' + identifier + '_message').addClass('-error').html(errors);
                        }
                    }
                });
            },
            submit: function (event) {
                event.preventDefault();
                event.stopPropagation();

                var payment_data = {},
                    $required = $('.oneyRequired'),
                    $fields = $required.find('input');

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
                        payment_data[name] = value;
                    }
                });

                return payplugModule.oney.required.save(payment_data);
            },
        },
    },
    popup: {
        props: {
            identifier: 'payplugPopin',
        },
        init: function () {
            var popup = this,
                props = popup.props;

            $document.on('click', '.' + props.identifier + '_close', popup.close)
                .on('click', function (event) {
                    var $clicked = $(event.target);
                    if ($clicked.is('.' + props.identifier) && $('.' + props.identifier).is('.-open')) {
                        popup.close();
                    }
                });
        },
        set: function (content) {
            var popup = payplugModule.popup,
                props = popup.props;

            if ($('.' + props.identifier).length) {
                popup.close();
            } else {
                popup.create();
            }
            popup.hydrate(content);
            popup.open();
        },
        open: function () {
            var props = payplugModule.popup.props;
            var popin = $('.' + props.identifier);
            popin.addClass('-open');
            window.setTimeout(function () {
                popin.addClass('-show');
            }, 0);
        },
        close: function () {
            var props = payplugModule.popup.props;
            var popin = $('.' + props.identifier);

            popin.removeClass('-show');
            window.setTimeout(function () {
                popin.removeClass('-open');
            }, 500);
        },
        create: function () {
            var props = payplugModule.popup.props,
                html = '<div class="' + props.identifier + '"><button class="' + props.identifier + '_close"></button><div class="' + props.identifier + '_content"></div></div>';
            $('body').append(html);
        },
        hydrate: function (content) {
            var props = payplugModule.popup.props;
            $('.' + props.identifier + '_content').html(content);
        }
    }
};
$(document).ready(function () {
    $document = $(document);
    $window = $(window);
    payplugModule.init();
});
