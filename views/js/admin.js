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

            form.hydrate();

            var error = form.check();
            if (error) {
                return payplug.tools.popup.error(error);
            }

            var data = {
                _ajax: 1,
                popin: 1,
                type: 'confirm',
                activate: 0,
                sandbox: form.props.data['payplug_sandbox'],
                embedded: form.props.data['payplug_embedded'],
                standard: form.props.data['payplug_standard'],
                one_click: form.props.data['payplug_one_click'],
                oney: form.props.data['payplug_oney'],
                installment: form.props.data['payplug_inst'],
                bancontact: form.props.data['payplug_bancontact'],
                deferred: form.props.data['payplug_deferred']
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
                        console.log(admin_ajax_url);
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
                    value = parseInt($elem.val());

                switch (type) {
                    case 'radio' :
                        if ($elem.prop('checked')) {
                            if (isNaN(value)) {
                                data[name] = $elem.val();
                            } else {
                                data[name] = value;
                            }
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
                data[$elem.attr('name')] = parseInt($elem.val());
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
                        var {oney, deferred,settings} = payplug;
                        settings.load();
                        $window.trigger('load');
                        if (deferred.props.stateChanged != null && deferred.props.active == true && $('input[name=' + deferred.props.switcher + ']:checked').val() == 1) {
                            $('html,body').stop(true).animate({'scrollTop': 0});
                        }
                    }
                }
            });
        },
        check: function () {

            var error = '';

            var {installment, oney, deferred, form} = payplug;
            if (installment.props.error && form.props.data['payplug_inst'] === 1) {
                error = error_installment + installment.props.error;
            } else {
                installment.props.error = null;
            }

            if (oney.props.error && form.props.data['payplug_oney'] === 1) {
                error += (error ? ' <br> ' : '') + error_oney + oney.props.error;
            } else {
                oney.props.error = null;
            }

            if (deferred.props.error && form.props.data['payplug_deferred'] === 1) {
                error += (error ? ' <br> ' : '') + error_deferred + deferred.props.error;
            } else {
                deferred.props.error = null;
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
            // check if user is logged
            login.props.logged = $('.' + identifier).is('.-logged');
            $document.on('click', '.' + identifier + '_login', login.login)
                .on('click', '.' + identifier + '_logout', login.logout)
                .on('click', 'button[name=password]', login.password)
                .on('keyup', 'input[name=PAYPLUG_PASSWORD]', login.submit);
        },
        submit: function (event) {
            var {login} = payplug,
                {identifier} = login.props;

            // Only validate the login form if key "Enter" press
            if (parseInt(event.keyCode) != 13) {
                return;
            }

            var {tools} = payplug,
                email = $('input[name=PAYPLUG_EMAIL]').val(),
                pwd = $('input[name=PAYPLUG_PASSWORD]').val();

            if (!tools.validate.isEmail(email) || !pwd.length) {
                return;
            }

            $('.' + identifier + '_login').trigger('click');
        },
        login: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var {login, settings} = payplug,
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
                        settings.load();
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
            // call load in order to get permission's information when reloading the page
            this.load();
            $document.on('switchSelected', '.' + identifier + ' input', settings.change);
        },
        load: function () {

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

            // Toggle bancontact only for live configuration
            var {bancontact} = payplug,
                {identifier} = bancontact.props,
                $bancontact = $('.' + identifier);

            if (name == 'payplug_sandbox' && !is_sandbox) {
                switcher.left($switcher, true);
                return settings.live();
            } else if (name == 'payplug_sandbox' && is_sandbox) {
                $bancontact.addClass('-hide');
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
                    console.log('in');
                    if (response.result) {
                        console.log('in');
                        settings.reset();
                        var {tools, bancontact} = payplug,
                            $bancontact = $('.' + bancontact.props.identifier),
                            {switcher} = tools,
                            $switcher = $('input[name=payplug_sandbox]').parents('.' + switcher.props.identifier);
                        switcher.right($switcher, true);
                        $bancontact.removeClass('-hide');
                    } else {
                        var {login} = payplug;
                        login.reload();
                    }
                }
            });
        },
        disable: function ($switcher) {
            var {switcher} = payplug.tools,
                {settings} = payplug,
                switcherName = $switcher.prevObject[0].name;

            switch (switcherName) {
                case 'payplug_oney' :
                    var checkpremium = 'oneyPremium';
                    break;
                case 'payplug_bancontact' :
                    var checkpremium = 'bancontactPremium';
                    break;
                default :
                    var checkpremium = 'premium';
                    break;
            }

            var data = {
                _ajax: 1,
                popin: 1,
                type: checkpremium
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
            limits: {
                min: oney_min_amounts,
                max: oney_max_amounts,
            },

        },
        init: function () {
            var {oney} = payplug,
                {identifier} = oney.props;
            $document.on('change', '.' + identifier + 'Fees input', oney.selectFees)
                .on('focusout', 'input[name="PAYPLUG_ONEY_CUSTOM_MIN_AMOUNTS"]', oney.checkMin)
                .on('focusout', 'input[name="PAYPLUG_ONEY_CUSTOM_MAX_AMOUNTS"]', oney.checkMax);
        },
        checkMin: function (event) {
            var {oney} = payplug,
                {identifier, limits} = oney.props,
                amount = $(this).val(),
                matches = amount.match(/^[0-9]+$/);
            var $val_max = document.getElementById("oney_max").value;
            var $error = $('.' + identifier + '_statement').find('span');
            oney.props.error = null;
            if (limits.min > amount  || matches == null) {
                $('#oney_min').addClass('error');
                $error.text(errorOneyThresholds);
                $error.show();
                oney.props.error = $error.text();
            } else if ( parseFloat($val_max) < amount) {
                $('#oney_min').addClass('error');
                $error.text(errorOneyMin);
                $error.show();
                oney.props.error = $error.text();
            } else {
                $('#oney_min').removeClass('error');
                $error.hide();
            }

        },
        checkMax: function (event) {
            var {oney} = payplug,
                {identifier, limits} = oney.props,
                amount = $(this).val(),
                matches = amount.match(/^[0-9]+$/);
            var $val_min = document.getElementById("oney_min").value;
            var $error = $('.' + identifier + '_statement').find('span');
            oney.props.error = null;

            if (limits.max < amount || matches == null) {
                $('#oney_max').addClass('error');
                $error.text(errorOneyThresholds)
                $error.show();
                oney.props.error = $error.text();
            } else if (parseFloat($val_min)  > amount) {
                $('#oney_max').addClass('error');
                $error.text(errorOneyMax);
                $error.show();
                oney.props.error = $error.text();
            } else {
                $('#oney_max').removeClass('error');
                $error.hide();
            }
        },
            selectFees: function(event) {
            var {oney} = payplug,
                {identifier} = oney.props,
                $options = $('.' + identifier + 'Fees_option'),
                $selected = $(this).parents('.' + identifier + 'Fees_option');
            $options.removeClass('-selected');
            $selected.addClass('-selected');
        }
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
            originalText: null,
            currentStateName: null,
            currentStateValue: null,
            stateChanged: null,
            loaded: null,
            active: false,
        },
        init: function () {
            var {deferred} = payplug,
                {identifier, switcher} = deferred.props,
                $selected = $('.' + identifier + ' select option:selected');
            $document.on('change', '.' + identifier + ' input[type=checkbox]', deferred.change)
                .on('switchSelected', 'input[name=' + switcher + ']', deferred.select)
                .on('change', '.' + identifier + ' select', deferred.select);
            $('.' + identifier + ' input[type=checkbox]').trigger('change');
            deferred.props.originalText = $('.' + identifier + '_warning').text();
            deferred.props.currentStateName = $selected.text();
            deferred.props.currentStateVal = $selected.val();
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
            deferred.props.active = true;
        },
        deactive: function () {
            var {deferred} = payplug,
                {identifier} = deferred.props;
            $('.' + identifier).find('select').attr('disabled', true);
            $('.' + identifier).find('.payplugDeferred_warning').hide();
            deferred.props.active = false;
        },
        select: function () {
            var {standard, installment, deferred} = payplug,
                {identifier, switcher} = deferred.props,
                $checkbox = $('.' + identifier).find('input[type=checkbox]'),
                $select = $('.' + identifier).find('select'),
                checked = $checkbox.prop('checked'),
                active = parseInt($('input[name=' + switcher + ']:checked').val()),
                $selectedItem = $('select[name=' + switcher + '_state] option:selected'),
                $warning = $('.' + identifier + '_warning'),
                recordedStateId = $select.data('id_state');

            if ($selectedItem.val() == 0 || $selectedItem.val() == recordedStateId) {
                $warning.hide();
                deferred.props.stateChanged = null;
            } else if ($selectedItem.val() != recordedStateId) {
                var textToReplace = ( deferred.props.currentStateValue == null || !$warning.text().includes(deferred.props.currentStateName) )? '%state%' : deferred.props.currentStateName;
                $warning.html($warning.text().replace(textToReplace, $selectedItem.text()));

                $warning.show();

                deferred.props.currentStateName = $selectedItem.text();
                deferred.props.currentStateValue = $selectedItem.val();
                deferred.props.stateChanged = 1;
            }

            if (deferred.props.loaded
                && !parseInt($('input[name=' + standard.props.switcher + ']:checked').val())
                && !parseInt($('input[name=' + installment.props.switcher + ']:checked').val())) {
                return deferred.unavailable();
            } else {
                deferred.props.loaded = true;
            }

            var $error = $('.' + identifier).find('span.' + identifier + '_error');
            deferred.props.error = null;

            if (checked && !parseInt($select.val()) && active) {
                $error.show();
                deferred.props.error = $error.text();
            } else {
                $error.hide();
            }
        },
        unavailable: function () {
            var {deferred, tools} = payplug,
                {switcher} = tools,
                data = {
                    _ajax: 1,
                    popin: 1,
                    type: 'deferred'
                },
                $switch = $('input[name=' + deferred.props.switcher + ']').parents('.' + switcher.props.identifier);

            if(!deferred.props.loaded) {
                return;
            }

            switcher.right($switch, true);

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
    bancontact: {
        props: {
            identifier: 'payplugBancontact',
            switcher: 'payplug_bancontact',
        },
        init: function() {}
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
                    is_right = $switch.is('.-right'),
                    is_format = $switch.is('.-format');

                if ($switch.is('.-disabled') || $switch.parents('.-hide').length) {
                    return;
                }
                if (!is_format) {
                    if (is_right) {
                        switcher.left($switch);
                    } else {
                        switcher.right($switch);
                    }
                }

            },
            select: function (event) {
                event.preventDefault();
                event.stopPropagation();
                var {switcher} = payplug.tools,
                    {identifier} = switcher.props,
                    $label = $(this),
                    id = $label.attr('for').replace('_left', '').replace('_right', '').replace('_center', ''),
                    is_right = $label.is('.-right'),
                    is_left = $label.is('.-left'),
                    is_center = $label.is('.-center'),
                    $switch = $label.parents('.' + identifier),
                    $tips = null;

                if ($switch.is('.-disabled') || $switch.parents('.-hide').length) {
                    return;
                }
                if (is_right) {
                    if (!$switch.is('.-right')) {
                        switcher.right($switch);
                    }
                } else if (is_left) {
                    if (!$switch.is('.-left')) {
                        switcher.left($switch);
                        if ($tips) {
                            $tips.find('.payplugTips_item.-left').show();
                        }
                    }
                } else if (is_center) {
                    if (!$switch.is('.-center')) {
                        switcher.center($switch);
                        if ($tips) {
                            $tips.find('.payplugTips_item.-center').show();
                        }
                    }
                }

            },
            right: function (target, withoutEvent) {
                var {switcher} = payplug.tools,
                    {identifier} = switcher.props;
                target.addClass('-right');
                target.removeClass('-left');
                target.removeClass('-center');
                target.find('input').removeAttr('checked').prop('checked', false);
                var name = target.find('input').eq(0).attr('name'),
                    $tips = $('.payplugTips.-' + name);

                if ($tips.length) {
                    $('.payplugTips.-' + name + ' > .payplugTips_item').addClass('-hide');
                    $('.payplugTips.-' + name + ' > .-right').removeClass('-hide');
                }

                var $selected = target.find('input').last();
                $selected.attr('checked', 'checked').prop('checked', true);
                if (typeof withoutEvent == 'undefined' || !withoutEvent) {
                    $selected.trigger('switchSelected');
                }
            },
            left: function (target, withoutEvent) {
                var {switcher} = payplug.tools,
                    {identifier} = switcher.props;
                target.removeClass('-right');
                target.removeClass('-center');
                target.addClass('-left');
                target.find('input').removeAttr('checked').prop('checked', false);

                var name = target.find('input').eq(0).attr('name'),
                    $tips = $('.payplugTips.-' + name);

                if ($tips.length) {
                    $('.payplugTips.-' + name + ' > .payplugTips_item').addClass('-hide');
                    $('.payplugTips.-' + name + ' > .-left').removeClass('-hide');
                }

                var $selected = target.find('input').first();
                $selected.attr('checked', 'checked').prop('checked', true);
                if (typeof withoutEvent == 'undefined' || !withoutEvent) {
                    $selected.trigger('switchSelected');
                }
            },
            center: function (target, withoutEvent) {
                var {switcher} = payplug.tools,
                    {identifier} = switcher.props;
                target.removeClass('-right');
                target.removeClass('-left');
                target.addClass('-center');
                target.find('input').removeAttr('checked').prop('checked', false);

                var name = target.find('input').eq(0).attr('name'),
                    $tips = $('.payplugTips.-' + name);

                if ($tips.length) {
                    $('.payplugTips.-' + name + ' > .payplugTips_item').addClass('-hide');
                    $('.payplugTips.-' + name + ' > .-center').removeClass('-hide');
                }

                var $selected = target.find('input').eq(1);
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

                if (!sanitizePopupHtml(content)) {
                    return;
                }
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
