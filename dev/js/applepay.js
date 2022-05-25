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

            // Create ApplePaySession
            const session = new ApplePaySession(3, request);

            session.onvalidatemerchant = async event => {
                // Call your own server to request a new merchant session.
                const merchantSession = await validateMerchant();
                session.completeMerchantValidation(merchantSession);
            };

            session.onpaymentmethodselected = event => {
                // Define ApplePayPaymentMethodUpdate based on the selected payment method.
                // No updates or errors are needed, pass an empty object.
                const update = {};
                session.completePaymentMethodSelection(update);
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
                const result = {
                    "status": ApplePaySession.STATUS_SUCCESS
                };
                session.completePayment(result);
            };

            session.oncancel = event => {
                // Payment cancelled by WebKit
            };

            session.begin();
        })
    }
};
