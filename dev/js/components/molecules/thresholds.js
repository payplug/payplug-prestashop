class Thresholds {

    props = {
        'container': 'advancedPaylater',
        'limits': {
            min: (typeof oney_min_amounts == 'undefined') ? 0 : oney_min_amounts,
            max: (typeof oney_max_amounts == 'undefined') ? 0 : oney_max_amounts,
        },
        'query': null,
    };

    initialize() {
        this.handleEvents();
    }

    handleEvents() {
        $(document)
            .on('focusout', 'input[name="payplug_oney_custom_min_amounts"]', this.checkMin)
            .on('focusout', 'input[name="payplug_oney_custom_max_amounts"]', this.checkMax);
    }

    checkMin() {
        var val_min = $('input[name="payplug_oney_custom_min_amounts"]').val(),
            val_max = $('input[name="payplug_oney_custom_max_amounts"]').val(),
            thresholdsErrorSpan = $('.thresholdError');

        if ($('._statement').hasClass('-right')) {
            $('._statement').removeClass('-right');
        }
        if (thresholds.props.limits.min > val_min || isNaN(val_min)) {
            $('.minThreshold').addClass('-error');
            thresholdsErrorSpan.text(errorOneyThresholds.replace(/\\(.)/mg, "$1"));
            thresholdsErrorSpan.show();
            $('.thresholdErrorIcon').show();
        } else if (parseFloat(val_max) < val_min) {
            $('.minThreshold').addClass('-error');
            thresholdsErrorSpan.text(errorOneyMin.replace(/\\(.)/mg, "$1"));
            thresholdsErrorSpan.show();
            $('.thresholdErrorIcon').show();
        } else {
            $('.minThreshold').removeClass('-error');
            thresholdsErrorSpan.hide();
            $('.thresholdErrorIcon').hide();
            thresholds.checkMax();
        }

        $(window).trigger('checkConfiguration');
    }


    checkMax() {
        var val_min = $('input[name="payplug_oney_custom_min_amounts"]').val(),
            val_max = $('input[name="payplug_oney_custom_max_amounts"]').val(),
            thresholdsErrorSpan = $('.thresholdError');

        if (thresholds.props.limits.max < val_max || isNaN(val_max)) {
            $('._statement').addClass('-right');
            $('.maxThreshold').addClass('-error');
            thresholdsErrorSpan.text(errorOneyThresholds.replace(/\\(.)/mg, "$1"));
            thresholdsErrorSpan.show();
            $('.thresholdErrorIcon').show();
        } else if (parseFloat(val_min) > val_max) {
            $('._statement').addClass('-right');
            $('.maxThreshold').addClass('-error');
            thresholdsErrorSpan.text(errorOneyMax.replace(/\\(.)/mg, "$1"));
            thresholdsErrorSpan.show();
            $('.thresholdErrorIcon').show();
        } else if (val_max < thresholds.props.limits.min) {
            $('._statement').addClass('-right');
            $('.maxThreshold').addClass('-error');
            thresholdsErrorSpan.text(errorOneyThresholds.replace(/\\(.)/mg, "$1"));
            thresholdsErrorSpan.show();
            $('.thresholdErrorIcon').show();
        } else {
            $('._statement').removeClass('-right');
            $('.maxThreshold').removeClass('-error');
            thresholdsErrorSpan.hide();
            $('.thresholdErrorIcon').hide();
        }

        $(window).trigger('checkConfiguration');
    }
}

const thresholds = new Thresholds();
thresholds.initialize();