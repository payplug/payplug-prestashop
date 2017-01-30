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
    admin_start();
});

function disableInput(){
    $('.ppdisabled').attr('disabled','disabled');
    $('span.ppdisabled').css('display','none');
    $('.ppdisabled').bind('click', function(e) {
        e.preventDefault();
    });
}

function validate_isEmail(s)
{
    var reg = /^[a-z\p{L}0-9!#$%&'*+\/=?^`{}|~_-]+[.a-z\p{L}0-9!#$%&'*+\/=?^`{}|~_-]*@[a-z\p{L}0-9]+[._a-z\p{L}0-9-]*\.[a-z\p{L}0-9]+$/i;
    return reg.test(s);
}

function validate_isPasswd(s)
{
    return (s.length >= 8 && s.length < 255);
}

function validate_field()
{
    var result = false;
    var flag = true;
    $('#p_error').remove();
    result = window['validate_isEmail']($('input.validate_email').val());
    if (result) {
        $('#error-email-regexp').addClass('hide');
        $('input.validate_email').parent().removeClass('form-error');
    } else {
        $('#error-email-regexp').removeClass('hide');
        $('input.validate_email').parent().addClass('form-error');
        flag = false;
    }

    result = window['validate_isPasswd']($('input.validate_password').val());
    if (result) {
        $('#error-password-regexp').addClass('hide');
        $('input.validate_password').parent().removeClass('form-error');
    } else {
        $('#error-password-regexp').removeClass('hide');
        $('input.validate_password').parent().addClass('form-error');
        flag = false;
    }

    if (flag) {
        $('input[name=submitAccount]').removeAttr('disabled');
        $('input[name=submitAccount]').removeClass('ppdisabled');
    } else {
        $('input[name=submitAccount]').attr('disabled','disabled');
        $('input[name=submitAccount]').addClass('ppdisabled');
    }
}

function admin_start()
{
    disableInput();

    $('input.switch-input').bind('change', function() {
        var firstValue = $(this).parent().find('.switch-input:first').val();
        if($(this).val() == firstValue) {
            $(this).siblings('.slide-button').css('left', '0%');
        } else {
            $(this).siblings('.slide-button').css('left', '50%');
        }
    });

    $('#payplug_sandbox_mode_off').bind('click', function(e) {
        if (($(this).attr('checked') == 'checked' || $(this).attr('checked') == true) && !$(this).hasClass('verified')) {
            e.preventDefault();
            callPopin('pwd');
        } else if (
            ($(this).attr('checked') == 'checked' || $(this).attr('checked') == true)
            && ($('#payplug_one_click_yes').attr('checked') == 'checked' || $('#payplug_one_click_yes').attr('checked') == true)
        ) {
            checkPremium(true);
            $(this).siblings('.slide-button').css('left', '0%');
        } else {
            $(this).siblings('.slide-button').css('left', '0%');
        }
    });

    $('#payplug_sandbox_mode_on').bind('click', function(e) {
        $(this).siblings('.slide-button').css('left', '50%');
    });

    $('input.validate').bind('keyup', function() {
        validate_field();
    });

    $('#payplug_show_on').bind('change', function(){
        if($(this).attr('checked') == true || $(this).attr('checked') == 'checked'){
            $(this).siblings('.switch-selection').css('left', '2px');
            $(this).attr('checked', false);
            var sandbox = $('#payplug_sandbox_mode_on').attr('checked');
            var embedded = $('#payplug_embedded_mode_on').attr('checked');
            var one_click = $('#payplug_one_click_yes').attr('checked');
            var args = {
                sandbox: (sandbox == 'checked' || sandbox == true) ? 1 : 0,
                embedded: (embedded == 'checked' || embedded == true) ? 1 : 0,
                one_click: (one_click == 'checked' || one_click == true) ? 1 : 0,
                activate: 1
            };
            callPopin('confirm', args);
        }
    });

    $('#payplug_show_off').bind('change', function(){
        if($(this).attr('checked') == true || $(this).attr('checked') == 'checked'){
            $(this).parent().removeClass('ppon');
            $(this).siblings('.switch-selection').css('left', '31px');
            $('.switch-show').css('background-color', '#8fb32a');
            $(this).attr('checked', false);
            callPopin('desactivate');
        }
    });

    $('#payplug_debug_mode_on').bind('change', function(){
        if($(this).attr('checked') == true || $(this).attr('checked') == 'checked'){
            var status = $('input[name=PAYPLUG_DEBUG_MODE]:checked').val();
            debug(status);
        }
    });

    $('#payplug_debug_mode_off').bind('change', function(){
        if($(this).attr('checked') == true || $(this).attr('checked') == 'checked'){
            var status = $('input[name=PAYPLUG_DEBUG_MODE]:checked').val();
            debug(status);
        }
    });

    $('#payplug_one_click_yes').bind('click', function(e) {
        if (
            ($('#payplug_sandbox_mode_off').attr('checked') == 'checked' || $('#payplug_sandbox_mode_off').attr('checked') == true)
            && !$(this).hasClass('premium')
        ){
            e.preventDefault();
            checkPremium(false);
        }
    });

    $('input[name=PAYPLUG_SANDBOX_MODE]').bind('change', function(e){
        // Change tips value of live / sandbox mode selected
        if($(this).val() == 1) { // Live
            $('#mode_live_tips').show();
            $('#mode_sandbox_tips').hide();
            $('#mode_live_tips').removeClass('hide');
        } else { // Sandbox
            $('#mode_sandbox_tips').show();
            $('#mode_live_tips').hide();
            $('#mode_sandbox_tips').removeClass('hide');
        }
    });

    $('input[name=PAYPLUG_EMBEDDED_MODE]').bind('change', function(e){
        // Change tips value of redirect / embedded mode selected
        if($(this).val() == 1) { // Redirect
            $('#payment_page_embedded_tips').show();
            $('#payment_page_redirect_tips').hide();
            $('#payment_page_embedded_tips').removeClass('hide');
        } else { // Emedded
            $('#payment_page_redirect_tips').show();
            $('#payment_page_embedded_tips').hide();
            $('#payment_page_redirect_tips').removeClass('hide');
        }
    });

    $('#submitSettings').bind('click', function(e){
        if($(this).hasClass('is_active')) {
            var sandbox = $('#payplug_sandbox_mode_on').attr('checked');
            var embedded = $('#payplug_embedded_mode_on').attr('checked');
            var one_click = $('#payplug_one_click_yes').attr('checked');
            var args = {
                sandbox: (sandbox == 'checked' || sandbox == true) ? 1 : 0,
                embedded: (embedded == 'checked' || embedded == true) ? 1 : 0,
                one_click: (one_click == 'checked' || one_click == true) ? 1 : 0,
                activate: 0
            };
            e.preventDefault();
            callPopin('confirm', args);
            return false;
        }
    });

    $('input[name=submitCheckConfiguration]').bind('click', function(e){
        e.preventDefault();
        callFieldset();
    });

    $('input[name=submitAccount]').bind('click', function(e){
        e.preventDefault();
        login();
    });
}

function login()
{
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var email = $('input[name=PAYPLUG_EMAIL]').val();
    var pwd = $('input[name=PAYPLUG_PASSWORD]').val();
    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: {
            _ajax : 1,
            log : 1,
            submitAccount : 1,
            PAYPLUG_EMAIL : email,
            PAYPLUG_PASSWORD : pwd,
        },
        beforeSend: function() {
            $('.panel-login .loader').show();
        },
        complete: function(){
            $('.panel-login .loader').hide();
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert('error LOGIN');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function(result)
        {
            $('div.panel-remove').remove();
            $('p.interpanel').after(result.content);
            admin_start();
			callFieldset();
        }
    });
}

