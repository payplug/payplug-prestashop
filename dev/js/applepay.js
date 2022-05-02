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
var $document, $window;
window[module_name+'Module'] = {
    init: function () {
        $('apple-pay-button').click(function() {
            // Define ApplePayPaymentRequest
            const request = applePayPaymentRequest;
            console.log(request);

            // Create ApplePaySession
            const session = new ApplePaySession(3, request);
            console.log(session);

            $.ajax({
                method: "POST",
                url: applePayAjaxURL,
            })
            .success(function (datas) {
                console.log('success');
                var merchant_session = JSON.parse(datas)
                console.log(merchant_session);
                console.log(merchant_session.id);

                session.onvalidatemerchant = async event => {
                    try {
                        session.completeMerchantValidation(merchant_session.payment_method.merchant_session);
                    } catch (err) {
                        console.error(err);
                    }
                };

                /*session.onpaymentmethodselected = event => {
                    // Define ApplePayPaymentMethodUpdate based on the selected payment method.
                    // No updates or errors are needed, pass an empty object.
                    console.log('onpaymentmethodselected');
                    const update = {};
                    session.completePaymentMethodSelection(update);
                };*/

                session.onshippingmethodselected = event => {
                    // Define ApplePayShippingMethodUpdate based on the selected shipping method.
                    // No updates or errors are needed, pass an empty object.
                    console.log('onshippingmethodselected');
                    const update = {};
                    session.completeShippingMethodSelection(update);
                };

                session.onshippingcontactselected = event => {
                    // Define ApplePayShippingContactUpdate based on the selected shipping contact.
                    console.log('onshippingcontactselected');
                    const update = {};
                    session.completeShippingContactSelection(update);
                };

                session.onpaymentauthorized = event => {
                    // Define ApplePayPaymentAuthorizationResult
                    console.log('onpaymentauthorized');
                    console.log(session);
                    console.log(ApplePaySession);
                    console.log(event.payment.token);
                    console.log(merchant_session.id);

                    $.ajax({
                        method: "POST",
                        url: applePayPaymentAjaxURL,
                        data: {
                            token: event.payment.token,
                            id_payment: merchant_session.id
                        }
                    })
                    .success(function (datas) {
                        console.log('success');
                        console.log(datas);

                        const result = {
                            "status": ApplePaySession.STATUS_SUCCESS
                        };

                        session.completePayment(result);

                        window.location.replace(datas);
                    })
                    .error(function (msg) {
                        console.log(msg);
                    });
                };

                session.oncancel = event => {
                    // Payment cancelled by WebKit
                    console.log('payment cancel');
                };

                session.begin();
            })
            .error(function (msg) {
                console.log(msg);
            });
        })
    }
};
