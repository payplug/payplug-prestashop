class Deferred {
    props = {
        'container': 'standardPaymentAdvanced',
        'idOrderState': null,
        'query': null,
    };


    initialize() {
        deferred.handleEvents();
    }

    handleEvents() {
        $(document)
            .on('ready', deferred.setIdOrderState)
            .on('change', '.payplugUISelect.-deferred input', deferred.handleDeferredState);
    }

    setIdOrderState() {
        const $container = $('.' + deferred.props.container);
        deferred.props.idOrderState = $container.find('input[name="payplug_deferred_state"]:checked').val();
    }

    handleDeferredState() {
        const oldOrderState = deferred.props.idOrderState;
        deferred.setIdOrderState();

        if (!parseInt(deferred.props.idOrderState)) {
            deferred.removeDisplayOrderStatusAlert();
        } else if (parseInt(oldOrderState) != parseInt(deferred.props.idOrderState)) {
            deferred.displayOrderStatusAlert();
        }
    }



    removeDisplayOrderStatusAlert() {
        const $content = $('._standardAdvancedOption.-deferred').find('._content'),
            $alert = $content.find('.payplugUITextAlert');
        if ($alert.length) {
            $alert.remove();
        }
    }

    displayOrderStatusAlert() {
        const queryData = {
            _ajax: 1,
            alert: 1,
            type: 'orderState',
            idOrderState: deferred.props.idOrderState
        };

        if (deferred.props.query != null) {
            deferred.props.query.abort();
            deferred.props.query = null;
        }
        deferred.props.query = $.ajax({
            type: 'POST',
            url: admin_ajax_url,
            dataType: 'json',
            data: queryData,
            error: function (jqXHR, textStatus, errorThrown) {
                if (errorThrown != 'abort') {
                    alert('An error occurred while trying to checking your premium status. ' +
                        'Maybe you clicked too fast before scripts are fully loaded ' +
                        'or maybe you have a different back-office url than expected.' +
                        'You will find more explanation in JS console.');
                    console.log(jqXHR, textStatus, errorThrown);
                }
            },
            success: function (result) {
                if (typeof result.alert != 'undefined' && result.alert) {
                    deferred.removeDisplayOrderStatusAlert();
                    const $content = $('._standardAdvancedOption.-deferred').find('._content');
                    $content.append(result.alert);
                }
            }
        });
    }
}

const deferred = new Deferred();
deferred.initialize();