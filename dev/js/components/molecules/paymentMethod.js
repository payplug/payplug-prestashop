class PaymentMethod {
    props = {
        'container': 'paymentMethodBlock',
        'query': null,
        'data': {}
    };

    initialize() {
        paymentMethod.handleEvents();
        $(window).trigger('reloadEvent');
    }

    handleEvents() {
        $(document)
            .on('click', 'input[name=payplug_sandbox]', paymentMethod.handleSandbox);
        $(window)
            .on('reloadEvent', paymentMethod.handleSandbox);
    }

    handleSandbox() {
        const $sandbox = $('input[name=payplug_sandbox]:checked');
        const sandBoxValue = parseInt($sandbox.val());
        paymentMethod.toggleBancontact(sandBoxValue);
    }

    toggleBancontact(hide) {
        const $container = $('.' + paymentMethod.props.container);
        if ($container.find('.-bancontact').length){
            if (hide) {
                $container.find('.-bancontact').hide();
            } else {
                $container.find('.-bancontact').show();
            }
        }
    }
}

const paymentMethod = new PaymentMethod();
$(document).ready(paymentMethod.initialize);