class Description {
    props = {
        'container': '__moduleName__Configuration',
        'query': null,
        'data': {}
    };

    initialize() {
        this.handleEvents();
    }

    handleEvents() {
        $(document)
            .on('change', 'input[name=payplug_show]', this.triggerShow);
    }

    triggerShow(event) {
        const $selected = $(event.target),
            show = parseInt($selected.val());

        if (show) {
            $('.payplugUiBlock').removeClass('-disabled');
        } else {
            $('.payplugUiBlock').addClass('-disabled');
        }

        $('.bannerBlock, .descriptionBlock').removeClass('-disabled');
    }
}

const description = new Description();
description.initialize();