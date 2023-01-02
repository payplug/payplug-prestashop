/**
 * 2013 - 2023 PayPlug SAS
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
 *  @copyright 2013 - 2023 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */
var $document, $window, __moduleName__Module = {
    init: function () {
        $document = $(document);
        $window = $(window);
        for (const section in this) {
            if (section != 'init') {
                this[section]['init']();
            }
        }
    },
    form: {
        props: {
            identifier: '__moduleName__',
            query: null,
            data: {},
        },
        init: function () {
            var {form} = __moduleName__Module;
            var {identifier} = form.props;

            $document.on('click', 'form.' + identifier + ' button[type="submit"]', form.submit)
                .on('click', '.__moduleName__Button[name="confirmConfiguration"]', form.save);
        },
        submit: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var $button = $(this);
            if ($button.is('.-disabled')) {
                return false;
            }

            var {form} = __moduleName__Module;

            form.hydrate();

            var error = form.check();
            if (error) {
                return __moduleName__Module.tools.popup.error(error);
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
                        console.log(jqXHR, textStatus, errorThrown);
                    }
                },
                success: function (result) {
                    if (typeof result.content != 'undefined') {
                        var {popup} = __moduleName__Module.tools;
                        popup.set(result.content, 'submit');
                    }
                }
            });
        },
        hydrate: function () {
            var {form} = __moduleName__Module;
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

            var {form} = __moduleName__Module;
            var {data, identifier} = form.props;

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
                        console.log(jqXHR, textStatus, errorThrown);
                    }
                },
                success: function (result) {
                    if (typeof result.content != 'undefined') {
                        var {popup} = __moduleName__Module.tools;
                        popup.set(result.popin, 'confirm');
                        $('form.' + identifier).replaceWith(result.content);
                        var {oney, deferred,settings} = __moduleName__Module;
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

            var {installment, oney, deferred, form} = __moduleName__Module;
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
            identifier: '__moduleName__Config',
            query: null
        },
        init: function () {
            var {config} = __moduleName__Module,
                {identifier} = config.props;
            $document.on('click', '.' + identifier + '_check', config.check);
        },
        check: function (event) {
            event.preventDefault();
            var {config}= __moduleName__Module;
            config.refresh();
        },
        refresh: function () {
            var {config} = __moduleName__Module,
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
                    __moduleName__Module.tools.loader.show($('.' + identifier));
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    if (textStatus != 'abort') {
                        alert('An error occurred while trying to refresh indicators. ' +
                            'Maybe you clicked too fast before scripts are fully loaded ' +
                            'or maybe you have a different back-office url than expected.' +
                            'You will find more explanation in JS console.');
                        console.log(jqXHR, textStatus, errorThrown);
                        __moduleName__Module.tools.loader.hide($('.' + identifier));
                    }
                },
                success: function (result) {
                    $('.' + identifier).replaceWith(result.content);
                    __moduleName__Module.tools.loader.hide($('.' + identifier));
                }
            });
        },
    },
    show: {
        props: {
            identifier: '__moduleName__Show',
            query: null,
        },
        init: function () {
            var {show} = __moduleName__Module,
                {identifier} = show.props;
            $document.on('switchSelected', '.' + identifier + ' input', show.change)
                .on('click', 'button[name="cancel_deactivate"]', show.cancel)
                .on('click', 'button[name="confirm_deactivate"]', show.deactivate);
        },
        change: function (event) {
            var {show} = __moduleName__Module,
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
            var {form} = __moduleName__Module,
                {identifier} = form.props,
                $submit = $('form.' + identifier).find('button[type="submit"]');

            $submit.trigger('click');
        },
        disable: function () {
            var {show} = __moduleName__Module,
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
                        console.log(jqXHR, textStatus, errorThrown);
                    }
                },
                success: function (result) {
                    if (typeof result.content != 'undefined') {
                        var {popup} = __moduleName__Module.tools;
                        popup.set(result.content, 'deactivate');
                    }
                }
            });
        },
        cancel: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var {show, tools} = __moduleName__Module,
                {switcher} = tools,
                showIdentifier = show.props.identifier,
                switcherIdentifier = switcher.props.identifier,
                $switcher = $('.' + showIdentifier).find('.' + switcherIdentifier);

            switcher.left($switcher, true);
        },
        deactivate: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var {show} = __moduleName__Module,
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
                    __moduleName__Module.tools.loader.show($('.' + identifier));
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    if (textStatus != 'abort') {
                        alert('An error occurred while trying to refresh indicators. ' +
                            'Maybe you clicked too fast before scripts are fully loaded ' +
                            'or maybe you have a different back-office url than expected.' +
                            'You will find more explanation in JS console.');
                        console.log(jqXHR, textStatus, errorThrown);
                        __moduleName__Module.tools.loader.hide($('.' + identifier));
                    }
                },
                success: function (result) {
                    if (typeof result.content != 'undefined') {
                        var {popup} = __moduleName__Module.tools;
                        popup.set(result.popin, 'confirm');
                        $('form.__moduleName__').replaceWith(result.content);
                        $window.trigger('load');
                    }
                }
            });
        },
    },
    login: {
        props: {
            identifier: '__moduleName__Login',
            query: null,
            logged: false
        },
        init: function () {
            var {login} = __moduleName__Module,
                {identifier} = login.props;
            // check if user is logged
            login.props.logged = $('.' + identifier).is('.-logged');
            $document.on('click', '.' + identifier + '_login', login.login)
                .on('click', '.' + identifier + '_logout', login.logout)
                .on('click', 'button[name=password]', login.password)
                .on('keyup', 'input[name=payplug_password]', login.submit);
        },
        submit: function (event) {
            var {login} = __moduleName__Module,
                {identifier} = login.props;

            // Only validate the login form if key "Enter" press
            if (parseInt(event.keyCode) != 13) {
                return;
            }

            var {tools} = __moduleName__Module,
                email = $('input[name="payplug_email"]').val(),
                pwd = $('input[name="payplug_password"]').val();

            if (!tools.validate.isEmail(email) || !pwd.length) {
                return;
            }

            $('.' + identifier + '_login').trigger('click');
        },
        login: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var {login, settings} = __moduleName__Module,
                {identifier} = login.props,
                data = {
                    _ajax: 1,
                    log: 1,
                    submitAccount: 1,
                    payplug_email: $('input[name="payplug_email"]').val(),
                    payplug_password: $('input[name="payplug_password"]').val(),
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
                    __moduleName__Module.tools.loader.show($('.' + identifier));
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    if (textStatus != 'abort') {
                        alert('An error occurred while trying to refresh indicators. ' +
                            'Maybe you clicked too fast before scripts are fully loaded ' +
                            'or maybe you have a different back-office url than expected.' +
                            'You will find more explanation in JS console.');
                        console.log(jqXHR, textStatus, errorThrown);
                        __moduleName__Module.tools.loader.hide($('.' + identifier));
                    }
                },
                success: function (result) {
                    if (typeof result.content != 'undefined' && result.content) {
                        $('form.__moduleName__').replaceWith(result.content);
                        login.props.logged = true;
                        settings.load();
                        $window.trigger('load');
                    } else if (typeof result.error != 'undefined' && result.error) {
                        __moduleName__Module.tools.popup.error(result.error);
                        __moduleName__Module.tools.loader.hide($('.' + identifier));
                    }
                }
            });
        },
        logout: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var {login} = __moduleName__Module,
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
                    __moduleName__Module.tools.loader.show($('.' + identifier));
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    if (textStatus != 'abort') {
                        alert('An error occurred while trying to refresh indicators. ' +
                            'Maybe you clicked too fast before scripts are fully loaded ' +
                            'or maybe you have a different back-office url than expected.' +
                            'You will find more explanation in JS console.');
                        console.log(jqXHR, textStatus, errorThrown);
                        __moduleName__Module.tools.loader.hide($('.' + identifier));
                    }
                },
                success: function (result) {
                    $('form.__moduleName__').replaceWith(result.content);
                    login.props.logged = false;
                }
            });
        },
        password: function (event) {
            event.preventDefault();
            event.stopPropagation();

            $('.__moduleName__Popup_error').html('');

            var {login}= __moduleName__Module;
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
                    console.log(jqXHR, textStatus, errorThrown);
                },
                success: function (result) {
                    if (typeof result.error != 'undefined' && result.error) {
                        $('.__moduleName__Popup_error').html(result.error);
                    } else if (typeof result.popin != 'undefined' && result.popin) {
                        var {popup} = __moduleName__Module.tools;
                        popup.set(result.popin, 'activate');
                    } else if (typeof result.content != 'undefined' && result.content) {
                        var {popup} = __moduleName__Module.tools;
                        popup.close();
                    }
                }
            });
        },
        reload: function () {
            var {login}= __moduleName__Module;

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
                    console.log(jqXHR, textStatus, errorThrown);
                },
                success: function (result) {
                    if (typeof result.content != 'undefined') {
                        var {popup} = __moduleName__Module.tools;
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
            identifier: '__moduleName__Settings',
            query: null,
        },
        init: function () {
            var {settings, login} = __moduleName__Module,
                {identifier} = settings.props;
            // call load in order to get permission's information when reloading the page
            this.load();
            $document.on('switchSelected', '.' + identifier + ' input', settings.change);
        },
        load: function () {

            var {settings, login}= __moduleName__Module;

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
                    console.log(jqXHR, textStatus, errorThrown);
                },
                success: function (result) {
                    for (permission in result) {
                        var $input = $('input[name=' + permission + ']');
                        if ($input.length) {
                            var {switcher} = __moduleName__Module.tools,
                                $switcher = $input.parents('.' + switcher.props.identifier),
                                is_allowed = result[permission];
                            $switcher.attr('data-allowed', (is_allowed ? 1 : 0));
                        }
                    }
                }
            });
        },
        change: function () {
            var {switcher} = __moduleName__Module.tools,
                {settings} = __moduleName__Module,
                {identifier} = switcher.props;

            var $input = $(this),
                $switcher = $input.parents('.' + identifier),
                value = parseInt($input.val()),
                name = $input.attr('name'),
                allowed = $switcher.attr('data-allowed');

            var is_sandbox = parseInt($('input[name=payplug_sandbox]:checked').val());

            // Toggle bancontact only for live configuration
            var {bancontact} = __moduleName__Module,
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
            var {switcher} = __moduleName__Module.tools,
                {settings} = __moduleName__Module,
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
            var {settings}= __moduleName__Module;

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
                        console.log(jqXHR, textStatus, errorThrown);
                    }
                },
                success: function (response) {
                    if (response.result) {
                        settings.reset();
                        var {tools, bancontact} = __moduleName__Module,
                            $bancontact = $('.' + bancontact.props.identifier),
                            {switcher} = tools,
                            $switcher = $('input[name=payplug_sandbox]').parents('.' + switcher.props.identifier);
                        switcher.right($switcher, true);
                        $bancontact.removeClass('-hide');
                    } else {
                        var {login}= __moduleName__Module;
                        login.reload();
                    }
                }
            });
        },
        disable: function ($switcher) {
            var {switcher} = __moduleName__Module.tools,
                {settings} = __moduleName__Module,
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
                    console.log(jqXHR, textStatus, errorThrown);
                },
                success: function (result) {
                    if (typeof result.content != 'undefined') {
                        var {popup} = __moduleName__Module.tools;
                        popup.set(result.content, 'disable');
                    }
                }
            });
        },
    },
    standard: {
        props: {
            switcher: 'payplug_standard',
        },
        init: function () {
            var {standard, deferred} = __moduleName__Module,
                {switcher} = standard.props;
            $document.on('switchSelected', 'input[name=' + switcher + ']', deferred.check);
        }
    },
    installment: {
        props: {
            identifier: '__moduleName__Installment',
            switcher: 'payplug_inst',
            query: null,
            error: null,
            limits: {
                min: 4,
                max: 20000,
            }
        },
        init: function () {
            var {installment, deferred} = __moduleName__Module,
                {switcher} = installment.props;
            $document.on('change', 'input[name=payplug_inst_mode]', installment.select)
                .on('keyup', 'input[name=payplug_inst_min_amount]', installment.check)
                .on('switchSelected', 'input[name=' + switcher + ']', deferred.check);
        },
        select: function (event) {
            event.preventDefault();
            event.stopPropagation();

            var {installment} = __moduleName__Module,
                {identifier} = installment.props;

            var inst = $('input[name=payplug_inst_mode]:checked').val();

            $('.' + identifier + '_schedule').removeClass('-select');
            $('.' + identifier + '_schedule.-x' + inst).addClass('-select');
        },
        check: function (event) {
            var {installment} = __moduleName__Module,
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
            identifier: '__moduleName__Deferred',
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
            var {deferred} = __moduleName__Module,
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
            var {standard, installment, deferred}= __moduleName__Module;

            if (!parseInt($('input[name=' + standard.props.switcher + ']:checked').val())
                && !parseInt($('input[name=' + installment.props.switcher + ']:checked').val())
                && parseInt($('input[name=' + deferred.props.switcher + ']:checked').val())) {
                $('input[name=' + deferred.props.switcher + '][value=0]').trigger('click');
            }
        },
        change: function (event) {
            var {deferred} = __moduleName__Module,
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
            var {deferred} = __moduleName__Module,
                {identifier} = deferred.props;
            $('.' + identifier).find('select').attr('disabled', false);
            deferred.props.active = true;
        },
        deactive: function () {
            var {deferred} = __moduleName__Module,
                {identifier} = deferred.props;
            $('.' + identifier).find('select').attr('disabled', true);
            $('.' + identifier).find('.__moduleName__Deferred_warning').hide();
            deferred.props.active = false;
        },
        select: function () {
            var {standard, installment, deferred} = __moduleName__Module,
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
                && !parseInt($('input[name=payplug_standard]:checked').length)
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
            var {deferred, tools} = __moduleName__Module,
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
                    console.log(jqXHR, textStatus, errorThrown);
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
            identifier: '__moduleName__Bancontact',
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
                identifer: '__moduleName__Loader',
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
                identifier: '__moduleName__Switch'
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
                var {switcher} = __moduleName__Module.tools,
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
                var {switcher} = __moduleName__Module.tools,
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
                            $tips.find('.__moduleName__Tips_item.-left').show();
                        }
                    }
                } else if (is_center) {
                    if (!$switch.is('.-center')) {
                        switcher.center($switch);
                        if ($tips) {
                            $tips.find('.__moduleName__Tips_item.-center').show();
                        }
                    }
                }

            },
            right: function (target, withoutEvent) {
                var {switcher} = __moduleName__Module.tools,
                    {identifier} = switcher.props;
                target.addClass('-right');
                target.removeClass('-left');
                target.removeClass('-center');
                target.find('input').removeAttr('checked').prop('checked', false);
                var name = target.find('input').eq(0).attr('name'),
                    $tips = $('.__moduleName__Tips.-' + name);

                if ($tips.length) {
                    $('.__moduleName__Tips.-' + name + ' > .__moduleName__Tips_item').addClass('-hide');
                    $('.__moduleName__Tips.-' + name + ' > .-right').removeClass('-hide');
                }

                var $selected = target.find('input').last();
                $selected.attr('checked', 'checked').prop('checked', true);
                if (typeof withoutEvent == 'undefined' || !withoutEvent) {
                    $selected.trigger('switchSelected');
                }
            },
            left: function (target, withoutEvent) {
                var {switcher} = __moduleName__Module.tools,
                    {identifier} = switcher.props;
                target.removeClass('-right');
                target.removeClass('-center');
                target.addClass('-left');
                target.find('input').removeAttr('checked').prop('checked', false);

                var name = target.find('input').eq(0).attr('name'),
                    $tips = $('.__moduleName__Tips.-' + name);

                if ($tips.length) {
                    $('.__moduleName__Tips.-' + name + ' > .__moduleName__Tips_item').addClass('-hide');
                    $('.__moduleName__Tips.-' + name + ' > .-left').removeClass('-hide');
                }

                var $selected = target.find('input').first();
                $selected.attr('checked', 'checked').prop('checked', true);
                if (typeof withoutEvent == 'undefined' || !withoutEvent) {
                    $selected.trigger('switchSelected');
                }
            },
            center: function (target, withoutEvent) {
                var {switcher} = __moduleName__Module.tools,
                    {identifier} = switcher.props;
                target.removeClass('-right');
                target.removeClass('-left');
                target.addClass('-center');
                target.find('input').removeAttr('checked').prop('checked', false);

                var name = target.find('input').eq(0).attr('name'),
                    $tips = $('.__moduleName__Tips.-' + name);

                if ($tips.length) {
                    $('.__moduleName__Tips.-' + name + ' > .__moduleName__Tips_item').addClass('-hide');
                    $('.__moduleName__Tips.-' + name + ' > .-center').removeClass('-hide');
                }

                var $selected = target.find('input').eq(1);
                $selected.attr('checked', 'checked').prop('checked', true);
                if (typeof withoutEvent == 'undefined' || !withoutEvent) {
                    $selected.trigger('switchSelected');
                }
            },
            able: function (target) {
                var {switcher} = __moduleName__Module.tools,
                    {identifier} = switcher.props;
                target.removeClass('-disabled');
            },
            disable: function (target) {
                var {switcher} = __moduleName__Module.tools,
                    {identifier} = switcher.props;
                target.addClass('-disabled');
            },
        },
        popup: {
            props: {
                identifier: '__moduleName__Popup',
            },
            init: function () {
                var {popup} = __moduleName__Module.tools,
                    {identifier} = popup.props;

                $document.on('click', '.' + identifier + '_close', popup.close)
                    .on('click', '.' + identifier + ' .__moduleName__Button.-close', popup.close)
                    .on('click', function (event) {
                        var $clicked = $(event.target);
                        if ($clicked.is('.' + identifier) && $('.' + identifier).is('.-open')) {
                            $('.' + identifier + '_close').trigger('click');
                        }
                    });
            },
            set: function (content, id) {
                var {popup} = __moduleName__Module.tools,
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
                var {popup} = __moduleName__Module.tools,
                    {identifier} = popup.props,
                    $popup = $('.' + identifier);

                $popup.addClass('-open');
                window.setTimeout(function () {
                    $popup.addClass('-show');
                }, 0);
            },
            close: function (event) {
                var {popup} = __moduleName__Module.tools,
                    {identifier} = popup.props,
                    $popup = $('.' + identifier);

                if ($(event.target).is('.' + identifier + '_close') && $popup.find('.__moduleName__Button.-close')) {
                    $popup.find('.__moduleName__Button.-close').trigger('click');
                }

                $popup.removeClass('-show');
                window.setTimeout(function () {
                    $popup.removeClass('-open');
                    popup.remove();
                }, 500);
            },
            create: function (id) {
                var {popup} = __moduleName__Module.tools,
                    {identifier} = popup.props,
                    html = '<div class="' + identifier + '"' + (id ? ' data-e2e-popin="' + id + '"' : '') + '><button class="' + identifier + '_close"></button><div class="' + identifier + '_content"></div></div>';
                $('body').append(html);
            },
            remove: function () {
                var {popup} = __moduleName__Module.tools,
                    {identifier} = popup.props,
                    $popup = $('.' + identifier);

                $popup.remove();
            },
            hydrate: function (content) {
                var {popup} = __moduleName__Module.tools,
                    {identifier} = popup.props;
                $('.' + identifier + '_content').html(content);

            },
            error: function (str) {
                var {popup} = __moduleName__Module.tools,
                    {identifier} = popup.props,
                    $error = '<div class="'+identifier+'_row">' +
                    '<p>' + str + '</p>' +
                    '<div class="'+identifier+'_footer -center">' +
                    '<button type="button" class="__moduleName__Button -green -close">Ok</button>' +
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
    __moduleName__Module.init();
});
