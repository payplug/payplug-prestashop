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
            .on('click', 'input[name=payplug_sandbox]', this.handleSandbox)
            .on('click', 'button[name=logout]', this.logout)
            .on('click', 'button[name=closePopin]', this.closePopin)
            .on('click', '.alertLiveButton', this.handleAlertLiveButton)
            .on('click', 'button[name=submitSandbox]', this.submitSandbox)
            .on('click', 'button[name=validateLive]', this.validateLive)
            .on('click', 'input[name=modalTriggered]', this.handleModal)
    }

    handleAlertLiveButton(event) {
            event.preventDefault();
            event.stopPropagation();

        const $sandbox = $('input[name="payplug_sandbox"][value="0"]');
        if ($sandbox.data('notallowed')) {
            return general.checkOnboarding();
        } else {
            return $sandbox.trigger('click');
        }
    }

    handleModal(event) {
        const $checkbox = $(event.target);
        const $modal = $('.payplugUIModal');
        if (!$checkbox.prop('checked') && $modal.find('button[name=validateLive]').length) {
            return general.setSandboxAllowed();
        }
    }

    checkOnboarding() {
        const $container = $('.' + general.props.container);

        const queryData = {
            _ajax: 1,
            log: 1,
            checkOnboarding: 1
        };

        if (general.props.query != null) {
            general.props.query.abort();
            general.props.query = null;
        }

        general.props.query = $.ajax({
            type: 'POST',
            url: admin_ajax_url,
            dataType: 'json',
            data: queryData,
            success: (result) => {
                if (result.content) {
                    general.reloadFromContent(result.content);
                } else {
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

    submitSandbox(event) {
        event.preventDefault();
        event.stopPropagation();
        const queryData = {
            _ajax: 1,
            log: 1,
            submitSandbox: 1,
            payplug_password: $('input[name="password"]').val(),
        };
        const $container = $('.' + general.props.container);
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

    closePopin(event) {
        const $container = $('.' + general.props.container);
        $container.find('input[name=modalTriggered]').prop('checked', 'false');
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
        const $container = $('.' + general.props.container);
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
                alert('An error occurred while trying to checking your premium status. ' +
                    'Maybe you clicked too fast before scripts are fully loaded ' +
                    'or maybe you have a different back-office url than expected.' +
                    'You will find more explanation in JS console.');
                console.log(jqXHR, textStatus, errorThrown);
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

    validateLive(event) {
        event.preventDefault();
        event.stopPropagation();

        // close the modal
        const $container = $('.' + general.props.container);
        $container.find('input[name=modalTriggered]').trigger('click');

        general.setSandboxAllowed();
    }

    setSandboxAllowed() {
        // set sandbox value
        const $sandBoxLive = $('input[name="payplug_sandbox"][value="0"]');
        $sandBoxLive.data("notallowed", 0).trigger('click');

        // remove alert block
        $('.onboardingAlert').remove();
    }

    handleSandbox(event) {
        const $sandbox = $(event.currentTarget);
        const sandBoxValue = parseInt($sandbox.val());

        if (!sandBoxValue && $sandbox.data('notallowed')) {
            event.preventDefault();
            event.stopPropagation();

            return general.checkOnboarding();
        }

        return general.toggleDescription(event);
    }

    reloadFromContent(content) {
        $('.'+module_name+'Configuration').replaceWith(content);
    }

    toggleDescription(event) {
        const $sandbox = $(event.currentTarget);
        const sandBoxValue = parseInt($sandbox.val());
        $('._sandboxDescription').removeClass('-hide');
        if (sandBoxValue) {
            $('._sandboxDescription.-live').addClass('-hide');
        } else {
            $('._sandboxDescription.-test').addClass('-hide');
        }
    }
}

const general = new General();
general.initialize();