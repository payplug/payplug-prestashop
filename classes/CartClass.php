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
 * Do not edit or add to this file if you wish to upgrade Payplug module to newer
 * versions in the future.
 *
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CartClass
{
    private $logger;
    private $dependencies;
    private $constant;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
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

        $creation_date = new \DateTime('now');
        $duration = '10S';
        $lifetime = new \DateInterval('PT' . $duration);
        $end_of_life = $creation_date->add($lifetime);

        do {
            $cart_lock = $this->dependencies->payplugLock->createLockG2($id_cart, $this->dependencies->name);

            if (!$cart_lock) {
                $time = new \DateTime('now');
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
}
