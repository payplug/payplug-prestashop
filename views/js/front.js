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

    var payplug_options = $('input[data-module-name=payplug]');
    payplug_options.each(function() {
        var extra = $(this).attr('id') + '-additional-information';
        $('#'+extra).attr('style', 'margin:0;');
    });
    payplug_options.parent().parent().find('img')
        .css('float', 'left')
        .css('margin', '0 10px 0 0');

    var pending = false;
    $('#payment-confirmation button').on('click', function(event){
        if (pending) {
            return false;
        }
        pending = true;
        $('#checkout-payment-step').css('background-color', '#eeeeee');
        $('.payment-confirmation button').attr('disabled', 'disabled');
        $('.ppfail').hide();
        $('.ppwait').show();
    });

    $('a.ppdeletecard').bind('click', function(e){
        e.preventDefault();
        var id_payplug_card = $(this).parent().parent().attr('id');
        id_payplug_card = id_payplug_card.replace('id_payplug_card_', '');
        var url = $(this).attr('href')+'&pc='+id_payplug_card;
        callDeleteSavedCard(id_payplug_card, url);
        return false;
    });

    $('a.ppdelete').bind('click', function(e){
        e.preventDefault();
        var id_payplug_card = $('input[name=payplug_card]:checked').val()
        if(id_payplug_card != 'new_card')
            callDeleteCard(id_payplug_card);
        return false;
    });

    $('input[name=payplug_card]').bind('change', function(e){
        if ($(this).val() == 'new_card') {
            $('a.ppdelete').hide();
        } else {
            $('a.ppdelete').show();
        }
    });

    $('input[name=payplug_card]').bind('change', function(e){
        var id_card = $('input[name=payplug_card]:checked').val()
        $('input:hidden[name=pc]').val(id_card);
    });
});

function callDeleteSavedCard(id_card, url)
{
    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        error: function(jqXHR, textStatus, errorThrown) {
            alert('error CALL DELETE CARD');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function(result)
        {
            if(result)
            {
                $('#id_payplug_card_'+id_card).remove();
                $('#module-payplug-cards div.message').show();
                $('#module-payplug-controllers-front-cards div.message').show();
            }
        }
    });
}

function callDeleteCard(id_card)
{
    var url = $('input:hidden[name=front_ajax_url]').val();
    var id_cart = $('input:hidden[name=id_cart]').val();
    var data = {_ajax: 1, pc: id_card, id_cart: id_cart};

    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: data,
        error: function(jqXHR, textStatus, errorThrown) {
            alert('error CALL DELETE CARD');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function(result)
        {
            if(result)
            {
                $('.'+id_card).remove();
            }
        }
    });
}
