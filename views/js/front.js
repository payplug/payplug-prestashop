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

    $('a.ppdeletecard').unbind('click');
    $('a.ppdeletecard').bind('click', function(e){
        e.preventDefault();
        var id_payplug_card = $(this).parent().parent().attr('id');
        id_payplug_card = id_payplug_card.replace('id_payplug_card_', '');
        var url = $(this).attr('href')+'&pc='+id_payplug_card;
        callDeleteSavedCard(id_payplug_card, url);
        return false;
    });

    $('a.ppdelete').unbind('click');
    $('a.ppdelete').bind('click', function(e){
        e.preventDefault();
        var id_payplug_card = $('input[name=payplug_card]:checked').val()
        if(id_payplug_card != 'new_card')
            callDeleteCard(id_payplug_card);
        return false;
    });

    $('input[name=payplug_card]').unbind('change');
    $('input[name=payplug_card]').bind('change', function(e){
        if ($(this).val() == 'new_card') {
            $('a.ppdelete').hide();
        } else {
            $('a.ppdelete').show();
        }
    });

    $('input[name=SubmitPayplugOneClick]').unbind('click');
    $('input[name=SubmitPayplugOneClick]').bind('click', function(e){
        e.preventDefault();
        $('body').append('<div class="overlay"></div>');
        var id_card = $('input[name=payplug_card]:checked').val()
        callPayment(id_card);
        return false;
    });

    $('a.payplug.call').unbind('click');
    $('a.payplug.call').bind('click', function(e) {
        e.preventDefault();
        $('body').append('<div class="overlay"><img class="loader" src="'+spinner_url+'" /></div>');
        callPayment('new_card');
        return false;
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
                $('#module-payplug-savedCards p.message').show();
                $('#module-payplug-controllers-front-cards p.message').show();
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
                if ($('div.card-wrapper').length <= 1) {
                    window.location.replace(payment_url);
                }
            }
        }
    });
}

function callPayment(id_card)
{
    var url = $('input:hidden[name=front_ajax_url]').val();
    var id_cart = $('input:hidden[name=id_cart]').val();
    var data = {_ajax: 1, pc: id_card, pay:1, cart: id_cart};

    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: data,
        beforeSend: function() {
            $('.ppwait').show();
            $('input[name=SubmitPayplugOneClick]').addClass('disable');
            $('input[name=SubmitPayplugOneClick]').attr('disabled','disabled');
        },
        complete: function(){
            $('.ppwait').hide();
            if (id_card == 'new_card') {
                $('input[name=SubmitPayplugOneClick]').removeClass('disable');
                $('input[name=SubmitPayplugOneClick]').removeAttr('disabled');
                $('div.overlay').remove();
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert('error CALL PAYMENT');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function(data)
        {
            if(data)
            {
                if(data.result == true)
                {
                    $('.ppfail').hide();
                    $('.ppsuccess').fadeIn(500);
                    var delay = 9000;
                    setTimeout(function(){
                            $('.ppsuccess').fadeOut(500);
                        },
                        delay
                    );

                    var validation_url = data.validation_url;
                    window.location.replace(validation_url);
                }
                else
                {
                    if(data.result == 'new_card')
                    {
                        if(data.embedded_mode == 0)
                            window.location.replace(data.payment_url);
                        else
                        {
                            Payplug.showPayment(data.payment_url);
                        }
                    }
                    else
                    {
                        $('.ppfail').fadeIn(500);
                        if (id_card != 'new_card') {
                            $('input[name=SubmitPayplugOneClick]').removeClass('disable');
                            $('input[name=SubmitPayplugOneClick]').removeAttr('disabled');
                            $('div.overlay').remove();
                        }
                        var delay = 9000;
                        setTimeout(function(){
                                $('.ppfail').fadeOut(500);
                            },
                            delay
                        );
                    }
                }
            }
        }
    });
}