function activate(enable)
{
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var data = {_ajax: 1, en: enable};

    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: {
            _ajax : 1,
            en : enable,
        },
        /*
        error: function(jqXHR, textStatus, errorThrown) {
            alert('errorActivate');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        */
        success: function()
        {
            if(enable == 1)
                $('#submitSettings').addClass('is_active');
            else
                $('#submitSettings').removeClass('is_active');
        }
    });
}

function debug(status)
{
    var url = $('input:hidden[name=admin_ajax_url]').val();
    data = {_ajax: 1, db: status};
    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: {
            _ajax : 1,
            db : status,
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert('error DEBUG');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function(result)
        {
            return true;
        }
    });
}

function callPopin(type, args){
    if(type == 'live_ok' || type == 'live_ok_not_premium')
    {
        //essentiel
        $('#payplug_sandbox_mode_off').siblings('.slide-button').css('left', '0%');

        $('#payplug_sandbox_mode_off').addClass('verified');
        $('#payplug_sandbox_mode_off').attr('checked', 'checked');
        $('.ppwarning.not_verified').remove();
        $('#payplug_sandbox_mode_on').removeAttr('checked');
        $('#payplug_popin').remove();
        if(type == 'live_ok_not_premium')
        {
            $('#payplug_one_click_yes').attr('checked', '');
            $('#payplug_one_click_no').attr('checked', 'checked');
        }

        $('#payplug_popin').remove();
        $('.ppoverlay').remove();
    }
    else if(type == 'confirm_ok')
    {
        $('#submitSettings').unbind('click');
        $('#submitSettings').click();

        $('#payplug_popin').remove();
        $('.ppoverlay').remove();
    }
    else if(type == 'confirm_ok_activate')
    {
        $('#payplug_show_on').siblings('.switch-selection').css('left', '31px');
        $('.switch-show').css('background-color', '#8fb32a');
        //$('#payplug_show_on').siblings('.slide-button').css('left', '31px');
        $('#payplug_show_on').attr('checked', true);
        $(this).parent().addClass('ppon');
        activate(1);
        //$('#payplug_show_on').click();
        $('#submitSettings').unbind('click');
        $('#submitSettings').click();

        $('#payplug_popin').remove();
        $('.ppoverlay').remove();
    }
    else if(type == 'confirm_ok_desactivate')
    {
        $('#payplug_show_on').siblings('.switch-selection').css('left', '2px');
        $('.switch-show').css('background-color', '#ff0000');
        $('#payplug_show_on').attr('checked', false);
        activate(0);
        $('#payplug_popin').remove();
        $('.ppoverlay').remove();
    }
    else
    {
        $('.ppoverlay').remove();
        $('#payplug_popin').remove();
        var url = $('input:hidden[name=admin_ajax_url]').val();
        var data = {_ajax: 1, popin: 1, type: type};
        if(type == 'confirm')
        {
            data = {_ajax: 1, popin: 1, type: type, sandbox: args['sandbox'], embedded: args['embedded'], one_click: args['one_click'], activate: args['activate']};
        }

        $.ajax({
            type: 'POST',
            url: url,
            dataType: 'json',
            data: data,
            error: function(jqXHR, textStatus, errorThrown) {
                alert('error CALLPOPIN');
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
            },
            success: function(result)
            {
                $('body').append(result.content);
                if(type == 'pwd') {
                    $('#payplug_popin input[type=password]').focus();
                }
                $('span.ppclose, .ppcancel').bind('click', function(){
                    $('#payplug_popin').remove();
                    $('.ppoverlay').remove();
                    if(type == 'wrong_pwd' || type == 'activate') {
                        $('#payplug_sandbox_mode_on').siblings('.slide-button').css('left', '50%');
                        $('#payplug_sandbox_mode_off').removeClass('verified');
                    }
                });
                $('#payplug_popin input[type=submit]').bind('click', function(e){
                    e.preventDefault();
                    submitPopin(this);
                });
            }
        });
    }
}

