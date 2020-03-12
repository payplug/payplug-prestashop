/**
 * 2013 - 2020 PayPlug SAS
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
 *  @copyright 2013 - 2020 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */
var $document, $window, payplug = {
    init: function () {
        $document = $(document);
        $window = $(window);

        for (const section in payplug) {
            if (section != 'init') {
                payplug[section]['init']();
            }
        }
    },
    form: {
        props: {
            identifier: 'payplug',
            query: null,
            data: {},
        },
        init: function () {
            var {form} = payplug;
            var {identifier} = form.props;

            $document.on('click', 'form.' + identifier + ' button[type="submit"]', form.submit)
                .on('click', 'button[name="confirm"]', form.save);
        },
        submit: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var $button = $(this);
            if ($button.is('.payplugButton-disabled')) {
                return false;
            }

            var {form} = payplug;

            form.hydrate();

            var data = {
                _ajax: 1,
                popin: 1,
                type: 'confirm',
                sandbox: form.props.data['payplug_sandbox'],
                embedded: form.props.data['payplug_embedded'],
                oney: form.props.data['payplug_oney'],
                one_click: form.props.data['payplug_one_click'],
                installment: form.props.data['payplug_inst'],
                deferred: form.props.data['payplug_deferred'],
                activate: 0
            };

            if (form.props.query != null) {
                form.props.query.abort();
                form.props.query = null;
            }

            form.props.query = $.ajax({
                type: 'POST',
                url: admin_ajax_url,
                dataType: 'json',
                data: data,
                error: function (jqXHR, textStatus, errorThrown) {
                    if (textStatus != 'abort') {
                        alert('An error occurred while trying to refresh indicators. ' +
                            'Maybe you clicked too fast before scripts are fully loaded ' +
                            'or maybe you have a different back-office url than expected.' +
                            'You will find more explanation in JS console.');
                        console.log(jqXHR);
                        console.log(textStatus);
                        console.log(errorThrown);
                    }
                },
                success: function (result) {
                    if (typeof result.content != 'undefined') {
                        var {popup} = payplug.tools;
                        popup.set(result.content);
                    }
                }
            });
        },
        hydrate: function () {
            var {form} = payplug;
            var {identifier} = form.props;
            var data = {};
            var $form = $('form.' + identifier);
            var $input = $form.find('input');
            var $select = $form.find('select');

            $input.each(function () {
                var $elem = $(this),
                    name = $elem.attr('name'),
                    type = $elem.attr('type'),
                    value = $elem.val();

                switch (type) {
                    case 'radio' :
                        if ($elem.prop('checked')) {
                            data[name] = value;
                        }
                        break;
                    case 'checkbox' :
                        data[name] = $elem.prop('checked');
                        break;
                    default :
                        data[name] = value;
                        break;
                }
            });
            $select.each(function () {
                var $elem = $(this);
                data[$elem.attr('name')] = $elem.val();
            });

            form.props.data = data;
        },
        save: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var {form} = payplug;
            var {data} = form.props;

            data['_ajax'] = 1;
            data['submitSettings'] = 1;

            if (form.props.query != null) {
                form.props.query.abort();
                form.props.query = null;
            }

            form.props.query = $.ajax({
                type: 'POST',
                url: admin_ajax_url,
                dataType: 'json',
                data: data,
                error: function (jqXHR, textStatus, errorThrown) {
                    if (textStatus != 'abort') {
                        alert('An error occurred while trying to refresh indicators. ' +
                            'Maybe you clicked too fast before scripts are fully loaded ' +
                            'or maybe you have a different back-office url than expected.' +
                            'You will find more explanation in JS console.');
                        console.log(jqXHR);
                        console.log(textStatus);
                        console.log(errorThrown);
                    }
                },
                success: function (result) {
                    if (typeof result.content != 'undefined') {
                        var {popup} = payplug.tools;
                        popup.set(result.popin);
                        $('form.payplug').replaceWith(result.content);
                        $window.trigger('load');
                    }
                }
            });
        },
    },
    config: {
        props: {
            identifier: 'payplugConfig',
            query: null
        },
        init: function () {
            var {config} = payplug,
                {identifier} = config.props;
            $document.on('click', '.' + identifier + '_check', config.check);
        },
        check: function (event) {
            event.preventDefault();
            var {config} = payplug;
            config.refresh();
        },
        refresh: function () {
            var {config} = payplug,
                {identifier} = config.props;

            if (config.props.query != null) {
                config.props.query.abort();
                config.props.query = null;
            }

            config.props.query = $.ajax({
                type: 'POST',
                url: admin_ajax_url,
                dataType: 'json',
                data: {
                    _ajax: 1,
                    check: 1,
                },
                beforeSend: function () {
                    payplug.tools.loader.show($('.' + identifier));
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    if (textStatus != 'abort') {
                        alert('An error occurred while trying to refresh indicators. ' +
                            'Maybe you clicked too fast before scripts are fully loaded ' +
                            'or maybe you have a different back-office url than expected.' +
                            'You will find more explanation in JS console.');
                        console.log(jqXHR);
                        console.log(textStatus);
                        console.log(errorThrown);
                        payplug.tools.loader.hide($('.' + identifier));
                    }
                },
                success: function (result) {
                    $('.' + identifier).replaceWith(result.content);
                    payplug.tools.loader.hide($('.' + identifier));
                }
            });
        },
    },
    show: {
        props: {
            identifier: 'payplugShow',
            query: null,
        },
        init: function () {
            var {show} = payplug,
                {identifier} = show.props;
            $document.on('switchSelected', '.' + identifier + ' input', show.change);
        },
        change: function (event) {
            var {show} = payplug,
                $input = $(this),
                enable = parseInt($input.val());

            if (enable) {
                show.enable();
            } else {
                event.stopPropagation();
                event.preventDefault();
                show.disable();
            }
        },
        enable: function () {
            var {form} = payplug,
                {identifier} = form.props,
                $submit = $('form.' + identifier).find('button[type="submit"]');

            $submit.trigger('click');
        },
        disable: function () {
            var {show} = payplug,
                data = {
                    _ajax: 1,
                    popin: 1,
                    type: 'desactivate'
                };

            if (show.props.query != null) {
                show.props.query.abort();
                show.props.query = null;
            }

            show.props.query = $.ajax({
                type: 'POST',
                url: admin_ajax_url,
                dataType: 'json',
                data: data,
                error: function (jqXHR, textStatus, errorThrown) {
                    if (textStatus != 'abort') {
                        alert('An error occurred while trying to refresh indicators. ' +
                            'Maybe you clicked too fast before scripts are fully loaded ' +
                            'or maybe you have a different back-office url than expected.' +
                            'You will find more explanation in JS console.');
                        console.log(jqXHR);
                        console.log(textStatus);
                        console.log(errorThrown);
                    }
                },
                success: function (result) {
                    if (typeof result.content != 'undefined') {
                        var {popup} = payplug.tools;
                        popup.set(result.content);
                    }
                }
            });
        },
    },
    login: {
        props: {
            identifier: 'payplugLogin',
            query: null
        },
        init: function () {
            var {login} = payplug,
                {identifier} = login.props;
            $document.on('click', '.' + identifier + '_login', login.login)
                .on('click', '.' + identifier + '_logout', login.logout)
                .on('click', 'button[name=password]', login.password);
        },
        login: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var {login} = payplug,
                {identifier} = login.props,
                data = {
                    _ajax: 1,
                    log: 1,
                    submitAccount: 1,
                    PAYPLUG_EMAIL: $('input[name=PAYPLUG_EMAIL]').val(),
                    PAYPLUG_PASSWORD: $('input[name=PAYPLUG_PASSWORD]').val(),
                }

            if (login.props.query != null) {
                login.props.query.abort();
                login.props.query = null;
            }

            login.props.query = $.ajax({
                type: 'POST',
                url: admin_ajax_url,
                dataType: 'json',
                data: data,
                beforeSend: function () {
                    payplug.tools.loader.show($('.' + identifier));
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    if (textStatus != 'abort') {
                        alert('An error occurred while trying to refresh indicators. ' +
                            'Maybe you clicked too fast before scripts are fully loaded ' +
                            'or maybe you have a different back-office url than expected.' +
                            'You will find more explanation in JS console.');
                        console.log(jqXHR);
                        console.log(textStatus);
                        console.log(errorThrown);
                        payplug.tools.loader.hide($('.' + identifier));
                    }
                },
                success: function (result) {
                    if (typeof result.content != 'undefined' && result.content) {
                        $('form.payplug').replaceWith(result.content);
                        $window.trigger('load');
                    } else if (typeof result.error != 'undefined' && result.error) {
                        var $error = '<div class="payplugPopup_row">' +
                            '<p>' + result.error + '</p>' +
                            '<div class="payplugPopup_footer payplugPopup_footer-center">' +
                            '<button type="button" class="payplugButton payplugButton-green payplugButton-close">Ok</button>' +
                            '</div>' +
                            '</div>';
                        payplug.tools.popup.set($error);
                    }
                }
            });
        },
        logout: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var {login} = payplug,
                {identifier} = login.props,
                data = {
                    _ajax: 1,
                    submitDisconnect: 1,
                }

            if (login.props.query != null) {
                login.props.query.abort();
                login.props.query = null;
            }

            login.props.query = $.ajax({
                type: 'POST',
                url: admin_ajax_url,
                dataType: 'json',
                data: data,
                beforeSend: function () {
                    payplug.tools.loader.show($('.' + identifier));
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    if (textStatus != 'abort') {
                        alert('An error occurred while trying to refresh indicators. ' +
                            'Maybe you clicked too fast before scripts are fully loaded ' +
                            'or maybe you have a different back-office url than expected.' +
                            'You will find more explanation in JS console.');
                        console.log(jqXHR);
                        console.log(textStatus);
                        console.log(errorThrown);
                        payplug.tools.loader.hide($('.' + identifier));
                    }
                },
                success: function (result) {
                    $('form.payplug').replaceWith(result.content);
                }
            });
        },
        password: function (event) {
            event.preventDefault();
            event.stopPropagation();

            $('.payplugPopup_error').html('');

            var {login} = payplug;
            var data = {
                _ajax: 1,
                submitPwd: 1,
                password: $('input[name=reload_password]').val()
            };

            if (login.props.query != null) {
                login.props.query.abort();
                login.props.query = null;
            }

            login.props.query = $.ajax({
                type: 'POST',
                url: admin_ajax_url,
                dataType: 'json',
                data: data,
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('An error occurred while trying to checking your premium status. ' +
                        'Maybe you clicked too fast before scripts are fully loaded ' +
                        'or maybe you have a different back-office url than expected.' +
                        'You will find more explanation in JS console.');
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                },
                success: function (result) {
                    if(typeof result.error != 'undefined' && result.error) {
                        $('.payplugPopup_error').html(result.error);
                    } else if(typeof result.popin != 'undefined' && result.popin) {
                        var {popup} = payplug.tools;
                        popup.set(result.popin);
                    } else if(typeof result.content != 'undefined' && result.content) {
                        var {popup} = payplug.tools;
                        popup.close();
                    }
                }
            });
        },
        reload: function () {
            var {login} = payplug;

            var data = {
                _ajax: 1,
                popin: 1,
                type: 'pwd'
            };

            if (login.props.query != null) {
                login.props.query.abort();
                login.props.query = null;
            }

            login.props.query = $.ajax({
                type: 'POST',
                url: admin_ajax_url,
                dataType: 'json',
                data: data,
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('An error occurred while trying to checking your premium status. ' +
                        'Maybe you clicked too fast before scripts are fully loaded ' +
                        'or maybe you have a different back-office url than expected.' +
                        'You will find more explanation in JS console.');
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                },
                success: function (result) {
                    if (typeof result.content != 'undefined') {
                        var {popup} = payplug.tools;
                        popup.set(result.content);
                    }
                }
            });
        },
        loader: {
            props: {
                identifer: 'login_loader',
            },
            hide: function () {
                var obj = this, $loader = $('.' + obj.props.identifer);
                $loader.removeClass(obj.props.identifer + '-visible');
                setTimeout(function () {
                    $loader.removeClass(obj.props.identifer + '-on');
                }, 100);
            },
            show: function () {
                var obj = this, $loader = $('.' + obj.props.identifer);
                $loader.addClass(obj.props.identifer + '-on');
                setTimeout(function () {
                    $loader.addClass(obj.props.identifer + '-visible');
                }, 100);
            }
        }
    },
    settings: {
        props: {
            identifier: 'payplugSettings',
            query: null,
        },
        init: function () {
            var {settings} = payplug,
                {identifier} = settings.props;
            $document.on('switchSelected', '.' + identifier + ' input', settings.change);
            $window.on('load', settings.load);
        },
        load: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var {settings} = payplug;

            if (settings.props.query != null) {
                settings.props.query.abort();
                settings.props.query = null;
            }

            settings.props.query = $.ajax({
                type: 'POST',
                url: admin_ajax_url,
                dataType: 'json',
                data: {_ajax: 1, checkPremium: 1},
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('An error occurred while trying to checking your premium status. ' +
                        'Maybe you clicked too fast before scripts are fully loaded ' +
                        'or maybe you have a different back-office url than expected.' +
                        'You will find more explanation in JS console.');
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                },
                success: function (result) {
                    for (permission in result) {
                        var $input = $('input[name=' + permission + ']');
                        if ($input.length) {
                            var {switcher} = payplug.tools,
                                $switcher = $input.parents('.' + switcher.props.identifier),
                                is_allowed = result[permission];
                            $switcher.attr('data-allowed', (is_allowed ? 1 : 0));
                        }
                    }
                }
            });
        },
        change: function () {
            var {switcher} = payplug.tools,
                {settings} = payplug,
                {identifier} = switcher.props;

            var $input = $(this),
                $switcher = $input.parents('.' + identifier),
                value = parseInt($input.val()),
                name = $input.attr('name'),
                allowed = $switcher.attr('data-allowed');

            var is_sandbox = parseInt($('input[name=payplug_sandbox]:checked').val());

            if (name == 'payplug_sandbox' && !is_sandbox) {
                switcher.left($switcher, true);
                return settings.live();
            }

            if (!value) {
                return;
            }

            if (!is_sandbox && typeof allowed != 'undefined' && !parseInt(allowed)) {
                settings.disable($switcher)
            }
        },
        reset: function () {
            var {switcher} = payplug.tools,
                {settings} = payplug,
                {identifier} = settings.props,
                s_identifier = switcher.props.identifier;

            var $options = $('.' + identifier).find('.' + s_identifier);

            $options.each(function () {
                var $switcher = $(this);
                var allowed = $switcher.attr('data-allowed');
                if (typeof allowed != 'undefined' && !parseInt(allowed)) {
                    switcher.right($switcher);
                }
            });
        },
        live: function () {
            var {settings} = payplug;

            if (settings.props.query != null) {
                settings.props.query.abort();
                settings.props.query = null;
            }

            settings.props.query = $.ajax({
                type: 'POST',
                url: admin_ajax_url,
                dataType: 'json',
                data: {_ajax: 1, has_live_key: 1},
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('An error occurred while trying to checking your verified status. ' +
                        'Maybe you clicked too fast before scripts are fully loaded ' +
                        'or maybe you have a different back-office url than expected.' +
                        'You will find more explanation in JS console.');
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                },
                success: function (response) {
                    if (response.result) {
                        settings.reset();
                        var {switcher} = payplug.tools,
                            $switcher = $('input[name=payplug_sandbox]').parents('.'+switcher.props.identifier);
                        switcher.right($switcher,true);
                    } else {
                        var {login} = payplug;
                        login.reload();
                    }
                }
            });
        },
        disable: function ($switcher) {
            var {switcher} = payplug.tools;
            var {settings} = payplug;

            var data = {
                _ajax: 1,
                popin: 1,
                type: 'premium'
            };

            switcher.right($switcher);

            if (settings.props.query != null) {
                settings.props.query.abort();
                settings.props.query = null;
            }

            settings.props.query = $.ajax({
                type: 'POST',
                url: admin_ajax_url,
                dataType: 'json',
                data: data,
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('An error occurred while trying to checking your premium status. ' +
                        'Maybe you clicked too fast before scripts are fully loaded ' +
                        'or maybe you have a different back-office url than expected.' +
                        'You will find more explanation in JS console.');
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                },
                success: function (result) {
                    if (typeof result.content != 'undefined') {
                        var {popup} = payplug.tools;
                        popup.set(result.content);
                    }
                }
            });
        },
    },
    installment: {
        props: {
            identifier: 'payplugInstallment',
            query: null,
            limits: {
                min: 4,
                max: 20000,
            }
        },
        init: function () {
            var {installment} = payplug;
            $document.on('change', 'input[name=PAYPLUG_INST_MODE]', installment.select)
                .on('keyup', 'input[name=PAYPLUG_INST_MIN_AMOUNT]', installment.check);
        },
        select: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var {installment} = payplug,
                {identifier} = installment.props;

            var inst = $('input[name=PAYPLUG_INST_MODE]:checked').val();

            $('.' + identifier + '_schedule').removeClass(identifier + '_schedule-select');
            $('.' + identifier + '_schedule-x' + inst).addClass(identifier + '_schedule-select');
        },
        check: function (event) {
            var {installment} = payplug,
                {identifier, limits} = installment.props,
                amount = $(this).val();

            if (limits.min > amount || amount > limits.max) {
                $('.' + identifier + '_amount').find('span').show();
            } else {
                $('.' + identifier + '_amount').find('span').hide();
            }
        },
    },
    tools: {
        init: function () {
            this.switcher.init();
            this.popup.init();
        },
        loader: {
            props: {
                identifer: 'payplugLoader',
            },
            hide: function (context) {
                var obj = this,
                    $loader = context.find('.' + obj.props.identifer);
                $loader.removeClass(obj.props.identifer + '-visible');
                setTimeout(function () {
                    $loader.removeClass(obj.props.identifer + '-on');
                }, 100);
            },
            show: function (context) {
                console.log(context);
                var obj = this,
                    $loader = context.find('.' + obj.props.identifer);
                $loader.addClass(obj.props.identifer + '-on');
                setTimeout(function () {
                    $loader.addClass(obj.props.identifer + '-visible');
                }, 100);
            }
        },
        switcher: {
            props: {
                identifier: 'payplugSwitch'
            },
            init: function () {
                var switcher = this,
                    {identifier} = switcher.props;
                $document.on('click', '.' + identifier, switcher.toggle)
                    .on('click', '.' + identifier + '_label', switcher.select);
            },
            toggle: function (event) {
                event.preventDefault();
                event.stopPropagation();
                var {switcher} = payplug.tools,
                    {identifier} = switcher.props,
                    $switch = $(this),
                    is_right = $switch.is('.' + identifier + '-right');

                if ($switch.is('.' + identifier + '-disabled')) {
                    return;
                }

                if (is_right) {
                    switcher.left($switch);
                } else {
                    switcher.right($switch);
                }
            },
            select: function (event) {
                event.preventDefault();
                event.stopPropagation();
                var {switcher} = payplug.tools,
                    {identifier} = switcher.props,
                    $label = $(this),
                    id = $label.attr('for').replace('_left', '').replace('_right', ''),
                    is_right = $label.is('.' + identifier + '_label-right'),
                    $switch = $label.parents('.' + identifier),
                    $tips = null;

                if ($switch.is('.' + identifier + '-disabled')) {
                    return;
                }

                if (is_right) {
                    if (!$switch.is('.' + identifier + '-right')) {
                        switcher.right($switch);
                    }
                } else {
                    if ($switch.is('.' + identifier + '-right')) {
                        switcher.left($switch);
                        if ($tips) {
                            $tips.find('.payplugTips_item-left').show();
                        }
                    }
                }
            },
            right: function (target,withoutEvent) {
                var {switcher} = payplug.tools,
                    {identifier} = switcher.props;
                target.addClass(identifier + '-right');
                target.find('input').removeAttr('checked').prop('checked', false);
                var name = target.find('input').eq(0).attr('name'),
                    $tips = $('.payplugTips-' + name);
                if ($tips.length) {
                    $tips.find('.payplugTips_item').hide();
                    $tips.find('.payplugTips_item-right').show();
                }

                var $selected = target.find('input[value=0]');
                $selected.attr('checked', 'checked')
                if(typeof withoutEvent == 'undefined' || !withoutEvent){
                    $selected.trigger('switchSelected');
                }
            },
            left: function (target) {
                var {switcher} = payplug.tools,
                    {identifier} = switcher.props;
                target.removeClass(identifier + '-right');
                target.find('input').removeAttr('checked').prop('checked', false);

                var name = target.find('input').eq(0).attr('name'),
                    $tips = $('.payplugTips-' + name);
                if ($tips.length) {
                    $tips.find('.payplugTips_item').hide();
                    $tips.find('.payplugTips_item-left').show();
                }

                var $selected = target.find('input[value=1]');
                $selected.attr('checked', 'checked')
                if(typeof withoutEvent == 'undefined' || !withoutEvent){
                    $selected.trigger('switchSelected');
                }
            },
            able: function (target) {
                var {switcher} = payplug.tools,
                    {identifier} = switcher.props;
                target.removeClass(identifier + '-disabled');
            },
            disable: function (target) {
                var {switcher} = payplug.tools,
                    {identifier} = switcher.props;
                target.addClass(identifier + '-disabled');
            },
        },
        popup: {
            props: {
                identifier: 'payplugPopup',
            },
            init: function () {
                var {popup} = payplug.tools,
                    {identifier} = popup.props;

                $document.on('click', '.' + identifier + '_close', popup.close)
                    .on('click', '.' + identifier + ' .payplugButton-close', popup.close)
                    .on('click', function (event) {
                        var $clicked = $(event.target);
                        if ($clicked.is('.' + identifier) && $('.' + identifier).is('.' + identifier + '-open')) {
                            popup.close();
                        }
                    });
            },
            set: function (content) {
                var {popup} = payplug.tools,
                    {identifier, loaded} = popup.props;

                if (!$('.' + identifier).length) {
                    popup.create();
                }
                popup.hydrate(content);
                popup.open();
            },
            open: function () {
                var {popup} = payplug.tools,
                    {identifier} = popup.props,
                    $popup = $('.' + identifier);

                $popup.addClass(identifier + '-open');
                window.setTimeout(function () {
                    $popup.addClass(identifier + '-show');
                }, 0);
            },
            close: function () {
                var {popup} = payplug.tools,
                    {identifier} = popup.props,
                    $popup = $('.' + identifier);

                $popup.removeClass(identifier + '-show');
                window.setTimeout(function () {
                    $popup.removeClass(identifier + '-open');
                    popup.remove();
                }, 500);
            },
            create: function () {
                var {popup} = payplug.tools,
                    {identifier} = popup.props,
                    html = '<div class="' + identifier + '"><button class="' + identifier + '_close"></button><div class="' + identifier + '_content"></div></div>';
                $('body').append(html);
            },
            remove: function () {
                var {popup} = payplug.tools,
                    {identifier} = popup.props,
                    $popup = $('.' + identifier);

                $popup.remove();
            },
            hydrate: function (content) {
                var {popup} = payplug.tools,
                    {identifier} = popup.props;
                $('.' + identifier + '_content').html(content);
            }
        }
    },
};

