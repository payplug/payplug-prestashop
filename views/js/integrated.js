$(document).ready(function(){
        // Create an instance of Integrated Payment
        var intPayment = new Payplug.IntegratedPayment(
            'pk_test_abcde123',                         // Your publishable key
            'pay_45678azerty',                          // A payment ID created server-side
            document.querySelector('.my-payment-form')  // A reference to your form
        );

        // Add each payments fields
        var pan = intPayment.pan(document.querySelector('.pan-input-container'));
        var cvv = intPayment.cvv(document.querySelector('.cvv-input-container'));
        var exp = intPayment.exp(document.querySelector('.exp-input-container'));
});
