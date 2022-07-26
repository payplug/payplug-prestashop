class PaymentMethod {
    props = {
        'container': 'paymentMethodBlock',
        'query': null,
        'data': {}
    };

    initialize() {
        paymentMethod.handleEvents();
        paymentMethod.handleReloadContent();
    }

    handleEvents() {
        $(document)
            .on('click', 'input[name=payplug_sandbox]', paymentMethod.handleSandbox)
            .on('change', '.paymentOption_switch input', paymentMethod.handlePaymentOption)
            .on('change', '.oneClickSwitch input', paymentMethod.handleOneClickPermission)
            .on('change', '.deferredSwitch input', paymentMethod.handleDeferredPermission)
            .on('change', '.installmentSwitch input', paymentMethod.handleInstallmentPermission);
        $(window)
            .on('reloadEvent', paymentMethod.handleReloadContent)
            .on('resetThresholders', paymentMethod.resetThresholders);
    }

    handleReloadContent() {
        paymentMethod.handleSandbox();
        paymentMethod.checkPaymentOptionInformation();
    }

    handleDeferredPermission(event) {
        const $input = $(event.target),
            $switch = $input.parents('.deferredSwitch'),
            $sandbox = $('input[name=payplug_sandbox]:checked');

        if (!parseInt($sandbox.val())) {
            event.preventDefault();
            event.stopPropagation();
            paymentMethod.checkPremium($switch);
        }
    }

    handleInstallmentPermission(event) {
        const $input = $(event.target),
            $switch = $input.parents('.installmentSwitch'),
            $sandbox = $('input[name=payplug_sandbox]:checked');

        if (!parseInt($sandbox.val())) {
            event.preventDefault();
            event.stopPropagation();
            paymentMethod.checkPremium($switch);
        }
    }

    handleOneClickPermission(event) {
        const $switch = $(event.target).parents('.oneClickSwitch'),
            $sandbox = $('input[name=payplug_sandbox]:checked'),
            isSandBox = parseInt($sandbox.val());

        if (!isSandBox) {
            event.preventDefault();
            event.stopPropagation();
            paymentMethod.checkPremium($switch);
        }
    }

    checkPaymentOptionInformation() {
        const $paymentOptions = $('.paymentOption');

        $paymentOptions.map((k, v) => {
            const $switch = $(v).find('.paymentOption_switch');
            paymentMethod.tooglePaymentOptionInformation($switch);
        })
    }

    handlePaymentOption(event) {
        const $switch = $(event.target).parents('.paymentOption_switch');
        const $sandbox = $('input[name=payplug_sandbox]:checked');
        const isSandBox = parseInt($sandbox.val());

        var payment_method = $switch.find('._switch').data('e2e-name');
        if (payment_method == 'paymentMethod_oney' || payment_method == 'paymentMethod_standard') {
            paymentMethod.resetThresholders();
        }

        if (!isSandBox && $switch.is('.-premium')) {
            event.preventDefault();
            event.stopPropagation();
            paymentMethod.checkPremium($switch);
        } else {
            paymentMethod.checkPaymentOptionInformation();
        }
    }

    resetThresholders() {
        var thresholdersInputs = {
            installmentMinAmountInput : {
                element : $('input[data-e2e-name="installmentMinAmount"]'),
                amount : inst_min_amount
            },
            oneyThresholdMinInput : {
                element : $('input[data-e2e-name="oneyThresholdMin"]'),
                amount : oney_min_amounts
            },
            oneyThresholdMaxInput : {
                element : $('input[data-e2e-name="oneyThresholdMax"]'),
                amount : oney_max_amounts
            }
        };

        $.each(thresholdersInputs, function(index) {
            if (thresholdersInputs[index].element.parent().hasClass('-error') === true) {
                thresholdersInputs[index].element.val(thresholdersInputs[index].amount);
                thresholdersInputs[index].element.focusin();
                thresholdersInputs[index].element.focusout();
            }
        });

        $(window).trigger('checkConfiguration');
    }

    tooglePaymentOptionInformation($switch) {
        if (!$switch.length) {
            return;
        }
        const $paymentOption = $switch.parents('.paymentOption');
        const checked = $switch.find('input').prop('checked');

        if (checked) {
            $paymentOption.find('._informations').addClass('-show');
        } else {
            $paymentOption.find('._informations').removeClass('-show');
        }
    }

    checkPremium($switch) {
        const $sandbox = $('input[name=payplug_sandbox]:checked');
        const checked = $switch.find('input').prop('checked');
        if (!checked) {
            paymentMethod.checkPaymentOptionInformation();
            return;
        }

        if (typeof $switch.find('input').attr('disabled') != 'undefined') {
            return;
        }

        $('.paymentOption_switch').each(function() {
            if ($(this).find('input').attr('name') != $switch.find('input').attr('name') && checked === true) {
                $(this).find('input').attr('disabled', 'disabled');
            }
        });

        const paymentMethodName = $switch.find('input').attr('name');
        paymentMethod.getPermissions($sandbox, true, paymentMethodName);
    }

    handlePaymentMethod(paymentMethodName) {
        const $container = $('.' + paymentMethod.props.container);
        const $input = $('input[name=' + paymentMethodName + ']');
        const $switch = $input.parents('.payplugUISwitch');

        $input.prop('checked', false).trigger('change');
        paymentMethod.tooglePaymentOptionInformation($switch);

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
                if (errorThrown != 'abort') {
                    alert('An error occurred while trying to checking your premium status. ' +
                        'Maybe you clicked too fast before scripts are fully loaded ' +
                        'or maybe you have a different back-office url than expected.' +
                        'You will find more explanation in JS console.');
                    console.log(jqXHR, textStatus, errorThrown);
                }
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
                paymentMethod.checkPaymentOptionInformation();
            }
        });
    }

    handleSandbox() {
        const $sandbox = $('input[name=payplug_sandbox]:checked');
        const sandBoxValue = parseInt($sandbox.val());

        if ($sandbox.data('notallowed')) {
            return;
        }

        paymentMethod.getPermissions(sandBoxValue, false);
        paymentMethod.togglePaymentOption(sandBoxValue);
        }

    getPermissions(sandBoxValue, switchToggle, paymentMethodName = '') {
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
                if (errorThrown !== 'abort') {
                    alert('An error occurred while trying to checking your premium status. ' +
                        'Maybe you clicked too fast before scripts are fully loaded ' +
                        'or maybe you have a different back-office url than expected.' +
                        'You will find more explanation in JS console.');
                    console.log(jqXHR, textStatus, errorThrown);
                }
                $('.paymentOption_switch input').removeAttr('disabled');
            },
            success: function (result) {
                if (typeof result != 'undefined') {
                    if (switchToggle) {
                        if (typeof result[paymentMethodName] != 'undefined' && !result[paymentMethodName]) {
                            paymentMethod.handlePaymentMethod(paymentMethodName);
                        } else {
                            paymentMethod.checkPaymentOptionInformation();
                        }
                        $('.paymentOption_switch input').removeAttr('disabled');
                        return result;
                    } else {
                        paymentMethod.paymentMethodToggle(result, sandBoxValue);
                    }
                }
            }
        });
    }

    paymentMethodToggle(permissions, sandBoxValue) {
        if (!sandBoxValue) {
            $.map(permissions, function (value, index) {
                if (index !== 'payplug_sandbox' && !value) {
                    $('input[name=' + index + ']:checked').trigger('click');
                }
            });
        }

    }

    togglePaymentOption(hide) {
        const $container = $('.' + paymentMethod.props.container);
        if ($container.find('.options').length) {
            if (hide) {
                $container.find('.options').addClass('-disabled');
                $container.find('._sandboxDescription').removeClass('-disabled');
                $container.find('._liveDescription').addClass('-disabled');

            } else {
                $container.find('.options').removeClass('-disabled');
                $container.find('._sandboxDescription').addClass('-disabled');
                $container.find('._liveDescription').removeClass('-disabled');
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