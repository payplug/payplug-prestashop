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
window[module_name+'ModuleApplePay'] = {
    init: function () {
        $('apple-pay-button').click(function() {
            // Define ApplePayPaymentRequest
            const request = applePayPaymentRequest;

            // Create ApplePaySession
            const session = new ApplePaySession(3, request);

            $.ajax({
                method: "POST",
                url: applePayMerchantSessionAjaxURL,
            })
            .success(function (datas) {
                var datas = JSON.parse(datas);
                if (datas.result === false) {
                    console.log(datas.error_message);
                }

                try {
                    var merchant_session_object = datas.apiResponse.merchant_session;
                    var id_payment = datas.idPayment;
                } catch (error) {
                    console.error(error);
                    payplugModule.popup.set(datas.template);
                    return
                }

                session.onvalidatemerchant = async event => {
                    try {
                        session.completeMerchantValidation(datas.apiResponse.merchant_session);
                    } catch (err) {
                        console.error(err);
                    }
                };

                /*session.onpaymentmethodselected = event => {
                    // Define ApplePayPaymentMethodUpdate based on the selected payment method.
                    // No updates or errors are needed, pass an empty object.
                    const update = {};
                    session.completePaymentMethodSelection(update);
                };*/

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
                        url: applePayPaymentAjaxURL,
                        data: {
                            token: event.payment.token,
                            id_payment: id_payment
                        }
                    })
                    .success(function (datas) {
                        var datas = JSON.parse(datas);
                        var apple_pay_Session_status = ApplePaySession.STATUS_SUCCESS;

                        if (datas.result !== true) {
                            apple_pay_Session_status = ApplePaySession.STATUS_FAILURE;
                        }

                        const result = {
                            "status": apple_pay_Session_status
                        };

                        session.completePayment(result);

                        if (datas.result === true) {
                            window.location.replace(datas.link_redirect);
                        } else {
                            payplugModule.popup.set(datas.template);
                        }
                    })
                };

                session.oncancel = event => {
                    // Payment cancelled by WebKit
                    console.log('payment cancel');
                };

                session.begin();
            })
        })
    }
};

$(document).ready(function () {
    window[module_name+'ModuleApplePay'].init();
});