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
var allow_debug = true, debug = function (str) {
    if (allow_debug) {
        console.log(str);
    }
};
var $document, $window, payplugModule = {
    init: function () {
        debug('payplug init');
        this.card.init();
        this.order.init();
        this.oney.init();
    },
    order: {
        init: function () {
            // Styling
            var $options = $('input[data-module-name="payplug"]');
            $options.each(function () {
                var optionId = $(this).attr('id') + '-additional-information';
                $('#' + optionId).attr('style', 'margin:0;');
            }).parents('.payment-option').addClass('payplug-payment-option')
        }
    },
    card: {
        init: function () {
            $document.on('click', 'a.ppdeletecard', payplugModule.card.delete);
        },
        delete: function (event) {
            event.preventDefault();

            var $elem = $(this),
                id_card = $elem.data('id_card'),
                url = $(this).attr('href') + '&pc=' + id_card;

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
                        $('#id_payplug_card_' + id_card).remove();
                        $('#module-payplug-cards div.message').show();
                        $('#module-payplug-controllers-front-cards div.message').show();
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
            debug('oney init');
            if (typeof payplug_oney == 'undefined' || !payplug_oney) {
                return;
            }
            var oney = payplugModule.oney;

            this.cta.init();

            $window.on('load', oney.load);
            prestashop.on('updatedProduct', oney.load);
        },
        load: function () {
            var oney = payplugModule.oney,
                data = {
                    _ajax: 1,
                    getOneyPriceAndPaymentOptions: 1
                };

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
                            oney.cta.enable();
                        }
                    } else if (oney.cta.props.loaded) {
                        if (typeof data.popin != 'undefined') {
                            payplugModule.oney.cta.popin.hydrate(data.popin);
                        } else if (typeof data.error != 'undefined') {
                            var popin_error = '<span class="' + oney.cta.popin.props.identifier + '"><p class="' + oney.cta.popin.props.identifier + '_error">' + data.error + '</p></span>'
                            payplugModule.oney.cta.popin.hydrate(popin_error);
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
                debug('oney cta init');
                cta.props.loaded = true;
                $document.on('click', '.' + cta.props.identifier + '_button', cta.popin.toggle);
                cta.popin.init();
            },
            enable: function () {
                var popin = payplugModule.oney.cta.popin.props.identifier,
                    cta = payplugModule.oney.cta.props.identifier;
                $('.' + cta + '_button').removeClass(cta + '_button-disabled');
                $('.' + popin).removeClass(popin + '-error');
            },
            disable: function () {
                var popin = payplugModule.oney.cta.popin.props.identifier,
                    cta = payplugModule.oney.cta.props.identifier;
                $('.' + cta + '_button').addClass(cta + '_button-disabled');
                $('.' + popin).addClass(popin + '-error');
            },
            popin: {
                props: {
                    identifier: 'oneyPopin',
                },
                init: function () {
                    var popin = payplugModule.oney.cta.popin.props.identifier,
                        cta = payplugModule.oney.cta.popin.props.identifier;
                    $document.on('click', '.' + popin + '_close', payplugModule.oney.cta.popin.hide)
                        .on('click', '.' + popin + '_navigation button', payplugModule.oney.cta.popin.select)
                        .on('click', function (event) {
                            var $clicked = $(event.target);
                            if ((!$clicked.is('.' + popin) && !$clicked.parents('.' + popin).length) && $('.' + cta).is('.' + cta + '-open')) {
                                payplugModule.oney.cta.popin.close();
                            }
                        });
                },
                reset: function () {
                    var oney = payplugModule.oney,
                        cta = oney.cta,
                        popin = cta.popin;
                    if (!$('.' + popin.props.identifier).length) {
                        $('.' + cta.props.identifier).append('<span class="' + popin.props.identifier + '" />');
                    }
                    oney.loader.set('.' + popin.props.identifier);
                    $('.' + popin.props.identifier).addClass(popin.props.identifier + '-loading');
                },
                hydrate: function (content) {
                    if (typeof content == 'undefined' || !content) {
                        return false;
                    }
                    var identifier = payplugModule.oney.cta.popin.props.identifier,
                        is_open = $('.' + identifier + '-open').length > 0;
                    $('.' + identifier).replaceWith(content).removeClass(identifier + '-loading');

                    var $button = $('.' + payplugModule.oney.cta.popin.props.identifier + '_navigation button').eq(0);
                    payplugModule.oney.cta.popin.choose($button.data('type'));

                    if (is_open) {
                        setTimeout(payplugModule.oney.cta.popin.open, 0);
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
                    $('.' + identifier + '_option').removeClass(identifier + '_option-show');
                    $('.' + identifier + '_option[data-type=' + option + ']').addClass(identifier + '_option-show');
                },
                toggle: function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    var popin = payplugModule.oney.cta.popin,
                        identifier = popin.props.identifier;

                    if (!$('.' + identifier).length) {
                        popin.reset();
                        payplugModule.oney.load();
                    }
                    var is_open = $('.' + identifier + '-open').length > 0;
                    if (is_open) {
                        popin.close();
                    } else {
                        popin.open();
                    }
                },
                open: function () {
                    var cta = payplugModule.oney.cta.props.identifier,
                        popin = payplugModule.oney.cta.popin.props.identifier;
                    $('.' + cta).addClass(cta + '-open');
                    $('.' + popin).addClass(popin + '-open');
                    setTimeout(function () {
                        $('.' + popin).addClass(popin + '-show');
                    }, 0);
                },
                close: function () {
                    var cta = payplugModule.oney.cta.props.identifier,
                        popin = payplugModule.oney.cta.popin.props.identifier;
                    $('.' + popin).addClass(popin + '-show');
                    $('.' + popin).removeClass(popin + '-open');
                    setTimeout(function () {
                        $('.' + cta).removeClass(cta + '-open');
                    }, 400);
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
        address: {
            init: function () {
                // editAddress
            }
        }
    },
};
$(document).ready(function () {
    $document = $(document);
    $window = $(window);
    payplugModule.init();
});
