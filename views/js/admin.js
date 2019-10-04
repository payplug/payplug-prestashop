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
    $('.error-email-input').addClass('hide');
    $('.error-password-input').addClass('hide');
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

    $('#payplug_deferred_auto').bind('change', function() {
        if ($("#payplug_deferred_auto").is(':checked')) {
            $('#payplug_deferred_state').attr('disabled', false);
        } else {
            $('#payplug_deferred_state').attr('disabled', true);
            if(!$('#deferred_config_error').hasClass('hide')) {
                $('#deferred_config_error').addClass('hide');
            }
        }
    });

    $('#payplug_deferred_state').bind('change', function() {
        if (!validateDeferred()) {
            $('#payplug_admin_form form').bind('submit', disableForm());
        } else {
            if(!$('#deferred_config_error').hasClass('hide')) {
                $('#deferred_config_error').addClass('hide');
            }
        }
    });

    $('input.switch-input').bind('change', function() {
        var firstValue = $(this).parent().find('.switch-input:first').val();
        if($(this).val() == firstValue) {
            $(this).siblings('.slide-button').css('left', '0%');
        } else {
            $(this).siblings('.slide-button').css('left', '50%');
        }
    });

    $('#payplug_sandbox_right').bind('click', function(e) {
        if (($(this).attr('checked') == 'checked' || $(this).attr('checked') == true)) {
            can_be_live();
            e.preventDefault();
        } else {
            $(this).siblings('.slide-button').css('left', '0%');
        }
    });

    $('#payplug_sandbox_left').bind('click', function(e) {
        $(this).siblings('.slide-button').css('left', '50%');
    });

    $('input.validate').bind('keyup', function() {
        validate_field();
    });

    $('#payplug_show_on').bind('change', function(){
        if($(this).attr('checked') == true || $(this).attr('checked') == 'checked'){
            $(this).siblings('.switch-selection').css('left', '2px');
            $(this).attr('checked', false);
            var sandbox = $('#payplug_sandbox_left').attr('checked');
            var embedded = $('#payplug_embedded_left').attr('checked');
            var one_click = $('#payplug_one_click_left').attr('checked');
            var installment = $('#payplug_inst_left').attr('checked');
            var deferred = $('#payplug_deferred_left').attr('checked');
            var args = {
                sandbox: (sandbox == 'checked' || sandbox == true) ? 1 : 0,
                embedded: (embedded == 'checked' || embedded == true) ? 1 : 0,
                one_click: (one_click == 'checked' || one_click == true) ? 1 : 0,
                installment: (installment == 'checked' || installment == true) ? 1 : 0,
                deferred: (deferred == 'checked' || deferred == true) ? 1 : 0,
                activate: 1
            };
            callPopin('confirm', args);
        }
    });

    $('#payplug_show_off').bind('change', function(){
        if($(this).attr('checked') == true || $(this).attr('checked') == 'checked'){
            $(this).parent().removeClass('ppon');
            $(this).siblings('.switch-selection').css('left', '31px');
            $('.switch-show').css('background-color', '#00ab7a');
            $(this).attr('checked', false);
            callPopin('desactivate');
        }
    });

    $('#payplug_debug_mode_left').bind('change', function(){
        if($(this).attr('checked') == true || $(this).attr('checked') == 'checked'){
            var status = $('input[name=payplug_debug_mode]:checked').val();
            debug(status);
        }
    });

    $('#payplug_debug_mode_right').bind('change', function(){
        if($(this).attr('checked') == true || $(this).attr('checked') == 'checked'){
            var status = $('input[name=payplug_debug_mode]:checked').val();
            debug(status);
        }
    });

    $('#payplug_one_click_left').bind('click', function(e) {
        if (
            ($('#payplug_sandbox_right').attr('checked') == 'checked' || $('#payplug_sandbox_right').attr('checked') == true)
            && !$(this).hasClass('premium')
        ){
            e.preventDefault();
            checkPremium(false, 'oneclick');
        }
    });

    $('#payplug_inst_left').bind('click', function(e) {
        if (
            ($('#payplug_sandbox_right').attr('checked') == 'checked' || $('#payplug_sandbox_right').attr('checked') == true)
            && !$(this).hasClass('premium')
        ){
            e.preventDefault();
            checkPremium(false, 'installment');

        }
    });

    $('#payplug_deferred_left').bind('click', function(e) {
        if (
            ($('#payplug_sandbox_right').attr('checked') == 'checked' || $('#payplug_sandbox_right').attr('checked') == true)
            && !$(this).hasClass('premium')
        ){
            e.preventDefault();
            checkPremium(false, 'deferred');
        }
    });

    $('input[name=payplug_sandbox]').bind('change', function(e){
        // Change tips value of live / sandbox mode selected
        if($(this).val() == 0) { // Live
            $('#mode_live_tips').show();
            $('#mode_sandbox_tips').hide();
            $('#mode_live_tips').removeClass('hide');
        } else { // Sandbox
            $('#mode_sandbox_tips').show();
            $('#mode_live_tips').hide();
            $('#mode_sandbox_tips').removeClass('hide');
        }
    });

    $('input[name=payplug_embedded]').bind('change', function(e){
        // Change tips value of redirect / embedded mode selected
        if($(this).val() == 1) { // Redirect
            $('#payment_page_embedded_tips').show();
            $('#payment_page_redirect_tips').hide();
            $('#payment_page_embedded_tips').removeClass('hide');
        } else { // Embedded
            $('#payment_page_redirect_tips').show();
            $('#payment_page_embedded_tips').hide();
            $('#payment_page_redirect_tips').removeClass('hide');
        }
    });

    $('#submitSettings').bind('click', function(e) {
        if (!validate_before_submit())
        {
            return false;
        }
        if ($(this).hasClass('is_active') && $('#installment_config_error').hasClass('hide')) {
            var sandbox = $('#payplug_sandbox_left').attr('checked');
            var embedded = $('#payplug_embedded_left').attr('checked');
            var one_click = $('#payplug_one_click_left').attr('checked');
            var installment = $('#payplug_inst_left').attr('checked');
            var deferred = $('#payplug_deferred_left').attr('checked');
            var args = {
                sandbox: (sandbox == 'checked' || sandbox == true) ? 1 : 0,
                embedded: (embedded == 'checked' || embedded == true) ? 1 : 0,
                one_click: (one_click == 'checked' || one_click == true) ? 1 : 0,
                installment: (installment == 'checked' || installment == true) ? 1 : 0,
                deferred: (deferred == 'checked' || deferred == true) ? 1 : 0,
                activate: 0
            };
            e.preventDefault();
            callPopin('confirm', args);
            return false;
        } else {
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

    if ($('input[name=payplug_inst]:checked').val() == 0) {
        $('.ppinstallmentchecked').hide();
    }
    $('input[name=payplug_inst]').bind('change', function(e){
        if($(this).val() == 1) {
            $('.ppinstallmentchecked').show();
        } else {
            $('.ppinstallmentchecked').hide();
        }
    });

    if ($('input[name=payplug_deferred]:checked').val() == 0) {
        $('.ppdeferredchecked').hide();
    }
    $('input[name=payplug_deferred]').bind('change', function(e){
        if($(this).val() == 1) {
            $('.ppdeferredchecked').show();
        } else {
            $('.ppdeferredchecked').hide();
        }
    });

    showInstallments($('input[name=PAYPLUG_INST_MODE]:checked').val());
    $('input[name=PAYPLUG_INST_MODE]').bind('change', function(e){
        showInstallments(this.value);
    });

    $('#payplug_installment_min_amount').bind('keyup', function() {
        var amount = $(this).val();
        var matches = amount.match(/^[0-9]+([,|\.]?[0-9]+)?$/);
        var formatedAmount = amount.replace(',', '.');
        if (matches == null || parseFloat(formatedAmount) < 4 || parseFloat(formatedAmount) > 20000) {
            if($('#installment_config_error').hasClass('hide')) {
                $('#installment_config_error').removeClass('hide');
            }
            $('#payplug_admin_form form').bind('submit', disableForm());
        } else {
            if(!$('#installment_config_error').hasClass('hide')) {
                $('#installment_config_error').addClass('hide');
            }
            $('#payplug_admin_form form').unbind('submit', disableForm());
        }
    });

    $(document).on('keyup keypress', '#payplug_admin_form form', function(e) {
        if(e.which == 13) {
            e.preventDefault();
            return false;
        }
    });
}

function disableForm() {
    return false;
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
            alert('An error occurred while trying to login. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
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
            alert('An error occurred while trying to switch debug mode. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function(result)
        {
            $('div.module_confirmation').each(function(){
                if (!$(this).hasClass('pphide')) {
                    if($(this).parent().hasClass('bootstrap')) {
                        $(this).parent().hide(500).remove();
                    } else {
                        $(this).hide(500).remove();
                    }
                } else {
                    if($(this).parent().hasClass('bootstrap')) {
                        $(this).parent().show(500);
                    }
                    $(this).show(500);
                }
            });
            return true;
        }
    });
}

function callPopin(type, args){
    if(type == 'live_ok' || type == 'live_ok_not_premium' || type == 'live_ok_no_inst' || type == 'live_ok_no_oneclick' )
    {
        //essentiel
        $('#payplug_sandbox_right').siblings('.slide-button').css('left', '50%');

        $('#payplug_sandbox_right').attr('checked', 'checked');
        $('.ppwarning.not_verified').remove();
        $('#payplug_sandbox_left').removeAttr('checked');
        $('#payplug_popin').remove();
        if(type == 'live_ok_not_premium' || type == 'live_ok_no_oneclick')
        {
            $('#payplug_one_click_left').attr('checked', '');
            $('#payplug_one_click_no').attr('checked', 'checked');
        }
        if(type == 'live_ok_not_premium' || type == 'live_ok_no_inst')
        {
            $('#payplug_inst_left').attr('checked', '');
            $('#payplug_installment_no').attr('checked', 'checked');
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
        $('.switch-show').css('background-color', '#00ab7a');
        $('#payplug_show_on').attr('checked', true);
        $(this).parent().addClass('ppon');
        activate(1);
        $('#submitSettings').unbind('click');
        $('#submitSettings').click();

        $('#payplug_popin').remove();
        $('.ppoverlay').remove();
    }
    else if(type == 'confirm_ok_desactivate')
    {
        $('#payplug_show_on').siblings('.switch-selection').css('left', '2px');
        $('.switch-show').css('background-color', '#dd2525');
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
            data = {_ajax: 1, popin: 1, type: type, sandbox: args['sandbox'], embedded: args['embedded'], one_click: args['one_click'], installment: args['installment'], deferred: args['deferred'], activate: args['activate']};
        }
        $.ajax({
            type: 'POST',
            url: url,
            dataType: 'json',
            data: data,
            error: function(jqXHR, textStatus, errorThrown) {
                alert('An error occurred while trying to open the popin. ' +
                    'Maybe you clicked too fast before scripts are fully loaded ' +
                    'or maybe you have a different back-office url than expected.' +
                    'You will find more explanation in JS console.');
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
                $('span.ppclose, .ppcancel').bind('click', function() {
                    $('#payplug_popin').remove();
                    $('.ppoverlay').remove();
                    if(type == 'wrong_pwd' || type == 'activate') {
                        $('#payplug_sandbox_left').siblings('.slide-button').css('left', '0%');
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
            alert('An error occurred while trying to submit your settings. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
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
            alert('An error occurred while trying to refresh indicators. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
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

function checkPremium(go_live, type)
{
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var data = {_ajax: 1, checkPremium: 1};
    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: data,
        error: function(jqXHR, textStatus, errorThrown) {
            alert('An error occurred while trying to checking your premium status. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function(result)
        {
            if (go_live == false) {
                if (result['can_save_cards'] == true && type == 'oneclick') {
                    $('input[name=payplug_one_click]').addClass('premium');
                    $('#payplug_one_click_left').click();
                    $('#payplug_one_click_left').siblings('.slide-button').css('left', '0%');
                }
                if (result['can_create_installment_plan'] == true && type == 'installment') {
                    $('input[name=payplug_inst]').addClass('premium');
                    $('#payplug_inst_left').click();
                    $('#payplug_inst_left').siblings('.slide-button').css('left', '0%');
                }
                if (result['can_create_deferred_payment'] == true && type == 'deferred') {
                    $('input[name=payplug_deferred]').addClass('premium');
                    $('#payplug_deferred_left').click();
                    $('#payplug_deferred_left').siblings('.slide-button').css('left', '0%');
                }
                if ((result['can_save_cards'] == false && type == 'oneclick')
                    || (result['can_create_installment_plan'] == false && type == 'installment')
                    || (result['can_create_deferred_payment'] == false && type == 'deferred')) {
                    callPopin('premium');
                }
            } else {
                if (result['can_save_cards'] == false) {
                    $('#payplug_one_click_right').click();
                    $('#payplug_one_click_right').siblings('.slide-button').css('left', '50%');
                }
                if (result['can_create_installment_plan'] == false) {
                    $('#payplug_inst_right').click();
                    $('#payplug_inst_right').siblings('.slide-button').css('left', '50%');
                }
                if (result['can_create_deferred_payment'] == false) {
                    $('#payplug_deferred_right').click();
                    $('#payplug_deferred_right').siblings('.slide-button').css('left', '50%');
                }
            }
        }
    });
}

function showInstallments(installment_value)
{
    $('.ppinstallments').hide();
    $('.pp'+installment_value+'installments').show();
}
function validateDeferred()
{
    var is_auto = $("#payplug_deferred_auto").is(':checked');
    var has_state = parseInt($('#payplug_deferred_state').val()) > 0 ;

    if (is_auto) {
        if (!has_state) {
            if($('#deferred_config_error').hasClass('hide')) {
                $('#deferred_config_error').removeClass('hide');
            }
            return false;
        }
    }
    return true;
}

function validate_before_submit()
{
    var flag = true;
    if (!validateDeferred()) {
        flag = false;
    }
    if (!flag) {
        //$('#payplug_admin_form form').bind('submit', disableForm());
    }
    return flag;
}

function can_be_live()
{
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var data = {_ajax: 1, has_live_key: 1};
    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: data,
        error: function(jqXHR, textStatus, errorThrown) {
            alert('An error occurred while trying to checking your verified status. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function(response)
        {
            if (response.result) {
                switch_to_live();
            } else {
                callPopin('pwd');
            }
        }
    });
}

function switch_to_live()
{
    $('#payplug_sandbox_right').attr('checked', 'checked');
    $('#payplug_sandbox_right').siblings('.slide-button').css('left', '50%');
}