$(document).ready(function () {
    payplug.init();
    admin_start();
});

function validate_isEmail(s) {
    var reg = /^[a-z\p{L}0-9!#$%&'*+\/=?^`{}|~_-]+[.a-z\p{L}0-9!#$%&'*+\/=?^`{}|~_-]*@[a-z\p{L}0-9]+[._a-z\p{L}0-9-]*\.[a-z\p{L}0-9]+$/i;
    return reg.test(s);
}

function validate_isPasswd(s) {
    return (s.length >= 8 && s.length < 255);
}

function validate_field() {
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
        $('input[name=submitAccount]').attr('disabled', 'disabled');
        $('input[name=submitAccount]').addClass('ppdisabled');
    }
}

function admin_start() {
    $('#payplug_sandbox_right').bind('click', function (e) {
        if (($(this).attr('checked') == 'checked' || $(this).attr('checked') == true)) {
            can_be_live();
            e.preventDefault();
        } else {
            $(this).siblings('.slide-button').css('left', '0%');
        }
    });

    $('#payplug_sandbox_left').bind('click', function (e) {
        $(this).siblings('.slide-button').css('left', '50%');
    });

    $('#payplug_show_on').bind('change', function () {
        if ($(this).attr('checked') == true || $(this).attr('checked') == 'checked') {
            $(this).siblings('.switch-selection').css('left', '2px');
            $(this).attr('checked', false);
            var sandbox = $('#payplug_sandbox_left').attr('checked');
            var embedded = $('#payplug_embedded_left').attr('checked');
            var one_click = $('#payplug_one_click_left').attr('checked');
            var installment = $('#payplug_inst_left').attr('checked');
            var deferred = $('#payplug_deferred_left').attr('checked');
            var oney = $('#payplug_oney_left').attr('checked');
            var args = {
                sandbox: (sandbox == 'checked' || sandbox == true) ? 1 : 0,
                embedded: (embedded == 'checked' || embedded == true) ? 1 : 0,
                one_click: (one_click == 'checked' || one_click == true) ? 1 : 0,
                installment: (installment == 'checked' || installment == true) ? 1 : 0,
                deferred: (deferred == 'checked' || deferred == true) ? 1 : 0,
                oney: (oney == 'checked' || oney == true) ? 1 : 0,
                activate: 1
            };
            callPopin('confirm', args);
        }
    });

    $('#payplug_show_off').bind('change', function () {
        if ($(this).attr('checked') == true || $(this).attr('checked') == 'checked') {
            $(this).parent().removeClass('ppon');
            $(this).siblings('.switch-selection').css('left', '31px');
            $('.switch-show').css('background-color', '#00ab7a');
            $(this).attr('checked', false);
            callPopin('desactivate');
        }
    });

    $('#payplug_one_click_left').bind('click', function (e) {
        if (
            ($('#payplug_sandbox_right').attr('checked') == 'checked' || $('#payplug_sandbox_right').attr('checked') == true)
            && !$(this).hasClass('premium')
        ) {
            e.preventDefault();
            checkPremium(false, 'oneclick');
        }
    });

    $('#payplug_inst_left').bind('click', function (e) {
        if (
            ($('#payplug_sandbox_right').attr('checked') == 'checked' || $('#payplug_sandbox_right').attr('checked') == true)
            && !$(this).hasClass('premium')
        ) {
            e.preventDefault();
            checkPremium(false, 'installment');

        }
    });

    $('#payplug_deferred_left').bind('click', function (e) {
        if (
            ($('#payplug_sandbox_right').attr('checked') == 'checked' || $('#payplug_sandbox_right').attr('checked') == true)
            && !$(this).hasClass('premium')
        ) {
            e.preventDefault();
            checkPremium(false, 'deferred');
        }
    });

    $('input[name=payplug_sandbox]').bind('change', function (e) {
        // Change tips value of live / sandbox mode selected
        if ($(this).val() == 0) { // Live
            $('#mode_live_tips').show();
            $('#mode_sandbox_tips').hide();
            $('#mode_live_tips').removeClass('hide');
        } else { // Sandbox
            $('#mode_sandbox_tips').show();
            $('#mode_live_tips').hide();
            $('#mode_sandbox_tips').removeClass('hide');
        }
    });

    $('input[name=payplug_embedded]').bind('change', function (e) {
        // Change tips value of redirect / embedded mode selected
        if ($(this).val() == 1) { // Redirect
            $('#payment_page_embedded_tips').show();
            $('#payment_page_redirect_tips').hide();
            $('#payment_page_embedded_tips').removeClass('hide');
        } else { // Embedded
            $('#payment_page_redirect_tips').show();
            $('#payment_page_embedded_tips').hide();
            $('#payment_page_redirect_tips').removeClass('hide');
        }
    });

    $('#submitSettings').bind('click', function (e) {
        if (!validate_before_submit()) {
            return false;
        }
        if ($(this).hasClass('is_active') && $('#installment_config_error').hasClass('hide')) {
            var sandbox = $('#payplug_sandbox_left').attr('checked');
            var embedded = $('#payplug_embedded_left').attr('checked');
            var one_click = $('#payplug_one_click_left').attr('checked');
            var installment = $('#payplug_inst_left').attr('checked');
            var deferred = $('#payplug_deferred_left').attr('checked');
            var oney = $('#payplug_oney_left').attr('checked');
            var args = {
                sandbox: (sandbox == 'checked' || sandbox == true) ? 1 : 0,
                embedded: (embedded == 'checked' || embedded == true) ? 1 : 0,
                one_click: (one_click == 'checked' || one_click == true) ? 1 : 0,
                installment: (installment == 'checked' || installment == true) ? 1 : 0,
                deferred: (deferred == 'checked' || deferred == true) ? 1 : 0,
                oney: (oney == 'checked' || oney == true) ? 1 : 0,
                activate: 0
            };
            e.preventDefault();
            callPopin('confirm', args);
            return false;
        } else {
            return false;
        }
    });

    $('input[name=submitCheckConfiguration]').bind('click', function (e) {
        e.preventDefault();
        callFieldset();
    });

    $('input[name=submitAccount]').bind('click', function (e) {
        e.preventDefault();
        login();
    });

    if ($('input[name=payplug_inst]:checked').val() == 0) {
        $('.ppinstallmentchecked').hide();
    }
    $('input[name=payplug_inst]').bind('change', function (e) {
        if ($(this).val() == 1) {
            $('.ppinstallmentchecked').show();
        } else {
            $('.ppinstallmentchecked').hide();
        }
    });

    if ($('input[name=payplug_deferred]:checked').val() == 0) {
        $('.ppdeferredchecked').hide();
    }
    $('input[name=payplug_deferred]').bind('change', function (e) {
        if ($(this).val() == 1) {
            $('.ppdeferredchecked').show();
        } else {
            $('.ppdeferredchecked').hide();
        }
    });

    showInstallments($('input[name=PAYPLUG_INST_MODE]:checked').val());
    $('input[name=PAYPLUG_INST_MODE]').bind('change', function (e) {
        showInstallments(this.value);
    });

    $('#payplug_installment_min_amount').bind('keyup', function () {
        var amount = $(this).val();
        var matches = amount.match(/^[0-9]+([,|\.]?[0-9]+)?$/);
        var formatedAmount = amount.replace(',', '.');
        if (matches == null || parseFloat(formatedAmount) < 4 || parseFloat(formatedAmount) > 20000) {
            if ($('#installment_config_error').hasClass('hide')) {
                $('#installment_config_error').removeClass('hide');
            }
            $('#payplug_admin_form form').bind('submit', disableForm());
        } else {
            if (!$('#installment_config_error').hasClass('hide')) {
                $('#installment_config_error').addClass('hide');
            }
            $('#payplug_admin_form form').unbind('submit', disableForm());
        }
    });

    $(document).on('keyup keypress', '#payplug_admin_form form', function (e) {
        if (e.which == 13) {
            e.preventDefault();
            return false;
        }
    });
}

