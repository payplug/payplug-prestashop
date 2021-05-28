/**
 * 2013 - 2021 PayPlug SAS
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
 *  @copyright 2013 - 2021 PayPlug SAS
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
            if ($button.is('.-disabled')) {
                return false;
            }

            var {form} = payplug;

            var error = form.check();
            if (error) {
                return payplug.tools.popup.error(error);
            }

            form.hydrate();

            var data = {
                _ajax: 1,
                popin: 1,
                type: 'confirm',
                sandbox: form.props.data['payplug_sandbox'],
                embedded: form.props.data['payplug_embedded'],
                oney: form.props.data['payplug_oney'],
                standard: form.props.data['payplug_standard'],
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
                        popup.set(result.content, 'submit');
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
                        data[name] = $elem.prop('checked') ? 1 : 0;
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
                        popup.set(result.popin, 'confirm');
                        $('form.payplug').replaceWith(result.content);
                        var {oney} = payplug;
                        $window.trigger('load');
                    }
                }
            });
        },
        check: function () {
            var error = '';

            var {installment, oney, deferred} = payplug;

            if (installment.props.error) {
                error = error_installment + installment.props.error;
            }

            if (oney.props.error) {
                error += (error ? ' <br> ' : '') + error_oney + oney.props.error;
            }

            if (deferred.props.error) {
                error += (error ? ' <br> ' : '') + error_deferred + deferred.props.error;
            }

            return error;
        }
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
            $document.on('switchSelected', '.' + identifier + ' input', show.change)
                .on('click', 'button[name="cancel_deactivate"]', show.cancel)
                .on('click', 'button[name="confirm_deactivate"]', show.deactivate);
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
                    type: 'deactivate'
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
                        popup.set(result.content, 'deactivate');
                    }
                }
            });
        },
        cancel: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var {show, tools} = payplug,
                {switcher} = tools,
                showIdentifier = show.props.identifier,
                switcherIdentifier = switcher.props.identifier,
                $switcher = $('.' + showIdentifier).find('.' + switcherIdentifier);

            switcher.left($switcher, true);
        },
        deactivate: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var {show} = payplug,
                {identifier} = show.props,
                data = {
                    _ajax: 1,
                    submitDisable: 1,
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
                    if (typeof result.content != 'undefined') {
                        var {popup} = payplug.tools;
                        popup.set(result.popin, 'confirm');
                        $('form.payplug').replaceWith(result.content);
                        var {oney} = payplug;
                        $window.trigger('load');
                    }
                }
            });
        },
    },
    login: {
        props: {
            identifier: 'payplugLogin',
            query: null,
            logged: false
        },
        init: function () {
            var {login} = payplug,
                {identifier} = login.props;
            $document.on('click', '.' + identifier + '_login', login.login)
                .on('click', '.' + identifier + '_logout', login.logout)
                .on('click', 'button[name=password]', login.password)
                .on('keyup', 'input[name=PAYPLUG_PASSWORD]', login.submit);
        },
        submit: function (event) {
            var {login} = payplug,
                {identifier} = login.props;

            if (typeof event.keyCode == 'undefined') {
                var {tools} = payplug,
                    email = $('input[name=PAYPLUG_EMAIL]').val(),
                    pwd = $('input[name=PAYPLUG_PASSWORD]').val();

                if (!tools.validate.isEmail(email) || !pwd.length) {
                    return;
                }
            } else if (parseInt(event.keyCode) != 13) {
                return;
            }

            $('.' + identifier + '_login').trigger('click');
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
                        login.props.logged = true;
                        $window.trigger('load');
                    } else if (typeof result.error != 'undefined' && result.error) {
                        payplug.tools.popup.error(result.error);
                        payplug.tools.loader.hide($('.' + identifier));
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
                    login.props.logged = false;
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
                    if (typeof result.error != 'undefined' && result.error) {
                        $('.payplugPopup_error').html(result.error);
                    } else if (typeof result.popin != 'undefined' && result.popin) {
                        var {popup} = payplug.tools;
                        popup.set(result.popin, 'activate');
                    } else if (typeof result.content != 'undefined' && result.content) {
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
                        popup.set(result.content, 'password');
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
                $loader.removeClass('-visible');
                setTimeout(function () {
                    $loader.removeClass('-on');
                }, 100);
            },
            show: function () {
                var obj = this, $loader = $('.' + obj.props.identifer);
                $loader.addClass('-on');
                setTimeout(function () {
                    $loader.addClass('-visible');
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
            var {settings, login} = payplug,
                {identifier} = settings.props;
            $document.on('switchSelected', '.' + identifier + ' input', settings.change);
            $window.on('load', settings.load);
        },
        load: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var {settings, login} = payplug;

            if (!login.props.logged) {
                return false;
            }

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
                    if (errorThrown != 'abort') {
                        alert('An error occurred while trying to checking your verified status. ' +
                            'Maybe you clicked too fast before scripts are fully loaded ' +
                            'or maybe you have a different back-office url than expected.' +
                            'You will find more explanation in JS console.');
                        console.log(jqXHR);
                        console.log(textStatus);
                        console.log(errorThrown);
                    }
                },
                success: function (response) {
                    if (response.result) {
                        settings.reset();
                        var {switcher} = payplug.tools,
                            $switcher = $('input[name=payplug_sandbox]').parents('.' + switcher.props.identifier);
                        switcher.right($switcher, true);
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
                        popup.set(result.content, 'disable');
                    }
                }
            });
        },
    },
    oney: {
        props: {
            identifier: 'payplugOney',
            switcher: 'payplug_oney',
            error: null,
        },
        init: function () {
            var {oney} = payplug,
                {identifier, switcher} = oney.props;

            $('input[name=' + switcher + ']').trigger('switchSelected');
        },
        urlCheck: function () {
            const url = ($(this).val());
            const pattern = new RegExp('^(https?:\\/\\/)?' +
                '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' +
                '((\\d{1,3}\\.){3}\\d{1,3}))' +
                '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' +
                '(\\?[;&a-z\\d%_.~+=-]*)?' +
                '(\\#[-a-z\\d_]*)?$', 'i');
            const matches = url.match(pattern);
            if (matches == null) {
                if (!$('.payplugOneyTOS_error').hasClass('-show')) {
                    $('.payplugOneyTOS_error').addClass('-show');
                }
                $("button[name=submitSettings]").prop("disabled", true);
                $("button[name=submitSettings]").addClass('-disabled');
            }
            if ((matches !== null) || (url.length == 0)) {
                if ($('.payplugOneyTOS_error').hasClass('-show')) {
                    $('.payplugOneyTOS_error').removeClass('-show');
                }
                $("button[name=submitSettings]").prop("disabled", false);
                $("button[name=submitSettings]").removeClass('-disabled');

            }
        },
    },
    standard: {
        props: {
            switcher: 'payplug_standard',
        },
        init: function () {
            var {standard, deferred} = payplug,
                {switcher} = standard.props;
            $document.on('switchSelected', 'input[name=' + switcher + ']', deferred.check);
        }
    },
    installment: {
        props: {
            identifier: 'payplugInstallment',
            switcher: 'payplug_inst',
            query: null,
            error: null,
            limits: {
                min: 4,
                max: 20000,
            }
        },
        init: function () {
            var {installment, deferred} = payplug,
                {switcher} = installment.props;
            $document.on('change', 'input[name=PAYPLUG_INST_MODE]', installment.select)
                .on('keyup', 'input[name=PAYPLUG_INST_MIN_AMOUNT]', installment.check)
                .on('switchSelected', 'input[name=' + switcher + ']', deferred.check);
        },
        select: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var {installment} = payplug,
                {identifier} = installment.props;

            var inst = $('input[name=PAYPLUG_INST_MODE]:checked').val();

            $('.' + identifier + '_schedule').removeClass('-select');
            $('.' + identifier + '_schedule.-x' + inst).addClass('-select');
        },
        check: function (event) {
            var {installment} = payplug,
                {identifier, limits} = installment.props,
                amount = $(this).val(),
                matches = amount.match(/^[0-9]+$/);

            var $error = $('.' + identifier + '_amount').find('span');
            installment.props.error = null;

            if (limits.min > amount || amount > limits.max || matches == null) {
                $error.show();
                installment.props.error = $error.text();
            } else {
                $error.hide();
            }
        },
    },
    deferred: {
        props: {
            identifier: 'payplugDeferred',
            switcher: 'payplug_deferred',
            query: null,
        },
        init: function () {
            var {deferred} = payplug,
                {identifier, switcher} = deferred.props;
            $document.on('change', '.' + identifier + ' input[type=checkbox]', deferred.change)
                .on('switchSelected', 'input[name=' + switcher + ']', deferred.select)
                .on('change', '.' + identifier + ' select', deferred.select);
            $('.' + identifier + ' input[type=checkbox]').trigger('change');
        },
        check: function () {
            var {standard, installment, deferred} = payplug;

            if (!parseInt($('input[name=' + standard.props.switcher + ']:checked').val())
                && !parseInt($('input[name=' + installment.props.switcher + ']:checked').val())
                && parseInt($('input[name=' + deferred.props.switcher + ']:checked').val())) {
                $('input[name=' + deferred.props.switcher + '][value=0]').trigger('click');
            }
        },
        change: function (event) {
            var {deferred} = payplug,
                $checkbox = $(this),
                checked = $checkbox.prop('checked');
            if (checked) {
                deferred.active();
            } else {
                deferred.deactive();
            }
            $('.' + deferred.props.identifier).find('select').trigger('change');
        },
        active: function () {
            var {deferred} = payplug,
                {identifier} = deferred.props;
            $('.' + identifier).find('select').attr('disabled', false);
        },
        deactive: function () {
            var {deferred} = payplug,
                {identifier} = deferred.props;
            $('.' + identifier).find('select').attr('disabled', true);
        },
        select: function () {
            var {standard, installment, deferred} = payplug,
                {identifier, switcher} = deferred.props,
                $checkbox = $('.' + identifier).find('input[type=checkbox]'),
                $select = $('.' + identifier).find('select'),
                checked = $checkbox.prop('checked'),
                active = parseInt($('input[name=' + switcher + ']:checked').val());

            if (!parseInt($('input[name=' + standard.props.switcher + ']:checked').val())
                && !parseInt($('input[name=' + installment.props.switcher + ']:checked').val())) {
                return deferred.unavailable();
            }

            var $error = $('.' + identifier).find('span');
            deferred.props.error = null;

            if (checked && !parseInt($select.val()) && active) {
                $error.show();
                deferred.props.error = $error.text();
            } else {
                $error.hide();
            }
        },
        unavailable: function(){
            var {deferred, tools} = payplug,
                {identifier} = deferred.props,
                {switcher} = tools,
                $switch = $('input[name=' + identifier + ']').parents('.' + switcher.props.identifier);

            switcher.right($switch);

            var data = {
                _ajax: 1,
                popin: 1,
                type: 'deferred'
            };

            if (deferred.props.query != null) {
                deferred.props.query.abort();
                deferred.props.query = null;
            }

            deferred.props.query = $.ajax({
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
                        var {popup} = tools;
                        popup.set(result.content, 'password');
                    }
                }
            });
        }
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
                $loader.removeClass('-visible');
                setTimeout(function () {
                    $loader.removeClass('-on');
                }, 100);
            },
            show: function (context) {
                var obj = this,
                    $loader = context.find('.' + obj.props.identifer);
                $loader.addClass('-on');
                setTimeout(function () {
                    $loader.addClass('-visible');
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
                    $switch = $(this),
                    is_right = $switch.is('.-right');

                if ($switch.is('.-disabled') || $switch.parents('.-hide').length) {
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
                    is_right = $label.is('.-right'),
                    $switch = $label.parents('.' + identifier),
                    $tips = null;

                if ($switch.is('.-disabled') || $switch.parents('.-hide').length) {
                    return;
                }

                if (is_right) {
                    if (!$switch.is('.-right')) {
                        switcher.right($switch);
                    }
                } else {
                    if ($switch.is('.-right')) {
                        switcher.left($switch);
                        if ($tips) {
                            $tips.find('.payplugTips_item.-left').show();
                        }
                    }
                }
            },
            right: function (target, withoutEvent) {
                var {switcher} = payplug.tools,
                    {identifier} = switcher.props;
                target.addClass('-right');
                target.find('input').removeAttr('checked').prop('checked', false);
                var name = target.find('input').eq(0).attr('name'),
                    $tips = $('.payplugTips.-' + name);

                if ($tips.length) {
                    $('.payplugTips.-' + name + ' > .payplugTips_item').addClass('-hide');
                    $('.payplugTips.-' + name + ' > .-right').removeClass('-hide');
                }

                var $selected = target.find('input[value=0]');
                $selected.attr('checked', 'checked').prop('checked', true);
                if (typeof withoutEvent == 'undefined' || !withoutEvent) {
                    $selected.trigger('switchSelected');
                }
            },
            left: function (target, withoutEvent) {
                var {switcher} = payplug.tools,
                    {identifier} = switcher.props;
                target.removeClass('-right');
                target.find('input').removeAttr('checked').prop('checked', false);

                var name = target.find('input').eq(0).attr('name'),
                    $tips = $('.payplugTips.-' + name);

                if ($tips.length) {
                    $('.payplugTips.-' + name + ' > .payplugTips_item').addClass('-hide');
                    $('.payplugTips.-' + name + ' > .-left').removeClass('-hide');
                }

                var $selected = target.find('input[value=1]');
                $selected.attr('checked', 'checked').prop('checked', true);
                if (typeof withoutEvent == 'undefined' || !withoutEvent) {
                    $selected.trigger('switchSelected');
                }
            },
            able: function (target) {
                var {switcher} = payplug.tools,
                    {identifier} = switcher.props;
                target.removeClass('-disabled');
            },
            disable: function (target) {
                var {switcher} = payplug.tools,
                    {identifier} = switcher.props;
                target.addClass('-disabled');
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
                    .on('click', '.' + identifier + ' .payplugButton.-close', popup.close)
                    .on('click', function (event) {
                        var $clicked = $(event.target);
                        if ($clicked.is('.' + identifier) && $('.' + identifier).is('.-open')) {
                            $('.' + identifier + '_close').trigger('click');
                        }
                    });
            },
            set: function (content, id) {
                var {popup} = payplug.tools,
                    {identifier} = popup.props;

                if ($('.' + identifier).length) {
                    popup.remove();
                }
                popup.create(id);
                popup.hydrate(content);
                popup.open();
            },
            open: function () {
                var {popup} = payplug.tools,
                    {identifier} = popup.props,
                    $popup = $('.' + identifier);

                $popup.addClass('-open');
                window.setTimeout(function () {
                    $popup.addClass('-show');
                }, 0);
            },
            close: function (event) {
                var {popup} = payplug.tools,
                    {identifier} = popup.props,
                    $popup = $('.' + identifier);

                if ($(event.target).is('.' + identifier + '_close') && $popup.find('.payplugButton.-close')) {
                    $popup.find('.payplugButton.-close').trigger('click');
                }

                $popup.removeClass('-show');
                window.setTimeout(function () {
                    $popup.removeClass('-open');
                    popup.remove();
                }, 500);
            },
            create: function (id) {
                var {popup} = payplug.tools,
                    {identifier} = popup.props,
                    html = '<div class="' + identifier + '"' + (id ? ' data-e2e-popin="' + id + '"' : '') + '><button class="' + identifier + '_close"></button><div class="' + identifier + '_content"></div></div>';
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
            },
            error: function (str) {
                var {popup} = payplug.tools;
                var $error = '<div class="payplugPopup_row">' +
                    '<p>' + str + '</p>' +
                    '<div class="payplugPopup_footer -center">' +
                    '<button type="button" class="payplugButton -green -close">Ok</button>' +
                    '</div>' +
                    '</div>';
                popup.set($error, 'error');
            }
        },
        validate: {
            isEmail: function (email) {
                if (typeof email == 'undefined' || !email) {
                    return false;
                }

                var regex = /^[a-z\p{L}0-9!#$%&'*+\/=?^`{}|~_-]+[.a-z\p{L}0-9!#$%&'*+\/=?^`{}|~_-]*@[a-z\p{L}0-9]+[._a-z\p{L}0-9-]*\.[a-z\p{L}0-9]+$/i;
                return regex.test(email);
            }
        }
    },
};

$(document).ready(function () {
    payplug.init();
});
