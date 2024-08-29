/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS
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
 *  @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */
var allow_debug = true, debug = function (str) {
    if (allow_debug) {
        console.log(str);
    }
};
let ipCDNCountdown = 0;
var $document, $window, __moduleName__Module = {
    init: function () {
        this.card.init();
        this.order.init();
        this.oney.init();
        this.popup.init();
        this.integrated.init();
    },
    order: {
        init: function () {
            // Styling
            var $options = $('input[data-module-name="__moduleName__"]');
            $options.parents('.payment-option').addClass('__moduleName__PaymentOption')
            $options.each(function () {
                var $form = $('#pay-with-' + this.id + '-form').find('form');
                if ($form.find('input[name=method]').val() == "oney") {
                    if ($form.find('input[name=__moduleName__Oney_type]').val().includes("without_fees")) {
                        $('#' + this.id + '-container').addClass('without_fees');
                    } else if ($form.find('input[name=__moduleName__Oney_type]').val().includes("with_fees")) {
                        $('#' + this.id + '-container').addClass('with_fees');
                    }
                }
            })

            this.checkErrors();

            $document.on('click', '.__moduleName__Msg_button', __moduleName__Module.popup.close);
            $document.on('click', '.__moduleName__Msg_declineButton', __moduleName__Module.popup.close);
        },
        checkErrors: function () {
            if (typeof check_errors == 'undefined' || !check_errors) {
                return;
            }

            var data = {_ajax: 1, getPaymentErrors: 1};

            $.ajax({
                url: window['__moduleName___ajax_url'] + '?rand=' + new Date().getTime(),
                headers: {"cache-control": "no-cache"},
                type: 'POST',
                async: true,
                cache: false,
                dataType: 'json',
                data: data,
                success: function (data) {
                    if (data.result) {
                        __moduleName__Module.popup.set(data.template);

                        // Select Oney Option
                        var $required = $('.' + __moduleName__Module.oney.required.props.identifier);
                        if ($required.length) {
                            $oneyType = data.errors[0].replace('oney_required_field_', '');
                            var paymentOption = $('input[value=' + $oneyType + ']')
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
    integrated: {
        props: {
            identifier: '__moduleName__IntegratedPayment',
            cartId: null,
            paymentId: null,
            paymentOptionId: null,
            form: {},
            checkoutForm: null,
            api: null,
            integratedPayment: null,
            token: null,
            notValid: false,
            fieldsInvalid: {
                cardHolder: true,
                pan: true,
                cvv: true,
                exp: true,
            },
            fieldsEmpty: {
                cardHolder: true,
                pan: true,
                cvv: true,
                exp: true,
            },
            save_card: false,
            scheme: null,
            query: null,
            submit: null,
        },
        init: function () {
            var integrated = __moduleName__Module.integrated,
                $integratedForm = $('.' + integrated.props.identifier);
            if ($integratedForm.length) {
                var $methodInput = document.querySelectorAll('input[name=method][value=integrated]').item(0).parentNode,
                    payment_option = ($methodInput.childNodes)[3];
                integrated.props.paymentOptionId = payment_option.id.replace('pay-with-', '');
                integrated.form.init();
            } else {
                return false;
            }
        },
        form: {
            init: function () {
                var integrated = __moduleName__Module.integrated,
                    payment_option_id = integrated.props.paymentOptionId;

                if (typeof $document == 'undefined') {
                    return false;
                }

                if ($('#' + payment_option_id).attr('checked') == 'checked') {
                    integrated.form.set();
                }

                $document.on('click', '#' + payment_option_id, integrated.form.set);
            },
            clear: function (clear) {
                // confirm creation integrated paiement or show fail popup
                var integrated = __moduleName__Module.integrated;
                integrated.props.submited = false;

                if (clear) {
                    form.cardHolder.clear();
                    form.pan.clear();
                    form.cvv.clear();
                    form.exp.clear();
                    $('.' + integrated.props.identifier + '_container.-saveCard')
                        .removeClass('-checked')
                        .find('input')
                        .prop('checked', false);
                }

                // unchecked tos
                $('input[name="conditions_to_approve[terms-and-conditions]"]').prop('checked', false);
            },
            confirm: function (token) {
                __moduleName__Module.tools.loadSpinner();
                // confirm creation integrated paiement or show fail popup
                var integrated = __moduleName__Module.integrated;
                if (integrated.props.query != null) {
                    integrated.props.query.abort();
                    integrated.props.query = null;
                }

                integrated.props.query = $.ajax({
                    type: 'POST',
                    url: window['__moduleName___ajax_url'],
                    dataType: 'json',
                    data: {
                        _ajax: 1,
                        confirmPayment: 1,
                        cart_id: integrated.props.cart_id,
                        pay_id: token,
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.log(jqXHR, textStatus, errorThrown);
                        integrated.form.clear();
                    },
                    success: function (data) {
                        __moduleName__Module.tools.removeSpinner();
                        if (data.result) {
                            window.location.href = data.return_url;
                        } else {
                            $('.' + integrated.props.identifier + '_error.-payment')
                                .text(integratedPaymentError)
                                .addClass('-show');
                            integrated.form.clear(true);
                            return false;
                        }
                    },
                });
            },
            field: {
                init: function () {
                    var integrated = __moduleName__Module.integrated,
                        field = integrated.form.field,
                        form = integrated.props.form;

                    form.cardHolder.onChange(function (event) {
                        if (!event.valid) {
                            field.error('cardHolder');
                            integrated.props.fieldsEmpty['cardHolder'] = 'FIELD_EMPTY' == event.error.name;
                            integrated.props.fieldsInvalid['cardHolder'] = 'INVALID_CARDHOLDER' == event.error.name;
                        } else {
                            field.valid('cardHolder');
                            integrated.props.fieldsEmpty['cardHolder'] = false;
                            integrated.props.fieldsInvalid['cardHolder'] = false;
                        }
                    });
                    form.pan.onChange(function (event) {
                        if (!event.valid) {
                            field.error('pan');
                            integrated.props.fieldsEmpty['pan'] = 'FIELD_EMPTY' == event.error.name;
                            integrated.props.fieldsInvalid['pan'] = 'INVALID_CARD_NUMBER' == event.error.name;
                        } else {
                            field.valid('pan');
                            integrated.props.fieldsEmpty['pan'] = false;
                            integrated.props.fieldsInvalid['pan'] = false;
                        }
                    });
                    form.cvv.onChange(function (event) {
                        if (!event.valid) {
                            field.error('cvv');
                            integrated.props.fieldsEmpty['cvv'] = 'FIELD_EMPTY' == event.error.name;
                            integrated.props.fieldsInvalid['cvv'] = 'INVALID_CVV' == event.error.name;
                        } else {
                            field.valid('cvv');
                            integrated.props.fieldsEmpty['cvv'] = false;
                            integrated.props.fieldsInvalid['cvv'] = false;
                        }
                    });
                    form.exp.onChange(function (event) {
                        if (!event.valid) {
                            field.error('exp');
                            integrated.props.fieldsEmpty['exp'] = 'FIELD_EMPTY' == event.error.name;
                            integrated.props.fieldsInvalid['exp'] = 'INVALID_EXPIRATION_DATE' == event.error.name;
                        } else {
                            field.valid('exp');
                            integrated.props.fieldsEmpty['exp'] = false;
                            integrated.props.fieldsInvalid['exp'] = false;
                        }
                    });

                    form.cardHolder.onFocus(function (event) {
                        field.focus('cardHolder');
                    });
                    form.pan.onFocus(function () {
                        field.focus('pan');
                    });
                    form.cvv.onFocus(function () {
                        field.focus('cvv');
                    });
                    form.exp.onFocus(function () {
                        field.focus('exp');
                    });

                    form.cardHolder.onBlur(function (event) {
                        field.blur('cardHolder');
                    });
                    form.pan.onBlur(function () {
                        field.blur('pan');
                    });
                    form.cvv.onBlur(function () {
                        field.blur('cvv');
                    });
                    form.exp.onBlur(function () {
                        field.blur('exp');
                    });
                },
                error: function (type) {
                    if (!type || typeof type == undefined) {
                        return false;
                    }
                    var integrated = __moduleName__Module.integrated;
                    $('.' + integrated.props.identifier + '_error.-' + type + ' span.invalidField').removeClass('-hide');
                    $('.' + integrated.props.identifier + '_container.-' + type).addClass('-invalid');
                },
                blur: function (type) {
                    if (!type || typeof type == undefined) {
                        return false;
                    }
                    var integrated = __moduleName__Module.integrated;
                    $('.' + integrated.props.identifier + '_container.-' + type).removeClass('-focus');
                    if ($('.' + integrated.props.identifier + '_container.-' + type).is('.integrated_payment_error')) {
                        integrated.form.field.error(type);
                    }
                },
                focus: function (type) {
                    if (!type || typeof type == undefined) {
                        return false;
                    }
                    var integrated = __moduleName__Module.integrated;
                    $('.' + integrated.props.identifier + '_container.-' + type).addClass('-focus').removeClass('-invalid');
                    $('.' + integrated.props.identifier + '_error.-' + type + ' span.emptyField').addClass('-hide');
                    $('.' + integrated.props.identifier + '_error.-' + type + ' span.invalidField').addClass('-hide');
                    $('.' + integrated.props.identifier + '_error.-fields').removeClass('-show');
                    $('.' + integrated.props.identifier + '_error.-api').removeClass('-show');
                },
                valid: function (type) {
                    if (!type || typeof type == undefined) {
                        return false;
                    }
                    var integrated = __moduleName__Module.integrated;
                    $('.' + integrated.props.identifier + '_error.-' + type + ' span.invalidField').addClass('-hide');
                    $('.' + integrated.props.identifier + '_container.-' + type + ' span.invalidField').removeClass('-invalid');
                },
            },
            getPaymentId: function (event) {
                //create integrated payment id
                var integrated = __moduleName__Module.integrated;
                if (typeof event != 'undefined') {
                    event.preventDefault();
                    event.stopPropagation();
                }

                if (integrated.props.submited) {
                    return;
                }
                integrated.props.submited = true;

                integratedPayment = integrated.props.integratedPayment;

                token = integratedPayment.token;
                if (integrated.props.query != null) {
                    integrated.props.query.abort();
                    integrated.props.query = null;
                }

                $('.' + integrated.props.identifier + '_error.-payment').removeClass('-show');
                $('.' + integrated.props.identifier + '_error.-api').removeClass('-show');

                integrated.props.query = $.ajax({
                    type: 'POST',
                    url: window['__moduleName___ajax_url'],
                    dataType: 'json',
                    data: {
                        _ajax: 1,
                        createIP: 1,
                        token: token,
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        integrated.form.clear();
                        console.log(jqXHR, textStatus, errorThrown);
                    },
                    success: function (result) {
                        if (result && result.resource_id) {
                            integrated.props.paymentId = result.resource_id;
                            integrated.props.cart_id = result.cart_id;
                            integrated.form.submit();
                        } else if (typeof result.force_reload != 'undefined' && result.force_reload) {
                            window.location.href = result.return_url;
                        } else {
                            __moduleName__Module.popup.set(integratedPaymentError);
                            integrated.form.clear();
                            return false;
                        }
                    },
                });
            },
            reset: function () {
                // confirm creation integrated paiement or show fail popup
                var integrated = __moduleName__Module.integrated,
                    $form = $('.' + integrated.props.identifier),
                    $cardHolder = $form.find('#cardholder'),
                    $pan = $form.find('#pan'),
                    $cvv = $form.find('#cvv'),
                    $exp = $form.find('#exp');

                integrated.form.clear();

                $cardHolder.remove();
                $pan.remove();
                $cvv.remove();
                $exp.remove();
                $form.removeClass('-loaded');

                // unchecked tos
                $('input[name="conditions_to_approve[terms-and-conditions]"]').prop('checked', false);

                try {
                    integrated.form.set();
                } catch (e) {
                    // @todo find a solution if an error block IP form display
                    if (typeof e.name != 'undefined' && typeof e.message != 'undefined') {
                        addLogger(e.name + " : " + e.message);
                    } else {
                        addLogger("UNKNOWN_ERROR: unable to generate IP form");
                    }
                }
            },
            set: function () {
                var integrated = __moduleName__Module.integrated;

                if (typeof Payplug == 'undefined') {
                    if (ipCDNCountdown < 10) {
                        $('input[name="conditions_to_approve[terms-and-conditions]"]').prop('checked', false);
                        console.log('Waiting for Integrated payment form to load');
                        ipCDNCountdown++;
                        setTimeout(function () {
                            integrated.form.set();
                        }, 1000);
                    }
                    return;
                }

                integrated.props.api = Payplug;

                var api = integrated.props.api,
                    $form = $('.' + integrated.props.identifier),
                    $scheme = $form.find('.-scheme'),
                    $cardHolder = $form.find('.' + integrated.props.identifier + '_container.-cardHolder'),
                    $pan = $form.find('.' + integrated.props.identifier + '_container.-pan'),
                    $cvv = $form.find('.' + integrated.props.identifier + '_container.-cvv'),
                    $exp = $form.find('.' + integrated.props.identifier + '_container.-exp'),
                    $saveCard = $form.find('.-saveCard'),
                    payment_option_id = integrated.props.paymentOptionId;

                // check if form already exists
                if ($form.is('.-loaded')) {
                    return;
                }

                try {
                    var integratedPayment = new Payplug.IntegratedPayment(is_sandbox_mode);
                } catch (e) {
                    if (typeof e.name != 'undefined' && typeof e.message != 'undefined') {
                        addLogger(e.name + " : " + e.message);
                    }
                }

                integrated.props.integratedPayment = integratedPayment;
                integratedPayment.setDisplayMode3ds(api.DisplayMode3ds.LIGHTBOX);

                var input_style = {
                    default: {
                        color: '#2B343D',
                        fontFamily: 'Poppins, sans-serif',
                        fontSize: '14px',
                        textAlign: 'left',
                        '::placeholder': {
                            color: '#969a9f',
                        },
                        ':focus': {
                            color: '#2B343D',
                        },
                    },
                    invalid: {
                        color: '#E91932'
                    },
                };

                form = {
                    integratedPayment: integratedPayment,
                    cardHolder: integratedPayment.cardHolder(
                        $cardHolder.get(0),
                        {
                            placeholder: placeholderCardholder,
                            default: input_style.default,
                            invalid: input_style.invalid
                        }
                    ),
                    pan: integratedPayment.cardNumber(
                        $pan.get(0),
                        {
                            placeholder: placeholderPan,
                            default: input_style.default,
                            invalid: input_style.invalid
                        }
                    ),
                    cvv: integratedPayment.cvv(
                        $cvv.get(0),
                        {
                            placeholder: placeholderCvv,
                            default: input_style.default,
                            invalid: input_style.invalid
                        }
                    ),
                    exp: integratedPayment.expiration(
                        $exp.get(0),
                        {
                            placeholder: placeholderExp,
                            default: input_style.default,
                            invalid: input_style.invalid
                        }
                    ),
                };

                $form.addClass('-loaded');

                $cardHolder.on('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    form.cardHolder.focus();
                });
                $scheme.find('input').on('click', function (event) {
                    integrated.props.scheme = $(this).val();
                });
                $pan.on('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    form.pan.focus();
                });
                $exp.on('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    form.exp.focus();
                });
                $cvv.on('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    form.cvv.focus();
                });
                $saveCard.find('input').on('change', function () {
                    if ($(this).prop('checked')) {
                        integrated.props.save_card = true;
                        $saveCard.addClass('-checked');
                    } else {
                        integrated.props.save_card = false;
                        $saveCard.removeClass('-checked');
                    }
                });

                integrated.props.form = form;
                // defined all event on form field
                integrated.form.field.init();

                // Once an attempt has been made
                integratedPayment.onCompleted(function (event) {
                    if (typeof event.error != 'undefined' && event.error != null) {
                        integrated.form.clear(true);

                        if (!event.error.hasOwnProperty('name')) {
                            event.error.name = 'API_ERROR';
                        }
                        if (!event.error.hasOwnProperty('message')) {
                            event.error.message = 'A generic error occured';
                        }

                        addLogger(event.error.name + " : " + event.error.message);
                        $('.' + integrated.props.identifier + '_error.-api').addClass('-show');
                        integrated.form.reset();
                    } else {
                        integrated.form.confirm(event.token);
                    }
                });

                // Listen to the validateForm Event
                integratedPayment.onValidateForm(function ({isFormValid}) {
                    if (isFormValid) {
                        integrated.form.getPaymentId();
                    } else {
                        integrated.props.submited = false;
                        integrated.form.showError();
                    }
                });

                $document.on('submit', 'form', integrated.form.validate);
            },
            showError: function () {
                // valide integrated payment form
                var integrated = __moduleName__Module.integrated;

                $('input[name="conditions_to_approve[terms-and-conditions]"]').prop('checked', false);

                // Check if field is empty
                for (var key in integrated.props.fieldsEmpty) {
                    if (integrated.props.fieldsEmpty[key]) {
                        $('.' + integrated.props.identifier + '_error.-' + key + ' span.invalidField').addClass('-hide');
                        $('.' + integrated.props.identifier + '_error.-' + key + ' span.emptyField').removeClass('-hide');
                        $('.' + integrated.props.identifier + '_container.-' + key).addClass('-invalid');
                        $('input[name="conditions_to_approve[terms-and-conditions]"]').prop('checked', false);
                    }
                }

                // Check if field is invalid
                for (var key in integrated.props.fieldsInvalid) {
                    if (integrated.props.fieldsInvalid[key]) {
                        $('.' + integrated.props.identifier + '_error.-fields').addClass('-show');
                    }
                }
            },
            submit: function () {
                // create an integrated payment

                var integrated = __moduleName__Module.integrated,
                    paymentId = integrated.props.paymentId,
                    integratedPayment = integrated.props.integratedPayment,
                    integratedPaymentScheme = null;

                switch (integrated.props.scheme) {
                    case 'cb':
                        integratedPaymentScheme = Payplug.Scheme.CARTE_BANCAIRE;
                        break;
                    case 'visa':
                        integratedPaymentScheme = Payplug.Scheme.VISA;
                        break;
                    case 'mastercard':
                        integratedPaymentScheme = Payplug.Scheme.MASTERCARD;
                        break;
                    default:
                        integratedPaymentScheme = Payplug.Scheme.AUTO;
                        break;
                }

                integratedPayment.pay(paymentId, integratedPaymentScheme, {save_card: integrated.props.save_card});
            },
            validate: function (event) {
                var integrated = __moduleName__Module.integrated,
                    payment_option_id = integrated.props.paymentOptionId,
                    isIntegrated = payment_option_id == $('input[name="payment-option"]:checked').attr('id');

                if (!$('#payment-confirmation:visible').length) {
                    return;
                }

                if (typeof event != 'undefined' && isIntegrated) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                if (isIntegrated) {
                    integratedPayment = integrated.props.integratedPayment;
                    integratedPayment.validateForm();
                }
            }
        },
    },
    applepay: {
        props: {
            identifier: '__moduleName__ApplePay',
            query: null,
            workflow: 'checkout',
            session: null,
            datas: null,
            request: null,
            address: {
                locality: null,
                country: null,
                postalCode: null,
                administrativeArea: null,
                countryCode: null
            },
            carrier: {
                amount: null,
                detail: null,
                identifier: null,
                label: null
            },
        },
        set: function (key, value) {
            this.props[key] = value;
        },
        init: function () {
            let {applepay} = __moduleName__Module,
                {workflow} = applepay.props;

            $('apple-pay-button, #payment-confirmation button').click(function (event) {
                $(this).fadeOut(10).fadeIn(10);
                let payment_method_id = $('input[name="payment-option"]:checked').attr('id'),
                    payment_method = $('#pay-with-' + payment_method_id + '-form input[name="method"]').val();

                if ('applepay' == payment_method || 'checkout' != workflow) {
                    event.preventDefault();
                    event.stopPropagation();
                    applepay.trigger();
                }
            });
        },
        trigger: function () {
            let {applepay} = __moduleName__Module,
                {workflow} = applepay.props;

            if (!workflow) {
                return;
            }

            if (applepay.props.session != null) {
                return;
            }

            if (applepay.props.query != null) {
                applepay.props.query.abort();
                applepay.props.query = null;
            }
            let data = {
                workflow: workflow,
            }

            // Check if it's on the product page and add product-related data
            if (workflow === 'product' && $('#product_page_product_id').length && $('#quantity_wanted').length) {
                data.id_product = $('#product_page_product_id').val();
                data.quantity = $('#quantity_wanted').val();
                data.empty_cart = true;
            }
            let applepay_datas;
            applepay.props.query = $.ajax({
                method: "POST",
                url: applePayPaymentRequestAjaxURL,
                async: false,
                data: data,
                success: function (result) {
                    applepay_datas = JSON.parse(result);
                },
                error: function () {
                    __moduleName__Module.applepay.error();
                }
            });

            applepay.create(applepay_datas);
        },
        create: function (data) {
            let {applepay} = __moduleName__Module,
                {workflow} = applepay.props;

            if (!workflow) {
                return;
            }

            if (typeof data.applePayPaymentRequest == 'undefined') {
                return;
            }

            const request = data.applePayPaymentRequest;
            applepay.props.request = request;

            // Define the default carrier
            if (typeof request.shippingMethods != 'undefined'
                && request.shippingMethods.length) {
                applepay.props.carrier = request.shippingMethods[0];
            }

            // Create ApplePaySession
            applepay.props.session = new ApplePaySession(3, request);

            if (applepay.props.query != null) {
                applepay.props.query.abort();
                applepay.props.query = null;
            }

            applepay.props.query = $.ajax({
                method: "POST",
                url: applePayMerchantSessionAjaxURL,
                data: {
                    workflow: workflow,
                    method: 'applepay',
                    id_cart: applePayIdCart
                },
                beforeSend: function () {
                    $('#apple-pay-button').css('pointer-events', 'none');
                },
                success: function (result) {
                    var datas = JSON.parse(result);
                    if (!datas.result) {
                        console.log(datas.error_message);
                        return __moduleName__Module.applepay.error();
                    }

                    applepay.props.datas = datas;
                    applepay.props.session.onvalidatemerchant = applepay.session.validatemerchant;
                    applepay.props.session.onshippingmethodselected = applepay.session.shippingmethodselected;
                    applepay.props.session.onshippingcontactselected = applepay.session.shippingcontactselected;
                    applepay.props.session.onpaymentauthorized = applepay.session.paymentauthorized;
                    applepay.props.session.oncancel = applepay.session.cancel;
                    applepay.props.session.begin();
                },
                error: function () {
                    __moduleName__Module.applepay.error();
                }
            });
        },
        update: function () {
            let {applepay} = __moduleName__Module,
                {workflow, carrier, address} = applepay.props,
                request = null;

            if (applepay.props.query != null) {
                applepay.props.query.abort();
                applepay.props.query = null;
            }
            applepay.props.query = $.ajax({
                method: "POST",
                url: payplug_ajax_url,
                async: false,
                dataType: 'json',
                data: {
                    _ajax: 1,
                    applepayUpdate: 1,
                    workflow: workflow,
                    carrier: carrier,
                    address: address,
                },
                success: function (datas) {
                    if (!datas.result) {
                        return __moduleName__Module.applepay.error();
                    }
                    request = datas.request;
                },
                error: function () {
                    __moduleName__Module.applepay.error();
                }
            });

            return request;
        },
        session: {
            validatemerchant: async () => {
                let {applepay} = __moduleName__Module,
                    {datas, session} = applepay.props;
                try {
                    session.completeMerchantValidation(datas.apiResponse.merchant_session);
                } catch (err) {
                    console.log(err);
                }
            },
            shippingcontactselected: (event) => {
                let {applepay} = __moduleName__Module,
                    {session} = applepay.props,
                    {shippingContact} = event;

                applepay.props.address = {
                    locality: shippingContact.locality,
                    country: shippingContact.country,
                    postalCode: shippingContact.postalCode,
                    administrativeArea: shippingContact.administrativeArea,
                    countryCode: shippingContact.countryCode,
                };

                const request = applepay.update();
                const update = {
                    'newTotal': request.total,
                    'newLineItems': request.lineItems,
                    'newShippingMethods': request.shippingMethods,
                };

                session.completeShippingContactSelection(update);
            },
            shippingmethodselected: (event) => {
                let {applepay} = __moduleName__Module,
                    {session} = applepay.props,
                    {shippingMethod} = event;

                applepay.props.carrier = shippingMethod;

                const request = applepay.update();
                const update = {
                    'newTotal': request.total,
                    'newLineItems': request.lineItems,
                };

                session.completeShippingMethodSelection(update);
            },
            paymentauthorized: (event) => {
                let {applepay} = __moduleName__Module,
                    {session, carrier} = applepay.props,
                    {payment} = event;

                // Define ApplePayPaymentAuthorizationResult
                $.ajax({
                    method: "POST",
                    url: payplug_ajax_url,
                    data: {
                        _ajax: 1,
                        token: payment.token,
                        user: {
                            billing: payment.billingContact,
                            shipping: payment.shippingContact,
                        },
                        carrier: carrier,
                        pay_id: applepay.props.datas.idPayment,
                        patchPayment: 1,
                        workflow: applepay.props.workflow,
                    },
                    success: function (json) {
                        var result = JSON.parse(json);

                        if (!result.result) {
                            session.completePayment({"status": ApplePaySession.STATUS_FAILURE});
                            return __moduleName__Module.applepay.error();
                        }

                        session.completePayment({"status": ApplePaySession.STATUS_SUCCESS});
                        window.location.replace(result.return_url);
                    },
                    error: function () {
                        __moduleName__Module.applepay.error();
                    }
                })
            },
            cancel: (event) => {
                let {applepay} = __moduleName__Module;
                if (applepay.props.query != null) {
                    applepay.props.query.abort();
                    applepay.props.query = null;
                }
                applepay.props.query = $.ajax({
                    method: "POST",
                    url: payplug_ajax_url,
                    async: false,
                    dataType: 'json',
                    data: {
                        _ajax: 1,
                        applepayCancel: 1,
                        workflow: applepay.props.workflow,
                    }
                });
                return __moduleName__Module.applepay.error();
            }
        },
        error: function () {
            let {applepay} = __moduleName__Module;
            $('#apple-pay-button').css('pointer-events', 'auto');
            applepay.props.session = null;
            applepay.props.datas = null;
            __moduleName__Module.popup.set(payplug_transaction_error_message);
        }
    },
    card: {
        props: {
            identifier: '__moduleName__Card',
            query: null,
            id_card: 0,
        },
        init: function () {
            var card = __moduleName__Module.card,
                identifier = card.props.identifier;

            $document.on('click', '.' + identifier + '_delete', __moduleName__Module.card.delete)
                .on('click', 'button[name="__moduleName__ConfirmDelete"]', __moduleName__Module.card.confirm);
        },
        //display first pop to confirm card deletion
        delete: function (event) {
            event.preventDefault();
            event.stopPropagation();
            var $elem = $(this);
            __moduleName__Module.card.props.id_card = $elem.data('id_card');
            __moduleName__Module.popup.set(card_confirm_deleted_msg);
        },
        //display second popup to announce the card's deletion success
        confirm: function (event) {

            event.preventDefault();
            event.stopPropagation();
            var id_card = __moduleName__Module.card.props.id_card,
                url = window['__moduleName___delete_card_url'] + '&pc=' + id_card,
                card = __moduleName__Module.card,
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
                    console.log(jqXHR, textStatus, errorThrown);
                },
                success: function (result) {
                    if (result) {
                        $('.' + identifier + '[data-id_card=' + id_card + ']').remove();
                        __moduleName__Module.popup.setDeleteCardPopup(card_deleted_msg);
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
            for (i = 0; i < __moduleName__Module.oney.props.queries.length; i++) {
                if (typeof __moduleName__Module.oney.props.queries[i] != 'undefined') {
                    __moduleName__Module.oney.props.queries[i].abort();
                }
            }
            __moduleName__Module.oney.props.queries = [];
        },
        init: function () {
            if (typeof window['__moduleName___oney'] == 'undefined' || !window['__moduleName___oney']) {
                return;
            }
            var oney = __moduleName__Module.oney;

            this.cta.init();
            this.required.init();

            oney.load();

            var popin = oney.cta.popin;
            prestashop.on('updatedCart', popin.check).on('updatedProduct', popin.check);
        },
        load: function (with_schedule) {
            var oney = __moduleName__Module.oney,
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
                url: window['__moduleName___ajax_url'] + '?rand=' + new Date().getTime(),
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
                        } else if (!with_schedule) {
                            oney.cta.enable();
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
                identifier: '__moduleName__OneyLoader',
            },
            set: function (target) {
                if (typeof target == 'undefined' || !target) {
                    return;
                }
                var loader = '<span class="' + this.props.identifier + '">' +
                    '<span class="' + this.props.identifier + '_spinner"><span></span></span>' +
                    '<span class="' + this.props.identifier + '_message">' + window['__moduleName___oney_loading_msg'] + ' <i>.</i><i>.</i><i>.</i></span>' +
                    '</span>';
                $(target).html(loader);
            },
        },
        cta: {
            props: {
                identifier: '__moduleName__OneyCta',
                loaded: false
            },
            init: function () {
                var cta = this;
                cta.props.loaded = true;
                $document.on('click', '.' + cta.props.identifier + '_button', cta.popin.toggle);
                cta.popin.init();
            },
            enable: function () {
                var popin = __moduleName__Module.oney.cta.popin.props.identifier,
                    cta = __moduleName__Module.oney.cta.props.identifier;
                $('.' + cta + '_button').removeClass('-disabled');
                $('.' + popin).removeClass('-error');
            },
            disable: function () {
                var popin = __moduleName__Module.oney.cta.popin.props.identifier,
                    cta = __moduleName__Module.oney.cta.props.identifier;
                $('.' + cta + '_button').addClass('-disabled');
                $('.' + popin).addClass('-error');
            },
            popin: {
                props: {
                    identifier: '__moduleName__OneyPopin',
                    open: false,
                    loaded: false,
                },
                init: function () {
                    var oney = __moduleName__Module.oney,
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
                    var oney = __moduleName__Module.oney,
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

                    var oney = __moduleName__Module.oney,
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
                    __moduleName__Module.oney.cta.popin.choose($button.data('type'));
                },
                choose: function (option) {
                    var identifier = __moduleName__Module.oney.cta.popin.props.identifier;
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
                    var popin = __moduleName__Module.oney.cta.popin;

                    var is_open = $('-open').length > 0;
                    if (is_open) {
                        popin.close();
                    } else {
                        popin.open();
                    }
                },
                check: function () {
                    var oney = __moduleName__Module.oney,
                        popin = oney.cta.popin,
                        open = popin.props.open;

                    oney.props.loaded = false;

                    if (open) {
                        popin.open();
                    }
                },
                open: function () {
                    var oney = __moduleName__Module.oney,
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
                    var oney = __moduleName__Module.oney,
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
                    __moduleName__Module.oney.cta.popin.open();
                },
                hide: function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    __moduleName__Module.oney.cta.popin.close();
                },
            }
        },
        required: {
            props: {
                identifier: '__moduleName__OneyRequired'
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
                var required = __moduleName__Module.oney.required,
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
                                dot = value.indexOf('.', at + 1),
                                plus = value.indexOf('+', 1),
                                is_email = at > 0 && dot > 0 && plus < 0;
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
                __moduleName__Module.oney.required.reset();
                __moduleName__Module.popup.close();
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
                    url: window['__moduleName___ajax_url'] + '?rand=' + new Date().getTime(),
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
                                __moduleName__Module.popup.close();
                            }, 5000);
                        } else {
                            var errors = '';
                            for (var error in data.message) {
                                if (error !== 'indexOf') {
                                    errors += $('<p />').html(data.message[error]).text() + "\n";
                                }
                            }

                            $('.' + identifier + '_message').addClass('-error').html(errors);
                        }
                    }
                });
            },
            submit: function (event) {
                event.preventDefault();
                event.stopPropagation();

                var payment_data = {},
                    $required = $('.__moduleName__OneyRequired'),
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
                    payment_data[name] = value;
                });

                return __moduleName__Module.oney.required.save(payment_data);
            },
        },
    },
    popup: {
        props: {
            identifier: '__moduleName__Popin',
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
            var popup = __moduleName__Module.popup,
                props = popup.props;
            if (!sanitizePopupHtml(content)) {
                return;
            }
            if ($('.' + props.identifier).length) {
                popup.close();
            } else {
                popup.create();
            }
            popup.hydrate(content);
            popup.open();


        },
        setDeleteCardPopup: function (content) {
            var popup = __moduleName__Module.popup,
                props = popup.props;
            popup.create();
            popup.hydrate(content);
            popup.open();
            $document.on('click', 'button[name="card_deleted"]', __moduleName__Module.popup.close);
        },
        open: function () {
            var props = __moduleName__Module.popup.props;
            var popin = $('.' + props.identifier);
            popin.addClass('-open');
            window.setTimeout(function () {
                popin.addClass('-show');
            }, 0);
        },
        close: function () {
            var props = __moduleName__Module.popup.props;
            var popin = $('.' + props.identifier);

            popin.removeClass('-show');
            popin.removeClass('-open');


        },
        remove: function () {
            var {popup} = __moduleName__Module.tools,
                {identifier} = popup.props,
                $popup = $('.' + identifier);

            $popup.remove();
        },
        create: function () {
            var props = __moduleName__Module.popup.props,
                html = '<div class="' + props.identifier + '"><button class="' + props.identifier + '_close"></button><div class="' + props.identifier + '_content"></div></div>';
            $('body').append(html);
        },
        hydrate: function (content) {
            var props = __moduleName__Module.popup.props;
            $('.' + props.identifier + '_content').html(content);
        }
    },
    tools: {
        loadSpinner: function () {
            $('.__moduleName__IntegratedPayment').append('<div class="ipOverlay -disabled">');
            html = '<div class="ipOverlay_inner" ><div class="ipOverlay__content"><span class="ipOverlay_spinner"></span</div></div>';
            $('.ipOverlay').append(html);

            $('.ipOverlay').removeClass('-disabled');
            $('.ipOverlay').addClass('-show');
        },
        removeSpinner: function () {
            $('.ipOverlay').remove();
        },
    },
    validation: {
        props: {
            identifier: '__moduleName__Validation',
            duration: 1,
            attemps: {
                current: 0,
                limit: 5,
                interval: 2000,
            },
            query: null
        },
        init: function () {
            const {validation} = __moduleName__Module;
            validation.try();
        },
        try: function (last_try) {
            const {validation} = __moduleName__Module;
            if (validation.props.query != null) {
                validation.props.query.abort();
                validation.props.query = null;
            }

            let data = {
                _ajax: 1
            }
            if (typeof last_try != 'undefined' && last_try) {
                data['last_try'] = 1;
            }

            validation.props.query = $.ajax({
                type: 'POST',
                url: window['validation_ajax_url'],
                dataType: 'json',
                data: data,
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR, textStatus, errorThrown);
                },
                success: function (data) {
                    if (typeof data.action != 'undefined') {
                        switch (data.action) {
                            case 'redirect':
                                validation.actions.redirect(data.redirected_url);
                                break;
                            case 'wait':
                                validation.actions.wait();
                                break;
                            default:
                                break;
                        }
                    }
                },
            });
        },
        actions: {
            redirect: function (url) {
                window.location.href = url;
            },
            wait: function () {
                let {validation} = __moduleName__Module,
                    {props} = validation,
                    {attemps} = props;

                // update attemps
                attemps.current++;
                props.attemps = attemps;

                let last_try = attemps.current >= attemps.limit;
                setTimeout(() => {
                    validation.try(last_try);
                }, attemps.interval);
            }
        }
    },
};

$(document).ready(function () {
    $document = $(document);
    $window = $(window);
    __moduleName__Module.init();
});

window['__moduleName__Module'] = __moduleName__Module;
