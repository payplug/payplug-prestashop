class PaymentMethod {
    props = {
        'container': 'paymentMethodBlock',
        'query': null,
        'data': {}
    };

    initialize() {
        paymentMethod.handleEvents();
        $(window).trigger('reloadEvent');
        paymentMethod.checkPaymentOptionInformation();
    }

    handleEvents() {
        $(document)
            .on('click', 'input[name=payplug_sandbox]', paymentMethod.handleSandbox)
            .on('change', '.paymentOption_switch input', paymentMethod.handlePaymentOptionInformation)
            .on('click', 'input[name=payplug_sandbox]', paymentMethod.handleSandbox)
            .on('click', 'input[name=payplug_bancontact]', paymentMethod.checkPremium)
            .on('click', 'input[name=payplug_applepay]', paymentMethod.checkPremium)
            .on('click', 'input[name=payplug_oney]', paymentMethod.checkPremium);
        $(window)
            .on('reloadEvent', paymentMethod.handleSandbox);
    }

    checkPaymentOptionInformation() {
        const $paymentOptions = $('.paymentOption');

        $paymentOptions.map((k,v) => {
            const $option = $(v);
            paymentMethod.tooglePaymentOptionInformation($option);
        })
    }

    handlePaymentOptionInformation(event) {
        const $paymentOption = $(event.target).parents('.paymentOption');
        paymentMethod.tooglePaymentOptionInformation($paymentOption);
    }

    tooglePaymentOptionInformation($paymentOption) {
        if (!$paymentOption.length) {
            return;
        }

        const $switch = $paymentOption.find('.paymentOption_switch');
        const checked = $switch.find('input').prop('checked');

        if (checked) {
            $paymentOption.find('._informations').addClass('-show');
        } else {
            $paymentOption.find('._informations').removeClass('-show');
        }
    }


    checkPremium(event) {
        event.preventDefault();
        event.stopPropagation();
        const $button = $(this);
        const paymentMethodName = $button.attr('name')
        const queryData = {
            _ajax: 1,
            checkPremium: 1
        };
        if (paymentMethod.props.query != null) {
            paymentMethod.props.query.abort();
            paymentMethod.props.query = null;
        }
        paymentMethod.props.query = $.ajax({
            type: 'POST',
            url: admin_ajax_url,
            dataType: 'json',
            data: queryData,
            error: function (jqXHR, textStatus, errorThrown) {
                alert('An error occurred while trying to checking your premium status. ' +
                    'Maybe you clicked too fast before scripts are fully loaded ' +
                    'or maybe you have a different back-office url than expected.' +
                    'You will find more explanation in JS console.');
                console.log(jqXHR, textStatus, errorThrown);
            },
            success: function (result) {
                if (result[paymentMethodName]===false){
                    paymentMethod.handleSPaymentMethod(paymentMethodName);
                }

            }
        });


    }

    handleSPaymentMethod(paymentMethodName) {
        const $container = $('.' + paymentMethod.props.container);
        $('input[name=' + paymentMethodName + ']').prop("checked", false);
        const queryData = {
            _ajax: 1,
            popin: 1,
            permissionsModal: 1,
            type: paymentMethod.getPremiumName(paymentMethodName)
        };
        if (paymentMethod.props.query != null) {
            paymentMethod.props.query.abort();
            paymentMethod.props.query = null;
        }
        paymentMethod.props.query = $.ajax({
            type: 'POST',
            url: admin_ajax_url,
            dataType: 'json',
            data: queryData,
            error: function (jqXHR, textStatus, errorThrown) {
                alert('An error occurred while trying to checking your premium status. ' +
                    'Maybe you clicked too fast before scripts are fully loaded ' +
                    'or maybe you have a different back-office url than expected.' +
                    'You will find more explanation in JS console.');
                console.log(jqXHR, textStatus, errorThrown);
            },
            success: function (result) {
                if (result.content) {
                    if ($('.payplugUIModal').length) {
                        $('.payplugUIModal').replaceWith(result.content);
                    } else {
                        $container.append(result.content);
                    }
                    $container.find('input[name=modalTriggered]').trigger('click');


                }
            }
        });
    }

    handleSandbox() {
        const $sandbox = $('input[name=payplug_sandbox]:checked');
        const sandBoxValue = parseInt($sandbox.val());
        paymentMethod.toggleBancontact(sandBoxValue);
        paymentMethod.toggleApplePay(sandBoxValue);
    }

    toggleBancontact(hide) {
        const $container = $('.' + paymentMethod.props.container);
        if ($container.find('.-bancontact').length) {
            if (hide) {
                $container.find('.-bancontact').hide();
            } else {
                $container.find('.-bancontact').show();
            }
        }
    }

    toggleApplePay(hide) {
        const $container = $('.' + paymentMethod.props.container);
        if ($container.find('.-applepay').length){
            if (hide) {
                $container.find('.-applepay').hide();
            } else {
                $container.find('.-applepay').show();
            }
        }
    }

    getPremiumName(paymentMethodName) {
        switch (paymentMethodName) {
            case 'payplug_oney' :
                var checkpremium = 'oneyPremium';
                break;
            case 'payplug_bancontact' :
                var checkpremium = 'bancontactPremium';
                break;
            case 'payplug_applepay' :
                var checkpremium = 'applepayPremium';
                break;
            default :
                var checkpremium = 'premium';
                break;
        }
        return checkpremium;
    }

}

const paymentMethod = new PaymentMethod();
$(document).ready(paymentMethod.initialize);