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

// loader.min.js doesn't fetch widget.min.js (the script that actually sets
// window.oneyMerchantApp) on its own - it only *defines* window.loadOneyWidget(t),
// which must be called to trigger that fetch and get notified once it's done.
// This file is webpack-bundled, so a local helper of the same name doesn't merge
// with that global: it just shadows it, and window.loadOneyWidget never gets
// called - oneyMerchantApp never appears, and callers silently time out. Call the
// official window.loadOneyWidget directly instead, with a bounded timeout kept as
// a safety net (widget.min.js failing/hanging) and to fail gracefully
// (onUnavailable) if the official loader isn't present at all.
function waitForOneyWidget(callback, onUnavailable) {
    if (typeof window.loadOneyWidget !== 'function') {
        if (typeof onUnavailable === 'function') {
            onUnavailable();
        }

        return;
    }

    var settled = false,
        timeout = setTimeout(function () {
            if (!settled) {
                settled = true;
                if (typeof onUnavailable === 'function') {
                    onUnavailable();
                }
            }
        }, 5000);

    window.loadOneyWidget(function () {
        if (settled) {
            return;
        }
        settled = true;
        clearTimeout(timeout);
        callback();
    });
}

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
                    let {form} = integrated.props;
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
        set: (key, value) => {
            const {applepay} = __moduleName__Module;
            applepay.props[key] = value;
        },
        init: () => {
            const {applepay} = __moduleName__Module;
            applepay.display();
            applepay.handler();
        },
        handler: () => {
            const {applepay} = __moduleName__Module,
                {workflow} = applepay.props;
            $('apple-pay-button, #payment-confirmation button').click(function (event) {
                $(this).fadeOut(10).fadeIn(10);
                let payment_method_id = $('input[name="payment-option"]:checked').attr('id'),
                    payment_method = $('#pay-with-' + payment_method_id + '-form input[name="method"]').val();

                if ('applepay' == payment_method || 'checkout' != workflow) {
                    event.preventDefault();
                    event.stopPropagation();
                    applepay.createSession();
                }
            });
        },
        display: () => {
            let {applepay} = __moduleName__Module,
                {identifier, workflow} = applepay.props,
                $wrapper = $('.' + identifier + '_wrapper');
            if (!$wrapper.length) {
                return;
            }

            const applepay_allowed = typeof window.ApplePaySession == 'function' && window.ApplePaySession.canMakePayments();

            switch (workflow) {
                case 'shopping-cart':
                case 'product':
                    if (applepay_allowed) {
                        $wrapper.addClass('-visible');
                    }
                    break;
                case 'checkout':
                default:
                    const paymentOptionId = $wrapper
                            .parents('.additional-information')
                            .eq(0)
                            .attr('id')
                            .replace('payment-option-', '')
                            .replace('-additional-information', ''),
                        $paymentOption = $('#payment-option-' + paymentOptionId + '-container').eq(0);
                    if (!applepay_allowed) {
                        $paymentOption.addClass('-hidden');
                    }
                    break;
            }
        },
        createSession: async () => {
            // Get PaymentRequest data
            const {applepay} = __moduleName__Module,
                {worflow} = applepay.props;

            if (!ApplePaySession) {
                return;
            }

            const request = applepay.getRequestDatas();

            // Define the default carrier
            if (typeof request.carriers != 'undefined' && request.carriers.length) {
                applepay.props.carrier = request.carriers[0];
            }

            // Create ApplePaySession
            let apple_pay_request = {
                "countryCode": request.country_code,
                "currencyCode": request.currency_code,
                "merchantCapabilities": [
                    "supports3DS"
                ],
                "supportedNetworks": [
                    "cartesBancaires",
                    "visa",
                    "masterCard"
                ],
                "supportedTypes": [
                    "debit",
                    "credit"
                ],
                "total": {
                    "label": request.total.label,
                    "type": "final",
                    "amount": request.total.amount
                },
                'applicationData': btoa(JSON.stringify({
                    'apple_pay_domain': request.apple_pay_domain
                })),
            }

            if ('checkout' != worflow) {
                apple_pay_request.shippingMethods = request.carriers;
                apple_pay_request.lineItems = request.line_items;
                apple_pay_request.requiredBillingContactFields = [
                    'postalAddress',
                    'name',
                ];
                apple_pay_request.requiredShippingContactFields = [
                    "postalAddress",
                    "name",
                    "phone",
                    "email"
                ];
            }

            const session = new ApplePaySession(4, apple_pay_request);
            applepay.props.session = session;
            applepay.getPaymentRequest();
        },
        beginSession: (request) => {
            let {applepay} = __moduleName__Module;
            if (typeof request == 'undefined') {
                return applepay.error();
            }
            applepay.props.request = request;
            applepay.props.session.onvalidatemerchant = applepay.sessionHandler.onvalidatemerchant;
            applepay.props.session.onshippingcontactselected = applepay.sessionHandler.onshippingcontactselected;
            applepay.props.session.onshippingmethodselected = applepay.sessionHandler.onshippingmethodselected;
            applepay.props.session.onpaymentauthorized = applepay.sessionHandler.onpaymentauthorized;
            applepay.props.session.oncancel = applepay.sessionHandler.oncancel;
            applepay.props.session.begin();
        },
        getPaymentRequest: () => {
            const {applepay} = __moduleName__Module,
                {workflow, query} = applepay.props;
            if (query != null) {
                query.abort();
            }
            applepay.props.query = $.ajax({
                method: "POST",
                url: applePayMerchantSessionAjaxURL,
                data: {
                    workflow: workflow,
                    method: 'applepay',
                    id_cart: applePayIdCart
                },
                beforeSend: () => {
                    $('#apple-pay-button').css('pointer-events', 'none');
                },
                success: (result) => {
                    const request = JSON.parse(result);
                    return applepay.beginSession(request);
                },
                error: () => {
                    return applepay.error();
                }
            });
        },
        getRequestDatas: () => {
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
            if (workflow === 'product' && $('#product_page_product_id').length) {
                data.empty_cart = true;
                var $product_form = $('#add-to-cart-or-refresh'),
                    form_data = $product_form.serializeArray();
                form_data.map(function (field) {
                    data[field.name] = field.value;
                });
            }
            let response_data;
            applepay.props.query = $.ajax({
                method: "POST",
                url: applePayPaymentRequestAjaxURL,
                async: false,
                data: data,
                success: function (result) {
                    response_data = JSON.parse(result);
                },
                error: function () {
                    response_data = null;
                }
            });
            return response_data;
        },
        getUpdatedRequest: () => {
            const {applepay} = __moduleName__Module,
                {workflow, carrier, address, query} = applepay.props;
            if (query != null) {
                query.abort();
            }
            let request = null;
            applepay.props.query = $.ajax({
                method: "POST",
                url: payplug_ajax_url,
                async: false,
                dataType: 'json',
                data: {
                    _ajax: 1,
                    method: 'applepayUpdate',
                    applepayUpdate: 1,
                    workflow: workflow,
                    carrier: carrier,
                    address: address,
                },
                success: (datas) => {
                    request = !datas.result ? null : datas.request;
                },
                error: () => {
                    request = null;
                }
            });
            return request;
        },
        sessionHandler: {
            onvalidatemerchant: async (event) => {
                const {applepay} = __moduleName__Module,
                    {session, request} = applepay.props;
                try {
                    const merchantSession = await request.apiResponse.merchant_session;
                    session.completeMerchantValidation(merchantSession);
                } catch (err) {
                    console.log('onvalidatemerchant: ', err);
                }
            },
            onshippingmethodselected: (event) => {
                const {applepay} = __moduleName__Module,
                    {session} = applepay.props,
                    {shippingMethod} = event;

                applepay.props.carrier = shippingMethod;

                const request = applepay.getUpdatedRequest();
                const update = {
                    'newTotal': {
                        "label": request.total.label,
                        "type": "final",
                        "amount": request.total.amount
                    },
                    'newLineItems': request.line_items,
                };

                try {
                    session.completeShippingMethodSelection(update);
                } catch (err) {
                    console.log('onshippingmethodselected: ', err);
                }
            },
            onshippingcontactselected: (event) => {
                const {applepay} = __moduleName__Module,
                    {session} = applepay.props,
                    {shippingContact} = event;

                applepay.props.address = {
                    locality: shippingContact.locality,
                    country: shippingContact.country,
                    postalCode: shippingContact.postalCode,
                    administrativeArea: shippingContact.administrativeArea,
                    countryCode: shippingContact.countryCode,
                };

                const request = applepay.getUpdatedRequest();
                const update = {
                    'newTotal': {
                        "label": request.total.label,
                        "type": "final",
                        "amount": request.total.amount
                    },
                    'newLineItems': request.line_items,
                    'newShippingMethods': request.carriers,
                };
                try {
                    session.completeShippingContactSelection(update);
                } catch (err) {
                    console.log('onshippingcontactselected: ', err);
                }
            },
            onpaymentauthorized: (event) => {
                const {applepay} = __moduleName__Module,
                    {session, carrier} = applepay.props,
                    {payment} = event;

                // Define ApplePayPaymentAuthorizationResult
                $.ajax({
                    method: "POST",
                    url: payplug_ajax_url,
                    data: {
                        _ajax: 1,
                        params: {
                            token: payment.token,
                            user: {
                                billing: payment.billingContact,
                                shipping: payment.shippingContact,
                            },
                            carrier: carrier,
                            pay_id: applepay.props.request.idPayment,
                            workflow: applepay.props.workflow,
                        },
                        patchPayment: 1,
                        method: 'applepayPatch',
                    },
                    success: (json) => {
                        var result = JSON.parse(json);

                        if (!result.result) {
                            session.completePayment({"status": ApplePaySession.STATUS_FAILURE});
                            return __moduleName__Module.applepay.error();
                        }

                        session.completePayment({"status": ApplePaySession.STATUS_SUCCESS});
                        window.location.replace(result.return_url);
                    },
                    error: () => {
                        console.log('onpaymentauthorized: An error occured');
                        __moduleName__Module.applepay.error();
                    }
                })
            },
            oncancel: (event) => {
                // Payment canceled by WebKit
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
                        method: 'applepayCancel',
                        workflow: applepay.props.workflow,
                    },
                    success: () => {
                        applepay.error();
                    },
                });
            },
        },
        error: () => {
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
        },
        init: function () {
            if (typeof window['__moduleName___oney'] == 'undefined' || !window['__moduleName___oney']) {
                return;
            }
            var oney = __moduleName__Module.oney;

            this.cta.init();
            this.required.init();
            this.checkout.init();

            oney.load();

            prestashop.on('updatedCart', oney.load).on('updatedProduct', oney.load);
        },
        // Fetch eligibility (allowed country/amount) and the amount to pass to the
        // official Oney widget: the widget itself computes the schedule, this module
        // no longer calls Oney's simulation API.
        load: function () {
            var oney = __moduleName__Module.oney,
                data = {
                    _ajax: 1,
                    isOneyElligible: 1,
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

            oney.props.query = $.ajax({
                url: window['__moduleName___ajax_url'] + '?rand=' + new Date().getTime(),
                headers: {"cache-control": "no-cache"},
                type: 'POST',
                async: true,
                cache: false,
                dataType: 'json',
                data: data,
                success: function (data) {
                    oney.cta.props.amount = data.amount || 0;
                    if (data.result) {
                        oney.cta.enable();
                    } else {
                        oney.cta.disable();
                    }
                },
                error: function () {
                    oney.cta.disable();
                }
            });
        },
        cta: {
            props: {
                identifier: '__moduleName__OneyCta',
                amount: 0
            },
            init: function () {
                var cta = this;
                $document.on('click', '.' + cta.props.identifier + '_button', cta.open);
            },
            enable: function () {
                $('.' + __moduleName__Module.oney.cta.props.identifier + '_button').removeClass('-disabled');
            },
            disable: function () {
                $('.' + __moduleName__Module.oney.cta.props.identifier + '_button').addClass('-disabled');
            },
            // Loads the official Oney loader (once) then opens the Oney-hosted
            // simulation pop-in — Payplug no longer renders its own pop-in markup.
            open: function (event) {
                event.preventDefault();
                event.stopPropagation();

                var cta = __moduleName__Module.oney.cta;
                if ($(event.currentTarget).is('.-disabled')) {
                    return;
                }

                // merchant_guid can be missing (e.g. no Oney metadata for the shop's
                // home country, and no allowed country has one either): the widget
                // can't initialize without it, so disable the CTA instead of calling
                // it with a broken value.
                if (!window['__moduleName___oney_merchant_guid']) {
                    cta.disable();

                    return;
                }

                var options = {
                    country: window['__moduleName___oney_country'],
                    language: window['__moduleName___oney_language'],
                    merchant_guid: window['__moduleName___oney_merchant_guid'],
                    payment_amount: cta.props.amount,
                    errorCallback: function () {
                        cta.disable();
                    }
                };

                // Only filter by business_transaction_codes when we actually have some:
                // filtering on an empty list is invalid and Oney's API rejects it. With
                // no filter, Oney falls back to its own default offer for the merchant.
                var business_transaction_codes = window['__moduleName___oney_business_transaction_codes'];
                if (business_transaction_codes && business_transaction_codes.length) {
                    options.filter_by = 'business_transaction_codes';
                    options.business_transaction_codes = business_transaction_codes;
                }

                payplug_utilities.loadScript(window['__moduleName___oney_loader_url'], function () {
                    waitForOneyWidget(function () {
                        oneyMerchantApp.loadSimulationPopin({options: options});
                    }, function () {
                        // oneyMerchantApp never showed up after the loader ran: keep the
                        // page usable, just don't offer a schedule that can't be trusted.
                        cta.disable();
                    });
                }, function () {
                    // Oney unavailable (loader blocked/unreachable): keep the page usable,
                    // just don't offer a schedule that can't be trusted.
                    cta.disable();
                });
            },
        },
        // Checkout: inline section injected by Oney into our placeholder(s), one per
        // Oney payment option (x3/x4, with/without fees), each filtered by its own
        // single business_transaction_code — a legal requirement.
        checkout: {
            props: {
                identifier: '__moduleName__OneyCheckout'
            },
            init: function () {
                var checkout = this;
                $('.' + checkout.props.identifier).each(function () {
                    checkout.load($(this));
                });

                // The Oney widget can detach its own popin from the DOM (as part of its
                // close handler) before the click event finishes bubbling. When that
                // happens, PrestaShop's checkout-step click handler (bound on the
                // .checkout-step containers) can no longer resolve $(target).closest('.checkout-step'),
                // which wipes -current/js-current-step from every step instead of
                // restoring it to the payment step, collapsing the whole checkout.
                // This listener is bound without the capture flag, so it runs on the
                // bubble phase after every other click handler (Oney's own included) has
                // already run. If nothing ended up marked current, restore the payment
                // step instead of blocking propagation, which would also prevent Oney's
                // own handler (e.g. its close button) from running at all.
                document.addEventListener('click', function (e) {
                    if (!e.target.closest('#showOneyWidgetAsModal, .oney-modal')) {
                        return;
                    }
                    if (!$('.checkout-step.-current').length) {
                        $('#checkout-payment-step').addClass('-current js-current-step');
                    }
                });
            },
            load: function ($placeholder) {
                var id = $placeholder.attr('id'),
                    business_transaction_code = $placeholder.data('business-transaction-code'),
                    payment_amount = parseFloat($placeholder.data('payment-amount')),
                    // The buyer's delivery country (and its matching merchant_guid) can
                    // differ from the shop's company_iso for multi-country merchants: use
                    // the same country server-side resolved the business_transaction_code
                    // for, not the shop-wide globals used by the PDP/cart pop-in.
                    country = $placeholder.data('country'),
                    merchant_guid = $placeholder.data('merchant-guid');

                if (!id || !business_transaction_code || !payment_amount || !country || !merchant_guid) {
                    return;
                }

                payplug_utilities.loadScript(window['__moduleName___oney_loader_url'], function () {
                    waitForOneyWidget(function () {
                        oneyMerchantApp.loadCheckoutSection({
                            options: {
                                country: country,
                                language: window['__moduleName___oney_language'],
                                merchant_guid: merchant_guid,
                                payment_amount: payment_amount,
                                filter_by: 'business_transaction_code',
                                business_transaction_code: business_transaction_code,
                                checkout_placeholder: '#' + id,
                                errorCallback: function () {
                                    $placeholder.hide();
                                }
                            }
                        });
                    }, function () {
                        // oneyMerchantApp never showed up after the loader ran: hide the
                        // placeholder, the rest of the checkout stays usable.
                        $placeholder.hide();
                    });
                }, function () {
                    // Oney unavailable (loader blocked/unreachable): hide the placeholder,
                    // the rest of the checkout (other payment methods) stays usable.
                    $placeholder.hide();
                });
            },
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
                _ajax: 1,
                cartid: window['ajax_cart_id'],
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
