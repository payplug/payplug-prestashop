<?php
/**
 * 2013 - 2020 PayPlug SAS
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
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

class PayPlugPaymentOneClick extends PayplugPaymentStandard
{
    /** @var int */
    public $id_company;

    /** @var string */
    public $payment_card;

    /** @var string */
    public $type = 'oneclick';

    /**
     * Constructor
     *
     * @param string $id_card
     * @return PayplugPayment
     */
    public function __construct($id_card = null, $options = array())
    {
        parent::__construct($id_card, $options);

        $this->is_allowed = $this->module->getConfiguration('PAYPLUG_ONE_CLICK');
        $this->id_company = $this->module->getConfiguration('PAYPLUG_COMPANY_ID');
        $this->definition_tab['payment_method'] = array(
            'type' => 'string',
            'validate' => 'isCleanHtml',
            'required' => true
        );

        $payplug_card = new PayPlugCard($this->card);
        $this->payment_card = Validate::isLoadedObject($payplug_card) ? $payplug_card : null;

        $this->type = 'oneclick';

        $this->generatePaymentTab();
        $this->validatePaymentTab();

        return $this;
    }

    /**
     * Generate the tab to create the oneclick payment in Payplug API
     *
     * @return bool
     */
    public function generatePaymentTab()
    {
        parent::generatePaymentTab();

        if (Validate::isLoadedObject($this->payment_card)) {
            $this->payment_tab['payment_method'] = $this->payment_card->id_card;
            $this->payment_tab['allow_save_card'] = false;
            $this->payment_tab['initiator'] = 'PAYER';
        }
    }
}