function submitPopin(input){
    $('#payplug_popin p.pperror').hide();
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var submit = input.name;
    var data = {_ajax: 1, submit: submit};
    var pwd = $('#payplug_popin input[name=pwd]').val();
    if(pwd != undefined)
        data = {_ajax: 1, submit: submit, pwd: pwd};

    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: data,
        error: function(jqXHR, textStatus, errorThrown) {
            alert('error SUBMITPOPIN');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function(response)
        {
            if(response.content == 'wrong_pwd') {
                $('#payplug_popin p.pperror').show();
            } else {
                callPopin(response.content);
            }
        }
    });
}

function callFieldset()
{
    var url = $('input:hidden[name=admin_ajax_url]').val();
    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: {
            _ajax : 1,
            check : 1,
        },
        beforeSend: function() {
            $('.checkFieldset .loader').show();
        },
        complete: function(){
            $('.checkFieldset .loader').hide();
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert('error CALLFIELDSET');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function(result)
        {
            $('.checkFieldset').html(result.content);
            $('input[name=submitCheckConfiguration]').bind('click', function(e){
                e.preventDefault();
                callFieldset();
            });
        }
    });
}

function checkPremium(go_live)
{
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var data = {_ajax: 1, checkPremium: 1};
    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: data,
        error: function(jqXHR, textStatus, errorThrown) {
            alert('error CHECK PREMIUM');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function(result)
        {
            if (go_live == false) {
                if(result == true) {
                    $('input[name=PAYPLUG_ONE_CLICK]').addClass('premium');
                    $('#payplug_one_click_yes').click();
                    $('#payplug_one_click_yes').siblings('.slide-button').css('left', '0%');
                } else {
                    callPopin('premium');
                }
            } else {
                if(result == true) {

                } else {
                    $('#payplug_one_click_no').click();
                    $('#payplug_one_click_no').siblings('.slide-button').css('left', '50%');
                }
            }

        }
    });
}