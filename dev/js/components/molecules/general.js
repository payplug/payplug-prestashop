class General {
    props = {
        'container': 'generalBlock',
        'query': null
    };

    initialize() {
        this.handleEvents();
    }

    handleEvents() {
        $(document)
            .on('click', 'button[name=showLogin]', this.showLogin)
            .on('click', 'button[name=hideLogin]', this.hideLogin)
            .on('click', 'button[name=login]', this.login)
            .on('click', 'input[name=payplug_sandbox]', this.toggleDescription)
            .on('click', 'button[name=logout]', this.logout)
            .on('click', 'button[name=closePopin]', this.closePopin)
    }

    closePopin(event) {
        const $container = $('.' + general.props.container);
        $container.find('input[name=modalTriggered]').prop('checked', false);
    }

    hideLogin(event) {
        event.preventDefault();
        event.stopPropagation();
        $('.generalBlock.-subscribe').removeClass('-hide');
        $('.generalBlock.-login').addClass('-hide');
    }

    showLogin(event) {
        event.preventDefault();
        event.stopPropagation();
        $('.generalBlock.-subscribe').addClass('-hide');
        $('.generalBlock.-login').removeClass('-hide');
    }

    login(event) {
        event.preventDefault();
        event.stopPropagation();

        const queryData = {
            _ajax: 1,
            log: 1,
            submitAccount: 1,
            payplug_email: $('input[name="userEmail"]').val(),
            payplug_password: $('input[name="userPassword"]').val(),
        };
        const $button = $(this);
        const $container = $button.parents('.' + general.props.container);

        if ($button.is('-disabled')) {
            return;
        }

        if (general.props.query != null) {
            general.props.query.abort();
            general.props.query = null;
        }

        general.props.query = $.ajax({
            type: 'POST',
            url: admin_ajax_url,
            dataType: 'json',
            data: queryData,
            beforeSend: () => {
                $button.addClass('-disabled').attr('disabled', 'disabled');
            },
            error: (jqXHR, textStatus, errorThrown) => {
                $button.removeClass('-disabled').removeAttr('disabled');
            },
            success: (result) => {
                if (result.content) {
                    general.reloadFromContent(result.content);
                } else {
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
            }
        });
    }

    logout(event) {
        event.preventDefault();
        event.stopPropagation();

        const queryData = {
            _ajax: 1,
            submitDisconnect: 1
        };
        const $button = $(this);
        if ($button.is('-disabled')) {
            return;
        }

        if (general.props.query != null) {
            general.props.query.abort();
            general.props.query = null;
        }

        general.props.query = $.ajax({
            type: 'POST',
            url: admin_ajax_url,
            dataType: 'json',
            data: queryData,
            beforeSend: function () {
                $button.addClass('-disabled').attr('disabled', 'disabled');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $button.addClass('-disabled').attr('disabled', 'disabled');
            },
            success: function (result) {
                if (result.content) {
                    general.reloadFromContent(result.content);
                } else {
                    $button.removeClass('-disabled').removeAttr('disabled');
                }
            }
        });
    }

    reloadFromContent(content) {
        $('.'+module_name+'Configuration').replaceWith(content);
    }

    toggleDescription(event) {
        const sandbox = parseInt($(this).val());
        $('._sandboxDescription').removeClass('-hide');
        if (sandbox) {
            $('._sandboxDescription.-live').addClass('-hide');
        } else {
            $('._sandboxDescription.-test').addClass('-hide');
        }
    }
}

const general = new General();
general.initialize();