function disableForm() {
    return false;
}

function login() {
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var email = $('input[name=PAYPLUG_EMAIL]').val();
    var pwd = $('input[name=PAYPLUG_PASSWORD]').val();
    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: {
            _ajax: 1,
            log: 1,
            submitAccount: 1,
            PAYPLUG_EMAIL: email,
            PAYPLUG_PASSWORD: pwd,
        },
        beforeSend: function () {
            $('.panel-login .loader').show();
        },
        complete: function () {
            $('.panel-login .loader').hide();
        },
        error: function (jqXHR, textStatus, errorThrown) {
            alert('An error occurred while trying to login. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function (result) {
            $('div.panel-remove').remove();
            $('p.interpanel').after(result.content);
            admin_start();
            callFieldset();
        }
    });
}

function activate(enable) {
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var data = {_ajax: 1, en: enable};

    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: {
            _ajax: 1,
            en: enable,
        },
        success: function () {
            if (enable == 1)
                $('#submitSettings').addClass('is_active');
            else
                $('#submitSettings').removeClass('is_active');
        }
    });
}

function debug(status) {
    var url = $('input:hidden[name=admin_ajax_url]').val();
    data = {_ajax: 1, db: status};
    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: {
            _ajax: 1,
            db: status,
        },
        error: function (jqXHR, textStatus, errorThrown) {
            alert('An error occurred while trying to switch debug mode. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function (result) {
            $('div.module_confirmation').each(function () {
                if (!$(this).hasClass('pphide')) {
                    if ($(this).parent().hasClass('bootstrap')) {
                        $(this).parent().hide(500).remove();
                    } else {
                        $(this).hide(500).remove();
                    }
                } else {
                    if ($(this).parent().hasClass('bootstrap')) {
                        $(this).parent().show(500);
                    }
                    $(this).show(500);
                }
            });
            return true;
        }
    });
}

