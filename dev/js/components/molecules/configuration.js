/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS
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
 *  @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */
class Configuration {
    props = {
        'container': '__moduleName__Configuration',
        'query': null,
        'data': {}
    };

    initialize() {
        configuration.handleEvents();
    }

    handleEvents() {
        const {container} = configuration.props;
        $(document)
            .on('click', '.' + container + ' button[name=saveConfiguration]', this.submit)
            .on('click', '.' + container + ' button[name=closePopin]', this.closePopin);
        $(window)
            .on('checkConfiguration', configuration.checkConfiguration);
    }

    get() {
        const $container = $('.' + configuration.props.container);
        const $input = $container.find('input');
        const $select = $container.find('select');
        let data = {};

        $input.map((key,item) => {
            var $item = $(item),
                name = $item.attr('name'),
                type = $item.attr('type'),
                value = $item.val();

            if(typeof name != 'undefined' && name) {
                switch (type) {
                    case 'radio' :
                        if ($item.prop('checked')) {
                            if (isNaN(value)) {
                                data[name] = $item.val();
                            } else {
                                data[name] = value;
                            }
                        }
                        break;
                    case 'checkbox' :
                        data[name] = $item.prop('checked') ? 1 : 0;
                        break;
                    default :
                        data[name] = value;
                        break;
                }
            }
        });

        $select.map((key,item) => {
            var $item = $(item);
            data[$item.attr('name')] = parseInt($item.val());
        });

        return data;
    }

    closePopin(event) {
        const $container = $('.' + configuration.props.container);
        $container.find('input[name=modalTriggered]').prop('checked', false);
    }

    closeAlert() {
        $('.payplugUIAlert').filter('.-error').remove();
    }

    isValidConfiguration() {
        const $container = $('.' + configuration.props.container);
        const $input = $container.find('input');
        const $select = $container.find('select');
        let hasError = false;

        $input.map((key,item) => {
            var $item = $(item);
            if ($item.is('.-error') || $item.parents().is('.-error')) {
                hasError = true;
            }
        });
        $select.map((key,item) => {
            var $item = $(item);
            if ($item.is('.-error') || $item.parents().is('.-error')) {
                hasError = true;
            }
        });

        return !hasError;
    }

    checkConfiguration() {
        const $container = $('.' + configuration.props.container);
        const isValid = configuration.isValidConfiguration();
        if (isValid) {
            $container.find('button[name="saveConfiguration"]')
                .removeClass('-disabled')
                .removeAttr('disabled');
        } else {
            $container.find('button[name="saveConfiguration"]')
                .addClass('-disabled')
                .attr('disabled', 'disabled');
        }
    }

    showError() {
        const $container = $('.' + configuration.props.container);
        const $button = $('button[name=saveConfiguration]');
        const queryData = {
            _ajax: 1,
            modal: 1,
            type: 'error',
            errorMessage: ''
        };

        // get Error form other block...
        // ... from Oney
        // ... from Installment
        // ... from Deferred

        if (configuration.props.query != null) {
            configuration.props.query.abort();
            configuration.props.query = null;
        }

        configuration.props.query = $.ajax({
            type: 'POST',
            url: admin_ajax_url,
            dataType: 'json',
            data: queryData,
            beforeSend: () => {
                $button.addClass('-disabled').attr('disabled', 'disabled');
            },
            error: (jqXHR, textStatus, errorThrown) => {
                alert('An error occurred while trying to checking your premium status. ' +
                    'Maybe you clicked too fast before scripts are fully loaded ' +
                    'or maybe you have a different back-office url than expected.' +
                    'You will find more explanation in JS console.');
                console.log(jqXHR, textStatus, errorThrown);
                $button.removeClass('-disabled').removeAttr('disabled');
            },
            success: (result) => {
                $button.removeClass('-disabled').removeAttr('disabled');
                if (result.modal) {
                    if ($('.payplugUIModal').length) {
                        $('.payplugUIModal').replaceWith(result.modal);
                    } else {
                        $container.append(result.modal);
                    }
                    $container.find('input[name=modalTriggered]').trigger('click');
                }
            }
        });
    }

    submit(event) {
        event.preventDefault();
        event.stopPropagation();

        configuration.closeAlert();

        if (!configuration.isValidConfiguration()) {
            return configuration.showError();
        }

        const currentConfiguration = configuration.get();
        const queryData = {
            _ajax: 1,
            save: 1,
            ...currentConfiguration
        };
        const $container = $('.' + configuration.props.container);
        const $button = $(this);

        if (configuration.props.query != null) {
            configuration.props.query.abort();
            configuration.props.query = null;
        }

        configuration.props.query = $.ajax({
            type: 'POST',
            url: admin_ajax_url,
            dataType: 'json',
            data: queryData,
            beforeSend: () => {
                $button.addClass('-disabled').attr('disabled', 'disabled');
            },
            error: (jqXHR, textStatus, errorThrown) => {
                alert('An error occurred while trying to checking your premium status. ' +
                    'Maybe you clicked too fast before scripts are fully loaded ' +
                    'or maybe you have a different back-office url than expected.' +
                    'You will find more explanation in JS console.');
                console.log(jqXHR, textStatus, errorThrown);
                $button.removeClass('-disabled').removeAttr('disabled');
            },
            success: (result) => {
                $button.removeClass('-disabled').removeAttr('disabled');
                if (result.modal) {
                    if ($('.payplugUIModal').length) {
                        $('.payplugUIModal').replaceWith(result.modal);
                    } else {
                        $container.append(result.modal);
                    }
                    $container.find('input[name=modalTriggered]').trigger('click');
                }
            }
        });
    }
}

const configuration = new Configuration();
$(document).ready(configuration.initialize);