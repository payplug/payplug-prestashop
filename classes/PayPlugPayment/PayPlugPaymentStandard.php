<?php
/**
 * 2013 - 2021 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

class PayPlugPaymentStandard extends PayplugPayment
{
    /**
     * Constructor
     *
     * @param string $id_card
     * @return PayplugPayment
     */
    public function __construct($id_card = null, $options = [])
    {
        parent::__construct($id_card, $options);

        $this->definition_tab['amount'] = ['type' => 'int', 'validate' => 'isInt', 'required' => true];
        $this->definition_tab['force_3ds'] = [
            'type' => 'bool',
            'validate' => 'isBool',
            'required' => false,
            'default' => false
        ];
        $this->definition_tab['allow_save_card'] = ['type' => 'bool', 'validate' => 'isBool', 'required' => true];
        $this->type = 'standard';

        $this->generatePaymentTab();
        $this->validatePaymentTab();

        return $this;
    }

    /**
     * Generate the tab to create the payment in Payplug API
     */
    public function generatePaymentTab()
    {
        parent::generatePaymentTab();

        if ($this->is_deferred) {
            $this->payment_tab['authorized_amount'] = $this->getCartAmount($this->payment_tab['currency']);
        } else {
            $this->payment_tab['amount'] = $this->getCartAmount($this->payment_tab['currency']);
        }

        $this->payment_tab['allow_save_card'] = Cart::isGuestCartByCartId($this->cart->id) != 1
            && Configuration::get('PAYPLUG_ONE_CLICK');
        $this->payment_tab['force_3ds'] = false;
    }

    /**
     * Register payment for later use
     *
     * @param string $pay_id
     * @return bool
     */
    public function register($pay_id = 'pending')
    {
        if ($inst_id = $this->getInstallment()) {
            $this->deleteInstallment($inst_id);
        }

        return parent::register($pay_id);
    }
}
