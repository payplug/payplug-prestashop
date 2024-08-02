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

namespace PayPlug\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PayplugLock
{
    private $dependencies;
    private $validators;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->validators = $this->dependencies->getValidators();
    }

    /**
     * Check.
     *
     * @param int $id_cart
     * @param int $loop_time
     * @param mixed $process
     */
    public function check($id_cart, $loop_time = 1, $process = 'none')
    {
        // Set delay
        $delay = new \DateInterval('PT10S');
        $lifetime = new \DateInterval('PT20S');

        // Check if lock exists
        $lock_exists = $this->dependencies
            ->getPlugin()
            ->getLockRepository()
            ->getBy('id_cart', (int) $id_cart);

        if ($lock_exists) {
            // Then define the expiration
            $last_update = new \DateTime($lock_exists['date_upd']);
            $last_check = $last_update->add($delay);
            $creation_date = new \DateTime($lock_exists['date_add']);
            $end_of_life = $creation_date->add($lifetime);
            $time = new \DateTime('now');

            $lock_repository = $this->dependencies->getPlugin()->getLockRepository();
            while (!empty($lock_repository->getBy('id_cart', (int) $id_cart)) && ($time < $last_check)) {
                sleep((int) $loop_time);

                // If lock take too much time, end the process
                if ($time > $end_of_life) {
                    if ('validation' == $process) {
                        $this->dependencies
                            ->getPlugin()
                            ->getLockRepository()
                            ->deleteBy('id_cart', (int) $id_cart);
                    } else {
                        return 'stop ipn';
                    }
                }

                $time = new \DateTime('now');
            }
        }
    }

    // todo: check multishop si cart_id identiques ou uniques
    public function createLockG2($id_cart, $process_print = 'none')
    {
        // check if has lock
        $lock_exists = $this->dependencies
            ->getPlugin()
            ->getLockRepository()
            ->getBy('id_cart', (int) $id_cart);

        if (!empty($lock_exists)) {
            $date_add = new \DateTime($lock_exists['date_add']);
            if ($this->validators['lock']->isExpired($date_add)['result']) {
                $this->dependencies
                    ->getPlugin()
                    ->getLockRepository()
                    ->deleteBy('id_cart', (int) $id_cart);
            }
        }

        // prevent exeception if _PS_DEBUG_SQL_ is true and there is a active lock
        $parameters = [
            'id_cart' => (int) $id_cart,
            'id_order' => $process_print,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        ];
        $req_lock = $this->dependencies
            ->getPlugin()
            ->getLockRepository()
            ->createLock($parameters);
        if (!$req_lock) {
            return false;
        }

        $lock = $this->dependencies
            ->getPlugin()
            ->getLockRepository()
            ->getBy('id_cart', (int) $id_cart);
        if (empty($lock)) {
            return false;
        }

        return $lock['id_order'];
    }
}
