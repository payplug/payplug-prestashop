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

namespace PayPlug\classes;

use DateInterval;
use DateTime;

class CartClass
{
    private $logger;
    private $dependencies;
    private $query;
    private $constant;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->query = $this->dependencies->getPlugin()->getQuery();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
    }

    /**
     * @description Create a lock from a Cart ID
     *
     * @param int $id_cart
     *
     * @return bool
     */
    public function createLockFromCartId($id_cart = 0)
    {
        if (!$id_cart || !is_int($id_cart)) {
            return false;
        }

        $this->logger = $this->dependencies->getPlugin()->getLogger();

        $this->logger->addLog('Lock creation', 'notice');

        $creation_date = new DateTime('now');
        $duration = '10S';
        $lifetime = new DateInterval('PT' . $duration);
        $end_of_life = $creation_date->add($lifetime);

        do {
            $cart_lock = $this->dependencies->payplugLock->createLockG2($id_cart, $this->dependencies->name);

            if (!$cart_lock) {
                $time = new DateTime('now');
                if ($time > $end_of_life) {
                    $this->logger->addLog(
                        'Try to create lock during ' . $duration . ' sec, but can\'t proceed',
                        'error'
                    );

                    return false;
                }
            } else {
                $this->logger->addLog('Lock created', 'notice');
            }
        } while (!$cart_lock);

        return true;
    }

    /**
     * @description Delete payplug lock for given id cart
     *
     * @param int $id_cart
     *
     * @return bool
     */
    public function deleteLockFromCartId($id_cart = 0)
    {
        if (!$id_cart || !is_int($id_cart)) {
            return false;
        }

        return $this->dependencies->payplugLock->deleteLockG2($id_cart);
    }

    /**
     * @description Get cart installment
     *
     * @param int $id_cart
     *
     * @return int
     */
    public function getPayplugInstallmentCart($id_cart = 0)
    {
        if (!$id_cart || !is_int($id_cart)) {
            return 0;
        }

        return $this->query
            ->select()
            ->fields('id_payment')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_payment')
            ->where('id_cart = ' . (int) $id_cart)
            ->build('unique_value')
        ;
    }

    /**
     * @description get cart installment backward
     *
     * @param int $id_cart
     *
     * @return mixed
     *
     * @deprecated use for installment from PayPlug 3.1.3 or further
     */
    public function getPayplugInstallmentCartBackward($id_cart = 0)
    {
        if (!$id_cart || !is_int($id_cart)) {
            return 0;
        }

        return $this->query
            ->select()
            ->fields('id_installment')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_installment_cart')
            ->where('id_cart = ' . (int) $id_cart)
            ->build('unique_value')
        ;
    }
}
