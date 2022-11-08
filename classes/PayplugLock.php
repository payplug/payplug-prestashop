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

namespace PayPlug\classes;

use DateInterval;
use DateTime;
use PrestaShopDatabaseException;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PayplugLock
{
    private $dependencies;
    private $constant;
    private $query;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;

        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->query = $this->dependencies->getPlugin()->getQuery();
    }

    /**
     * Check
     *
     * @param int   $id_cart
     * @param int   $loop_time
     * @param mixed $process
     */
    public function check($id_cart, $loop_time = 1, $process = 'none')
    {
        // Set delay
        $delay = new DateInterval('PT10S');
        $lifetime = new DateInterval('PT20S');

        // Check if lock exists
        $lock_exists = $this->existsLockG2($id_cart);
        if ($lock_exists) {
            // Then define the expiration
            $last_update = new DateTime($lock_exists['date_upd']);
            $last_check = $last_update->add($delay);
            $creation_date = new DateTime($lock_exists['date_add']);
            $end_of_life = $creation_date->add($lifetime);
            $time = new DateTime('now');

            while (($this->existsLockG2($id_cart) !== false) && ($time < $last_check)) {
                if (function_exists('usleep')) {
                    usleep($loop_time * 1000000);
                } else {
                    $this->usleep($loop_time * 1000);
                }

                // If lock take too much time, end the process
                if ($time > $end_of_life) {
                    if ($process == 'validation') {
                        $this->deleteLockG2($id_cart);
                    } else {
                        return 'stop ipn';
                    }
                }

                $time = new DateTime('now');
            }
        }
    }

    //TODO: check multishop si cart_id identiques ou uniques
    public function createLockG2($id_cart, $process_print = 'none')
    {
        // check if has lock
        $lock_exists = $this->existsLockG2($id_cart);
        if ($lock_exists) {
            $lifetime = new DateInterval('PT2M');
            $date_limit = new DateTime('now');
            $date_limit->sub($lifetime);
            $date_add = new DateTime($lock_exists['date_add']);
            if ($date_limit > $date_add) {
                $this->deleteLockG2($id_cart);
            }
        }

        // prevent exeception if _PS_DEBUG_SQL_ is true and there is a active lock
        try {
            $req_lock = $this->query
                ->insert()
                ->into($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_lock')
                ->fields('id_cart')->values((int) $id_cart)
                ->fields('id_order')->values(pSQL($process_print))
                ->fields('date_add')->values(date('Y-m-d H:i:s'))
                ->fields('date_upd')->values(date('Y-m-d H:i:s'))
                ->build()
            ;
        } catch (PrestaShopDatabaseException $e) {
            $req_lock = false;
        } catch (Exception $e) {
            $req_lock = false;
        }
        if (!$req_lock) {
            return false;
        }
        $lock = $this->existsLockG2($id_cart);
        if (!$lock) {
            return false;
        }

        return $lock['id_order'];
    }

    public function deleteLockG2($id_cart)
    {
        $req_lock = $this->query
            ->delete()
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_lock')
            ->where('`id_cart` = ' . (int) $id_cart)
            ->build()
        ;

        if (!$req_lock) {
            return false;
        }

        return true;
    }

    public function existsLockG2($id_cart)
    {
        $req_lock = $this->query
            ->select()
            ->fields('*')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_lock')
            ->where('id_cart = ' . (int) $id_cart)
            ->build('unique_row')
        ;

        if (!$req_lock) {
            return false;
        }

        return $req_lock;
    }

    /**
     * Sleep time
     *
     * @param int $seconds
     */
    private function usleep($seconds)
    {
        $start = microtime();

        do {
            // Wait !
            $current = microtime();
        } while (($current - $start) < $seconds);
    }
}
