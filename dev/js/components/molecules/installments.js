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
            .on('focusout', 'input[name="payplug_inst_min_amount"]', installments.checkAmount);

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
    }

}

const installments = new Installments();
installments.initialize();