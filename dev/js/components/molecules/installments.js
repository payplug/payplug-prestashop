class Installments {
    props = {
        'limits': {
            min: 4,
            max: 20000,
        },
        'query': null,
    };

    initialize() {
        this.handleEvents();
    }

    handleEvents() {
        $(document)
            .on('focusout', 'input[name="payplug_inst_min_amount"]', this.checkAmount);
    }

    checkAmount() {
        var amount = $('input[name="payplug_inst_min_amount"]').val(),
            minAmountErrorSpan = $('.installmentError'),
            matches = amount.match(/^[0-9]+$/);

        if ( installments.props.limits.min > amount || amount > installments.props.limits.max || matches == null ) {
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