window.iso_user = admin_iso_code;

$(document).ready(function(){
    window.iso_user = admin_iso_code;

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
