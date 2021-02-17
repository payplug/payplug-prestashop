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

namespace PayPlug\src\repositories;

use PayPlug\src\specific\CartSpecific;

class PaymentRepository extends Repository
{
    private $cartSpecific;
    private $query;

    public function __construct()
    {
        $this->cartSpecific = CartSpecific::factory();
        $this->query = QueryRepository::factory();
    }

    /**
     * @description Insert cart, pay id and hash in Payplug Payment Cart table
     * @param $idPayment
     * @param $idCart
     * @param $cartHash
     * @param bool $force
     * @return bool
     */
    public function storePayment($idPayment, $idCart, $cartHash, $force = false)
    {
        $resCheck = $this->checkPaymentCart($idCart);
        $resCheck = end($resCheck);

        if (!$resCheck) {
            $this->query
                ->insert()
                ->into(_DB_PREFIX_ . 'payplug_payment_cart')
                ->fields('id_payment')->values(pSQL($idPayment))
                ->fields('id_cart')->values(pSQL($idCart))
                ->fields('cart_hash')->values(pSQL($cartHash));

            if (!$this->query->build()) {
                return false;
            }

            return true;

        } elseif ($resCheck['cart_hash'] !== $cartHash || $force) {
            return $this->updatePaymentCart($idPayment, $idCart, $cartHash);
        }
    }

    /**
     * @description Check if existing payment or installment hashed
     * @param $idCart
     * @return bool|array
     */
    public function checkPaymentCart($idCart)
    {
        $reqCheck = $this->query
            ->select()
            ->fields('*')
            ->from(_DB_PREFIX_ .'payplug_payment_cart')
            ->where('id_cart = ' . (int)$idCart);

        $resCheck = $reqCheck->build();

        if (!$resCheck) {
            return false;
        } else {
            return $resCheck;
        }
    }

    /**
     * @description Update hash and payment id in Payplug Payment Cart table
     * @param $idPayment
     * @param $idCart
     * @param $cartHash
     * @return bool
     */
    public function updatePaymentCart($idPayment, $idCart, $cartHash)
    {
        $table = _DB_PREFIX_ .'payplug_payment_cart';
        $this->query
            ->update()
            ->table($table)
            ->set($table.'.id_payment =  \''.pSQL($idPayment).'\'')
            ->set($table.'.cart_hash =  \''.pSQL($cartHash).'\'')
            ->where($table.'.id_cart = '.(int)$idCart)
        ;

        if (!$this->query->build()) {
            return false;
        }

        return true;
    }
}