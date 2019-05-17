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

$(document).ready(function() {
    var url = $('#payplug_form_js').data('payment-url');
    Payplug.showPayment(url);
    Payplug._listen('message', window, function(e) {
        if(typeof e.data == 'string' && e.data == 'closePayPlugFrame'){
            retrievePayment();
        }
    });
    function retrievePayment(){
        var retrieve_url = $('#payplug_form_js').data('retrieve-url');
        $.ajax({
            type: 'POST',
            async: true,
            url: retrieve_url,
            dataType: 'json',
            data: {retrieve:1},
            error: function(jqXHR, textStatus, errorThrown) {
                alert('error CALL RETRIEVE');
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
            },
            success: function(data)
            {
                if(data) {
                    if (typeof data.redirect_url != 'undefined' && data.redirect_url){
                        window.location.replace(data.redirect_url);
                    }
                }
            }
        });
    }
});
