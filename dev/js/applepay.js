/**
 * 2013 - 2022 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@window[module_name+'Module'].com so we can send you a copy immediately.
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
var $document, $window, session = null;
window[module_name+'ModuleApplePay'] = {
    init: function () {
        $('apple-pay-button').click(function() {
            if (session != null) {
                return;
            }

            var data='';
            $.ajax({
                method: "POST",
                url: applePayPaymentRequestAjaxURL,
                async: false
            })
                .success(function (datas) {
                    data = JSON.parse(datas);
                })
                .error(function () {
                    $('#apple-pay-button').css('pointer-events', 'auto');
                    session = null;
                    payplugModule.popup.set(payplug_transaction_error_message);
                })

            // Define ApplePayPaymentRequest
            const request = data.applePayPaymentRequest;

            // Create ApplePaySession
            session = new ApplePaySession(3, request);
            var paymentId= null;

            $.ajax({
                method: "POST",
                url: applePayMerchantSessionAjaxURL,
                data: {
                    method: 'applepay',
                    id_cart: applePayIdCart
                },
                beforeSend: function () {
                    $('#apple-pay-button').css('pointer-events', 'none');
                }
            })
                .success(function (datas) {
                    var datas = JSON.parse(datas);
                    if (!datas.result) {
                        console.log(datas.error_message);
                        $('#apple-pay-button').css('pointer-events', 'auto');
                        session = null;
                        payplugModule.popup.set(payplug_transaction_error_message);
                        return;
                    }

                    try {
                        var merchant_session_object = datas.apiResponse.merchant_session;
                        paymentId = datas.idPayment;
                        var id_cart = datas.idCart;
                    } catch (error) {
                        console.error(error);
                        payplugModule.popup.set(payplug_transaction_error_message);
                        return;
                    }

                    session.onvalidatemerchant = async event => {
                        try {
                            session.completeMerchantValidation(datas.apiResponse.merchant_session);
                        } catch (err) {
                            console.error(err);
                        }
                    };

                    session.onshippingmethodselected = event => {
                        // Define ApplePayShippingMethodUpdate based on the selected shipping method.
                        // No updates or errors are needed, pass an empty object.
                        const update = {};
                        session.completeShippingMethodSelection(update);
                    };

                    session.onshippingcontactselected = event => {
                        // Define ApplePayShippingContactUpdate based on the selected shipping contact.
                        const update = {};
                        session.completeShippingContactSelection(update);
                    };

                    session.onpaymentauthorized = event => {
                        // Define ApplePayPaymentAuthorizationResult
                        $.ajax({
                            method: "POST",
                            url: payplug_ajax_url,
                            data: {
                                _ajax: 1,
                                token: event.payment.token,
                                pay_id: paymentId,
                                cart_id: id_cart,
                                patchPayment: 1
                            }
                        })
                            .success(function (datas) {
                                var datas = JSON.parse(datas);

                                if (!datas.result) {
                                    session.completePayment({ "status": ApplePaySession.STATUS_FAILURE });
                                    $('#apple-pay-button').css('pointer-events', 'auto');
                                    session = null;
                                    payplugModule.popup.set(payplug_transaction_error_message);
                                    return;
                                }

                                session.completePayment({ "status": ApplePaySession.STATUS_SUCCESS });
                                window.location.replace(datas.return_url);
                            })
                            .error(function () {
                                $('#apple-pay-button').css('pointer-events', 'auto');
                                session = null;
                                payplugModule.popup.set(payplug_transaction_error_message);
                            })
                    };

                    session.oncancel = event => {
                        // Payment cancelled by WebKit
                        $('#apple-pay-button').css('pointer-events', 'auto');
                        session = null;
                        console.log('payment cancel');
                    };

                    session.begin();
                })
                .error(function () {
                    $('#apple-pay-button').css('pointer-events', 'auto');
                    session = null;
                    payplugModule.popup.set(payplug_transaction_error_message);
                })
        })
    }
};