<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS.
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
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\src\models\classes\paymentMethod;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OneClickPaymentMethod extends PaymentMethod
{
    public function __construct($dependencies)
    {
        parent::__construct($dependencies);
        $this->name = 'one_click';
    }

    /**
     * @description Get option for given configuration
     *
     * @param array $current_configuration
     *
     * @return array
     */
    public function getOption($current_configuration = [])
    {
        return [];
    }

    /**
     * @description Get payment option
     *
     * @param array $payment_options
     *
     * @return array
     */
    protected function getPaymentOption($payment_options = [])
    {
        if (!is_array($payment_options)) {
            return [];
        }

        $this->setParameters();

        $cards = $this->dependencies
            ->getPlugin()
            ->getCardAction()
            ->renderList(true);

        if (empty($cards)) {
            return $payment_options;
        }

        $payment_options = parent::getPaymentOption($payment_options);

        if (!isset($payment_options[$this->name])) {
            return $payment_options;
        }

        $default_one_click_option = $payment_options[$this->name];

        foreach ($cards as $card) {
            $payment_key = 'one_click_' . $card['id_payplug_card'];
            $brand = $this->getCardBrand($card);

            $payment_options[$payment_key] = $default_one_click_option;
            $payment_options[$payment_key]['inputs']['pc']['value'] = (int) $card['id_payplug_card'];
            $payment_options[$payment_key]['logo'] = 'none' != $card['brand']
                ? $this->img_path . 'svg/checkout/standard/'
                . $this->dependencies->getPlugin()->getTools()->tool('strtolower', $card['brand']) . '.svg'
                : '';

            $payment_options[$payment_key]['callToActionText'] = sprintf(
                $payment_options[$payment_key]['callToActionText'],
                $brand,
                $card['last4'],
                $card['expiry_date']
            );
            $payment_options[$payment_key]['action'] = $this->context->link->getModuleLink($this->dependencies->name, 'dispatcher', ['def' => isset($options['deferred']) ? (int) $options['deferred'] : 0], true);
        }

        unset($payment_options[$this->name]);

        return $payment_options;
    }

    /**
     * @description Get card brand from given array
     *
     * @param array $card
     *
     * @return mixed
     */
    protected function getCardBrand($card = [])
    {
        $default = $this->dependencies->l('payplug.getPaymentOptions.card', 'oneclickpaymentmethod');

        if (!is_array($card) || empty($card)) {
            return $default;
        }

        return 'none' != $card['brand']
            ? $this->dependencies->getPlugin()->getTools()->tool('ucfirst', $card['brand'])
            : $default;
    }
}
