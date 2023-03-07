<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\models\repositories;

class PaymentRepository extends QueryRepository
{
    /**
     * @description Get cart id from given payment id
     *
     * @param string $pay_id
     *
     * @return array
     */
    public function getByIdPayment($pay_id = '')
    {
        if (!is_string($pay_id) || !$pay_id) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('*')
            ->from($this->prefix . $this->module_name . '_payment')
            ->where('id_payment = "' . $this->escape($pay_id) . '"')
            ->build('unique_row');

        return $result ? $result : [];
    }

    /**
     * @description Get payment id from given cart id
     *
     * @param int $cart_id
     *
     * @return array
     */
    public function getByCart($cart_id = '')
    {
        if (!is_int($cart_id) || !$cart_id) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('*')
            ->from($this->prefix . $this->module_name . '_payment')
            ->where('id_cart = ' . (int) $cart_id)
            ->build('unique_row');

        return $result ? $result : [];
    }
}
