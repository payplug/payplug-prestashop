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
        $(document)
            .on('click', 'button[name=saveConfiguration]', this.submit)
            .on('click', 'button[name=closePopin]', this.closePopin)
    }

    get() {
        const $container = $('.' + configuration.props.container);
        const $input = $container.find('input');
        const $select = $container.find('select');
        let data = {};
        let hasErrors = false;

        $input.map((key,item) => {
            var $item = $(item),
                name = $item.attr('name'),
                type = $item.attr('type'),
                value = $item.val();

            if ($item.is('.-error')) {
                hasErrors = true;
            }
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

            if ($item.is('.-error')) {
                hasErrors = true;
            }
            data[$item.attr('name')] = parseInt($item.val());
        });

        return hasErrors ? {} : data;
    }

    closePopin(event) {
        const $container = $('.' + configuration.props.container);
        $container.find('input[name=modalTriggered]').prop('checked', false);
    }
    closeAlert() {
        $('.payplugUIAlert').filter('.-error').remove();
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

        const currentConfiguration = configuration.get();

        if($.isEmptyObject(currentConfiguration)) {
            return configuration.showError();
        }

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