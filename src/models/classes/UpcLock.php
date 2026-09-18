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

use PayPlug\src\utilities\traits\ServiceGetter;
use PayplugUnifiedCore\Contracts\ILock;

class UpcLock implements ILock
{
    use ServiceGetter;

    private const REPOSITORY_SERVICE = 'payplug.models.repositories.upc_lock';

    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    public function acquire(string $key, int $ttlSeconds): bool
    {
        $repository = $this->getService(self::REPOSITORY_SERVICE);
        $expires_at = time() + $ttlSeconds;

        // Insert-first: a UNIQUE violation on lock_key makes createEntity() return 0
        // (EntityRepository::build() catches the exception), never throws.
        if ($repository->createEntity(['lock_key' => $key, 'expires_at' => $expires_at])) {
            return true;
        }

        $row = $repository->getBy('lock_key', $key);
        if (!$row) {
            // Lost a race to a concurrent acquire/steal between our failed INSERT and
            // this re-read; refuse rather than retry.
            return false;
        }

        if ((int) $row['expires_at'] >= time()) {
            return false;
        }

        // Expired: steal it via UPDATE. Narrow residual race: two concurrent stealers
        // can both succeed here, same accepted trade-off as this codebase's existing
        // PayplugLock precedent.
        return (bool) $repository->updateEntity((int) $row['id_payplug_upc_lock'], ['expires_at' => $expires_at]);
    }

    public function release(string $key): void
    {
        $this->getService(self::REPOSITORY_SERVICE)->deleteBy('lock_key', $key);
    }
}
