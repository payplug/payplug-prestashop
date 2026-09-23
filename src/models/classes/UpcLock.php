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

    /**
     * The expires_at value this instance itself set the last time it acquired/stole a given key -
     * release() only deletes the row while it still carries this exact value, so releasing after
     * the row has since been stolen by someone else (this instance's TTL ran out) can't delete
     * that new owner's lock out from under them.
     *
     * @var array<string, int>
     */
    private $ownedExpirations = [];

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
            $this->ownedExpirations[$key] = $expires_at;

            return true;
        }

        // Expired-lock steal, atomic at the SQL level: UPDATE ... WHERE lock_key = :key AND
        // expires_at < :now, re-checking the expiry in the same statement instead of a prior
        // SELECT. Only the caller whose UPDATE actually affects a row (checked via
        // Db::Affected_Rows(), see UpcLockRepository::stealExpired()) wins the race - two
        // concurrent stealers targeting the same expired row can no longer both succeed. A
        // non-expired lock, or a lock_key that no longer exists at all (e.g. lost a race to a
        // concurrent acquire/steal between our failed INSERT and this steal attempt), both
        // simply affect zero rows and fall through to `return false` below.
        if (!$repository->stealExpired($key, $expires_at)) {
            return false;
        }

        $this->ownedExpirations[$key] = $expires_at;

        return true;
    }

    public function release(string $key): void
    {
        if (!isset($this->ownedExpirations[$key])) {
            return;
        }

        $repository = $this->getService(self::REPOSITORY_SERVICE);
        $row = $repository->getBy('lock_key', $key);

        if ($row && (int) $row['expires_at'] === $this->ownedExpirations[$key]) {
            $repository->deleteBy('lock_key', $key);
        }

        unset($this->ownedExpirations[$key]);
    }
}
