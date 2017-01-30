/**
 * 2013 - 2016 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2016 PayPlug SAS
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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

    $('#payment-confirmation button').on('click', function(){
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
        id_card = $('input[name=payplug_card]:checked').val()
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