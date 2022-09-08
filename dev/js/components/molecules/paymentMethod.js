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
            .on('change', '.paymentMethod_switch input', paymentMethod.handlePaymentOption)
            .on('change', 'input[name="payplug_one_click"]', paymentMethod.handleOneClickPermission)
            .on('change', 'input[name="payplug_deferred"]', paymentMethod.handleDeferredPermission)
            .on('change', 'input[name="payplug_inst"]', paymentMethod.handleInstallmentPermission);
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
            $switch = $input.parents('._switch').eq(0),
            $sandbox = $('input[name=payplug_sandbox]:checked');

        if (!parseInt($sandbox.val())) {
            event.preventDefault();
            event.stopPropagation();
            paymentMethod.checkPremium($switch);
        }
    }

    handleInstallmentPermission(event) {
        const $input = $(event.target),
            $switch = $input.parents('._switch').eq(0),
            $sandbox = $('input[name=payplug_sandbox]:checked');

        if (!parseInt($sandbox.val())) {
            event.preventDefault();
            event.stopPropagation();
            paymentMethod.checkPremium($switch);
        }
    }

    handleOneClickPermission(event) {
        const $switch = $(event.target).parents('._switch').eq(0),
            $sandbox = $('input[name=payplug_sandbox]:checked'),
            isSandBox = parseInt($sandbox.val());

        if (!isSandBox) {
            event.preventDefault();
            event.stopPropagation();
            paymentMethod.checkPremium($switch);
        }
    }

    checkPaymentOptionInformation() {
        const $paymentOptions = $('.paymentMethod');

        $paymentOptions.map((k, v) => {
            const $switch = $(v).find('.paymentMethod_switch');
            paymentMethod.tooglePaymentOptionInformation($switch);
        })
    }

    handlePaymentOption(event) {
        const $switch = $(event.target).parents('.paymentMethod_switch');
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
        const $paymentOption = $switch.parents('.paymentMethod'),
            checked = $switch.find('input').prop('checked');

        if (checked) {
            $paymentOption.find('._additionnal').addClass('-show');
        } else {
            $paymentOption.find('._additionnal').removeClass('-show');
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

        $('.paymentMethod_switch').each(function() {
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
                $('.paymentMethod_switch input').removeAttr('disabled');
            },
            success: function (result) {
                if (typeof result != 'undefined') {
                    if (switchToggle) {
                        if (typeof result[paymentMethodName] != 'undefined' && !result[paymentMethodName]) {
                            paymentMethod.handlePaymentMethod(paymentMethodName);
                        } else {
                            paymentMethod.checkPaymentOptionInformation();
                        }
                        $('.paymentMethod_switch input').removeAttr('disabled');
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
        const $container = $('.' + paymentMethod.props.container),
            $paymentMethods = $container.find('.paymentMethod');
        if ($paymentMethods.length) {
            $paymentMethods.map((i,e) => {
                let $paymentMethod = $(e);
                if (!$paymentMethod.is('.-useSandbox')) {
                    if (hide) {
                        $paymentMethod.addClass('-sandbox');
                    } else {
                        $paymentMethod.removeClass('-sandbox');
                    }
                }
            });
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
            case 'payplug_amex' :
                var checkpremium = 'amexPremium';
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