function callPopin(type, args) {
    if (type == 'live_ok' || type == 'live_ok_not_premium' || type == 'live_ok_no_inst' || type == 'live_ok_no_oneclick') {
        //essentiel
        $('#payplug_sandbox_right').siblings('.slide-button').css('left', '50%');

        $('#payplug_sandbox_right').attr('checked', 'checked');
        $('.ppwarning.not_verified').remove();
        $('#payplug_sandbox_left').removeAttr('checked');
        $('#payplug_popin').remove();
        if (type == 'live_ok_not_premium' || type == 'live_ok_no_oneclick') {
            $('#payplug_one_click_left').attr('checked', '');
            $('#payplug_one_click_no').attr('checked', 'checked');
        }
        if (type == 'live_ok_not_premium' || type == 'live_ok_no_inst') {
            $('#payplug_inst_left').attr('checked', '');
            $('#payplug_installment_no').attr('checked', 'checked');
        }

        $('#payplug_popin').remove();
        $('.ppoverlay').remove();
    } else if (type == 'confirm_ok') {
        $('#submitSettings').unbind('click');
        $('#submitSettings').click();

        $('#payplug_popin').remove();
        $('.ppoverlay').remove();
    } else if (type == 'confirm_ok_activate') {
        $('#payplug_show_on').siblings('.switch-selection').css('left', '31px');
        $('.switch-show').css('background-color', '#00ab7a');
        $('#payplug_show_on').attr('checked', true);
        $(this).parent().addClass('ppon');
        activate(1);
        $('#submitSettings').unbind('click');
        $('#submitSettings').click();

        $('#payplug_popin').remove();
        $('.ppoverlay').remove();
    } else if (type == 'confirm_ok_desactivate') {
        $('#payplug_show_on').siblings('.switch-selection').css('left', '2px');
        $('.switch-show').css('background-color', '#dd2525');
        $('#payplug_show_on').attr('checked', false);
        activate(0);
        $('#payplug_popin').remove();
        $('.ppoverlay').remove();
    } else {
        $('.ppoverlay').remove();
        $('#payplug_popin').remove();
        var url = $('input:hidden[name=admin_ajax_url]').val();
        var data = {_ajax: 1, popin: 1, type: type};
        if (type == 'confirm') {
            data = {
                _ajax: 1,
                popin: 1,
                type: type,
                sandbox: args['sandbox'],
                embedded: args['embedded'],
                oney: args['oney'],
                one_click: args['one_click'],
                installment: args['installment'],
                deferred: args['deferred'],
                activate: args['activate']
            };
        }
        $.ajax({
            type: 'POST',
            url: url,
            dataType: 'json',
            data: data,
            error: function (jqXHR, textStatus, errorThrown) {
                alert('An error occurred while trying to open the popin. ' +
                    'Maybe you clicked too fast before scripts are fully loaded ' +
                    'or maybe you have a different back-office url than expected.' +
                    'You will find more explanation in JS console.');
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
            },
            success: function (result) {
                $('body').append(result.content);
                if (type == 'pwd') {
                    $('#payplug_popin input[type=password]').focus();
                }
                $('span.ppclose, .ppcancel').bind('click', function () {
                    $('#payplug_popin').remove();
                    $('.ppoverlay').remove();
                    if (type == 'wrong_pwd' || type == 'activate') {
                        $('#payplug_sandbox_left').siblings('.slide-button').css('left', '0%');
                    }
                });
                $('#payplug_popin input[type=submit]').bind('click', function (e) {
                    e.preventDefault();
                    submitPopin(this);
                });
            }
        });
    }
}

