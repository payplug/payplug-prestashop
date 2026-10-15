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

use PayPlug\classes\DependenciesClass;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Per-cart mutual exclusion around order creation, based on MySQL GET_LOCK.
 *
 * The lock belongs to the database connection: MySQL frees it when the connection
 * closes, so a crashed request cannot leave it behind. Known limits:
 * - MySQL < 5.7 and MariaDB < 10.0.2 hold one named lock per connection; taking
 *   another one elsewhere in the same request silently releases this one.
 * - The lock is local to one MySQL node (no exclusion across Galera writers).
 * - A transaction-mode connection pooler may run acquire and release on different
 *   sessions; release() then logs "Order lock not confirmed" at error level.
 */
class OrderLock
{
    const TIMEOUT = 10;

    public $dependencies;

    private $held = [];

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    /**
     * @description Wait up to TIMEOUT seconds for the order creation lock of a cart
     *
     * @param int $id_cart
     *
     * @return bool
     */
    public function acquire($id_cart = 0)
    {
        $logger = $this->dependencies
            ->getPlugin()
            ->getLogger();

        if (!is_int($id_cart) || $id_cart < 1) {
            $logger->addLog('OrderLock::acquire - Invalid argument, $id_cart must be a positive integer.', 'error');

            return false;
        }

        $name = $this->getLockName($id_cart);
        $query_adapter = $this->dependencies
            ->getPlugin()
            ->getQueryAdapter();
        $answer = $query_adapter->getNamedLock($name, self::TIMEOUT);

        if (null === $answer || false === $answer) {
            $cause = (string) $query_adapter->getLastError();
            $logger->addLog(
                'OrderLock::acquire - Order lock unavailable, order creation blocked for cart ' . $id_cart
                . ' (GET_LOCK failed' . ('' !== $cause ? ': ' . $cause : '') . ')',
                'error'
            );

            return false;
        }

        $acquired = 1 === (int) $answer;
        if ($acquired) {
            $this->held[$name] = true;
        }

        $logger->addLog('OrderLock::acquire - Lock ' . ($acquired ? 'acquired' : 'not acquired') . ' for cart ' . $id_cart, 'info');

        return $acquired;
    }

    /**
     * @description Release the order creation lock of a cart, if this instance holds it
     *
     * @param int $id_cart
     *
     * @return bool
     */
    public function release($id_cart = 0)
    {
        $name = $this->getLockName($id_cart);
        if (!isset($this->held[$name])) {
            return false;
        }
        unset($this->held[$name]);

        $released = 1 === (int) $this->dependencies
            ->getPlugin()
            ->getQueryAdapter()
            ->releaseNamedLock($name);

        if (!$released) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog(
                    'OrderLock::release - Order lock not confirmed, race protection may be inactive for cart ' . (int) $id_cart
                    . ' (RELEASE_LOCK did not return 1)',
                    'error'
                );
        }

        return $released;
    }

    /**
     * @description GET_LOCK names are server-wide, so the name is scoped to the shop database
     *
     * @param int $id_cart
     *
     * @return string
     */
    public function getLockName($id_cart = 0)
    {
        return 'payplug_order_' . substr($this->getScope(), 0, 16) . '_' . (int) $id_cart;
    }

    /**
     * @return string
     */
    protected function getScope()
    {
        return md5((defined('_DB_NAME_') ? _DB_NAME_ : '') . (defined('_DB_PREFIX_') ? _DB_PREFIX_ : ''));
    }
}
