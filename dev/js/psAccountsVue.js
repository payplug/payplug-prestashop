$(document).ready(function(){
    $(window).on('load', function(){
        if (typeof window.psaccountsVue != 'undefined') {
            window?.psaccountsVue?.init();
        } else {
            require('prestashop_accounts_vue_components').init();
        }
    });

    if (typeof window.psaccountsVue != 'undefined') {
        window?.psaccountsVue?.init();
    } else {
        require('prestashop_accounts_vue_components').init();
    }
});
