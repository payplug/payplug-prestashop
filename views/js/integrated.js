$(document).ready(function(){
    var $form = $('.payplugIntegratedPayment'),
        $cardholder = $('.payplugIntegratedPayment_input.-cardholder'),
        $pan = $('.payplugIntegratedPayment_input.-pan'),
        $cvv = $('.payplugIntegratedPayment_input.-cvv'),
        $exp = $('.payplugIntegratedPayment_input.-exp');

    // Create an instance of Integrated Payment
    var intPayment = new Payplug.IntegratedPayment(
            'pk_test_abcde123',                         // Your publishable key
            $form.get(0)
        ),
        cholder = intPayment.cardHolder($cardholder.get(0)),
        pan = intPayment.cardNumber($pan.get(0)),
        cvv = intPayment.cvv($cvv.get(0)),
        exp = intPayment.expiration($exp.get(0));


});
