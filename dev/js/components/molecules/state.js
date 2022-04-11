class State {
    props = {
        'container': 'stateBlock',
        'query': null
    };

    initialize() {
        this.handleEvents();
    }

    handleEvents() {
        $(document)
            .on('click', 'button[name=stateButton]', this.checkState)
    }

    checkState() {
        const queryData = {
            _ajax: 1,
            log: 1,
            checkState: 1
        };

        if (state.props.query != null) {
            state.props.query.abort();
            state.props.query = null;
        }

        state.props.query = $.ajax({
            type: 'POST',
            url: admin_ajax_url,
            dataType: 'json',
            data: queryData,
            success: (result) => {
                if (result.content) {
                    $('._stateAlert').removeClass('-showAlert');
                } else {
                    $('._stateAlert').addClass('-showAlert');
                }
            }
        });
    }
}

const state = new State();
state.initialize();