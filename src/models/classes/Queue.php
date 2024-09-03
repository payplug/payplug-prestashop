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

namespace PayPlug\src\models\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Queue
{
    public $dependencies;
    public $lock_key;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description set up the locking mechanism or queue creation for a given cart.
     *
     * @param int $id_cart
     * @param string $id_resource
     * @param string $end_of_life
     * @param string $duration
     */
    public function setLockOrQueue($id_cart = 0, $id_resource = '', $end_of_life = '', $duration = '')
    {
        $logger = $this->dependencies->getPlugin()->getLogger();

        if (!isset($cart) || !isset($resource)) {
            $logger->addLog('Cart or resource is not set.', 400);
        }

        // check if queueing system is enabled
        if ($this->dependencies->configClass->isValidFeature('feature_queueing_system')) {
            $logger->addLog('Notification: Attempting to set queue for Cart ID: ' . $id_cart);
            $create_queue = $this->dependencies
                ->getPlugin()
                ->getQueueAction()
                ->hydrateAction($id_cart, $id_resource);
            if (!$create_queue['result']) {
                $logger->addLog(
                    'Error: Queue cannot be created for Cart ID: ' . $id_cart,
                    'error'
                );
            }
            if ($create_queue['exists']) {
                $logger->addLog('Queue already exists for Cart ID: ' . $id_cart);
            }

            $logger->addLog('Queue created successfully for Cart ID: ' . $id_cart);
        } else {
            do {
                $logger->addLog('Notification: Attempting to set lock for Cart ID: ' . $id_cart);
                $cart_lock = $this->dependencies
                    ->payplugLock
                    ->createLockG2($id_cart, 'ipn');
                if (!$cart_lock) {
                    $time = new \DateTime('now');
                    if ($time > $end_of_life) {
                        $logger->addLog(
                            'Try to create lock during ' . $duration . ' sec, but can\'t proceed',
                            'error'
                        );

                        return false;
                    }
                } else {
                    $logger->addLog('Lock created', 'notice');
                }
            } while (!$cart_lock);
        }
    }
}
