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
var allow_debug = false, debug = function (str) {
    if (allow_debug) {
        console.log(str);
    }
};
var $document, payplug = {
    init: function () {
        debug('payplug init');
        this.card.init();
        this.order.init();
    },
    order: {
        init: function () {
            // Styling
            var $options = $('input[data-module-name="payplug"]');
            $options.each(function() {
                var optionId = $(this).attr('id') + '-additional-information';
                $('#'+optionId).attr('style', 'margin:0;');
            }).parents('.payment-option').addClass('payplug-payment-option')
        }
    },
    card: {
        init: function () {
            $document.on('click', 'a.ppdeletecard', payplug.card.delete);
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
    }
};
$(document).ready(function () {
    $document = $(document);
    payplug.init();
});
