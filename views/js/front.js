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
var $document, payplugModule = {
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
            classes: {
                build: 'oneyCTA-builder',
                button: 'oneyCta_button',
            },
        },
        init: function () {
            debug('oney init');
            if(typeof payplug_oney == 'undefined' || !payplug_oney) {
                return;
            }

            this.product.init();
            this.checkout.init();
            this.cta.init();
        },
        product: {
            init: function () {
                debug('oney product init');
                if(!$('.product-prices').length) {
                    return;
                }
                this.set();
            },
            set: function () {
                $('.product-prices').append('<span class="' + payplugModule.oney.props.classes.build + '" />')
            }
        },
        checkout: {
            init: function () {
                debug('oney checkout init');
                if(!$('.cart-detailed-totals').length) {
                    return;
                }
                this.set();
            },
            set: function () {
                $('.cart-detailed-totals').append('<span class="' + payplugModule.oney.props.classes.build + '" />')
            }
        },
        cta: {
            init: function () {
                debug('oney cta init');
                if($('.' + payplugModule.oney.props.classes.build).length) {
                    this.get();
                }
            },
            get: function(){
                debug('oney cta get');
                $.ajax({
                    type: 'POST',
                    url: payplug_ajax_url,
                    dataType: 'json',
                    data: {
                        _ajax: 1,
                        getOneyCta: 1,
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.log(jqXHR);
                        console.log(textStatus);
                        console.log(errorThrown);
                    },
                    success: function (data) {
                        if(data.result) {
                            $('.' + payplugModule.oney.props.classes.build).replaceWith(data.tpl);
                            payplugModule.oney.cta.load();
                            payplugModule.oney.popin.init();
                        }
                    }
                });
            },
            load: function () {
                debug('oney cta load');
                var oney = payplugModule.oney,
                    data = {
                        _ajax: 1,
                        getOneyPriceAndPaymentOptions: 1
                    };

                // check if context is product page
                // if ($('#product_page_product_id').length) {
                //     var $product_form = $('#add-to-cart-or-refresh'),
                //         from_data = $product_form.serializeArray();
                //     from_data.map(function(field){
                //         data[field.name] = field.value;
                //     })
                // }

                oney.popin.setLoader();

                $.ajax({
                    url: payplug_ajax_url + '?rand=' + new Date().getTime(),
                    headers: {"cache-control": "no-cache"},
                    type: 'POST',
                    async: true,
                    cache: false,
                    dataType: 'json',
                    data: data,
                    success: function (data) {
                        if(data.result) {
                            payplugModule.oney.popin.set(data.popin);
                        } else {
                            if (typeof data.popin != 'undefined') {
                                payplugModule.oney.popin.set(data.popin);
                            } else if (typeof data.error != 'undefined') {
                                var popin_error = '<span class="oneyPopin"><p class="oneyPopin_error">' + data.error + '</p></span>'
                                payplugModule.oney.popin.set(popin_error);
                            }

                            payplugModule.oney.popin.disable();
                        }
                    }
                });
            },
        },
        loader: {
            set: function (target) {
                var popin = '<span class="oneyLoader">' +
                    '<span class="oneyLoader_spinner"><span></span></span>' +
                    '<span class="oneyLoader_message">' + payplug_oney_loading_msg + ' <i>.</i><i>.</i><i>.</i></span>' +
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
                    if ((!$clicked.is('.oneyPopin') && !$clicked.parents('.oneyPopin').length) && $('.oneyCta').is('.oneyCta-open')) {
                        popin.close();
                    }
                });
            },
            set: function (content) {
                if (!$('.oneyCta').length) {
                    return false;
                }
                var is_open = $('.oneyCta').is('.oneyCta-open');
                $('.oneyPopin').replaceWith(content).removeClass('oneyPopin-loading');

                var $button = $('.oneyPopin_navigation button').eq(0);
                payplugModule.oney.popin.choose($button.data('type'));

                if (is_open) {
                    setTimeout(payplugModule.oney.popin.open, 0);
                }
            },
            setLoader: function () {
                var target = '.oneyPopin';
                if (!$(target).length) {
                    $('.oneyCta').append('<span class="oneyPopin" />');
                }
                payplugModule.oney.loader.set(target);
                $(target).addClass('oneyPopin-loading');
            },
            toggle: function (event) {
                event.preventDefault();
                event.stopPropagation();
                var popin = payplugModule.oney.popin,
                    is_active = $('.oneyCta').is('.oneyCta-open');
                if (is_active) {
                    popin.close();
                } else {
                    popin.open();
                }
            },
            enable: function () {
                $('.oneyCta_button').removeClass('oneyCta_button-disabled');
                $('.oneyPopin').removeClass('oneyPopin-error');
            },
            disable: function () {
                $('.oneyCta_button').addClass('oneyCta_button-disabled');
                $('.oneyPopin').addClass('oneyPopin-error');
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
                $('.oneyPopin_navigation li').removeClass('selected');
                $('.oneyPopin_navigation button[data-type=' + option + ']').parent('li').addClass('selected');

                // option
                $('.oneyPopin_option').removeClass('oneyPopin_option-show');
                $('.oneyPopin_option[data-type=' + option + ']').addClass('oneyPopin_option-show');
            },
            open: function () {
                $('.oneyCta').addClass('oneyCta-open');
                $('.oneyPopin').addClass('oneyPopin-open');

                setTimeout(function () {
                    $('.oneyPopin').addClass('oneyPopin-show');
                }, 0);
            },
            close: function () {
                $('.oneyPopin').addClass('oneyPopin-show');
                $('.oneyPopin').removeClass('oneyPopin-open');

                setTimeout(function () {
                    $('.oneyCta').removeClass('oneyCta-open');
                }, 400);
            },
            hide: function (event) {
                event.preventDefault();
                event.stopPropagation();
                payplugModule.oney.popin.close();
            },
        },
    },
};
$(document).ready(function () {
    $document = $(document);
    payplugModule.init();
});
