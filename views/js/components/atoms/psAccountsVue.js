console.log('init: ' + window.iso_user);
window.iso_user = 'en';

$(document).ready(function(){
    console.log('ready: ' + window.iso_user);
    window.iso_user = 'en';

    console.log('last: ' + window.iso_user);

    $(window).on('load', function(){
        console.log('window loaded: ' + window.iso_user);
        if (typeof window.psaccountsVue != 'undefined') {
            console.log('window.psaccountsVue ok: ' + window.iso_user);
            window?.psaccountsVue?.init();
        } else {
            console.log('window.psaccountsVue ko: ' + window.iso_user);
            require('prestashop_accounts_vue_components').init();
        }
    });
    // window?.psaccountsVue?.init() || require('prestashop_accounts_vue_components').init();
});