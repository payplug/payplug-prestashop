class Installments {
    props = {
        'limits': {
            min: 4,
            max: 20000,
        },
        'query': null,
    };

    initialize() {
        installments.handleEvents();
    }

    handleEvents() {
        $(document)
            .on('focusout', 'input[name="payplug_inst_min_amount"]', installments.checkAmount)
            .on('change', '.installmentSwitch input', installments.handleInstallment);
    }

    checkAmount() {
        var amount = $('input[name="payplug_inst_min_amount"]').val(),
            minAmountErrorSpan = $('.installmentError');

        if (installments.props.limits.min > amount || amount > installments.props.limits.max || isNaN(amount)) {
            $('.installmentMinAmount').addClass('-error');
            minAmountErrorSpan.text(errorInstallmentAmount);
            minAmountErrorSpan.show();
            $('.installmentErrorIcon').show();
        } else {
            $('.installmentMinAmount').removeClass('-error');
            minAmountErrorSpan.hide();
            $('.installmentErrorIcon').hide();
        }

        $(window).trigger('checkConfiguration');
    }

    handleInstallment(event) {
        const $input = $(event.target),
            $select = $('.payplugUISelect.installmentMode'),
            $inputMode = $('.payplugUIInput.installmentMinAmount').find('input');

        $(window).trigger('resetThresholders');

        if ($input.prop('checked')) {
            $select.removeClass('-disabled')
                .find('._current').attr('tabindex', '1');
            $inputMode.prop('disabled', false)
                .parents('.payplugUIInput')
                .removeClass('-disabled');
        } else {
            $select.addClass('-disabled')
                .find('._current').removeAttr('tabindex');
            $inputMode.prop('disabled', true)
                .parents('.payplugUIInput')
                .addClass('-disabled');
        }
    }
}

const installments = new Installments();
installments.initialize();