function submitPopin(input) {
    $('#payplug_popin p.pperror').hide();
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var submit = input.name;
    var data = {_ajax: 1, submit: submit};
    var pwd = $('#payplug_popin input[name=pwd]').val();
    if (pwd != undefined)
        data = {_ajax: 1, submit: submit, pwd: pwd};

    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: data,
        error: function (jqXHR, textStatus, errorThrown) {
            alert('An error occurred while trying to submit your settings. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function (response) {
            if (response.content == 'wrong_pwd') {
                $('#payplug_popin p.pperror').show();
            } else {
                callPopin(response.content);
            }
        }
    });
}

function callFieldset() {
    var url = $('input:hidden[name=admin_ajax_url]').val();
    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: {
            _ajax: 1,
            check: 1,
        },
        beforeSend: function () {
            $('.checkFieldset .loader').show();
        },
        complete: function () {
            $('.checkFieldset .loader').hide();
        },
        error: function (jqXHR, textStatus, errorThrown) {
            alert('An error occurred while trying to refresh indicators. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function (result) {
            $('.checkFieldset').html(result.content);
            $('input[name=submitCheckConfiguration]').bind('click', function (e) {
                e.preventDefault();
                callFieldset();
            });
        }
    });
}

function checkPremium(go_live, type) {
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var data = {_ajax: 1, checkPremium: 1};
    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: data,
        error: function (jqXHR, textStatus, errorThrown) {
            alert('An error occurred while trying to checking your premium status. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function (result) {
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

function showInstallments(installment_value) {
    $('.ppinstallments').hide();
    $('.pp' + installment_value + 'installments').show();
}

function validateDeferred() {
    var is_auto = $("#payplug_deferred_auto").is(':checked');
    var has_state = parseInt($('#payplug_deferred_state').val()) > 0;

    if (is_auto) {
        if (!has_state) {
            if ($('#deferred_config_error').hasClass('hide')) {
                $('#deferred_config_error').removeClass('hide');
            }
            return false;
        }
    }
    return true;
}

function validate_before_submit() {
    var flag = true;
    if (!validateDeferred()) {
        flag = false;
    }
    if (!flag) {
        //$('#payplug_admin_form form').bind('submit', disableForm());
    }
    return flag;
}

function can_be_live() {
    var url = $('input:hidden[name=admin_ajax_url]').val();
    var data = {_ajax: 1, has_live_key: 1};
    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        data: data,
        error: function (jqXHR, textStatus, errorThrown) {
            alert('An error occurred while trying to checking your verified status. ' +
                'Maybe you clicked too fast before scripts are fully loaded ' +
                'or maybe you have a different back-office url than expected.' +
                'You will find more explanation in JS console.');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function (response) {
            if (response.result) {
                switch_to_live();
            } else {
                callPopin('pwd');
            }
        }
    });
}

function switch_to_live() {
    $('#payplug_sandbox_right').attr('checked', 'checked');
    $('#payplug_sandbox_right').siblings('.slide-button').css('left', '50%');
}
