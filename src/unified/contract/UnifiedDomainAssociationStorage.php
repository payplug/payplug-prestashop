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

namespace PayPlug\src\unified\contract;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayplugUnifiedCore\Contracts\IDomainAssociationStorage;

class UnifiedDomainAssociationStorage implements IDomainAssociationStorage
{
    const CONTENT_KEY = 'PAYPLUG_APPLE_PAY_WELL_KNOWN';
    const STATUS_KEY = 'PAYPLUG_APPLE_PAY_WELL_KNOWN_STATUS';
    const STATUS_JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description Get the stored file, null when nothing valid is stored
     *
     * The file is stored base64-encoded so that Db::escape() (strip_tags, nl2br) and HTMLPurifier
     * cannot alter it: the decoded value is exactly what writeContent() received.
     */
    public function readContent(): ?string
    {
        $stored = $this->configuration()->getGlobalValue(self::CONTENT_KEY);
        if (!is_string($stored) || '' === $stored) {
            return null;
        }

        $content = base64_decode($stored, true);

        return false === $content ? null : $content;
    }

    public function writeContent(string $content): void
    {
        if (!$this->configuration()->updateValue(self::CONTENT_KEY, base64_encode($content), 0, 0)) {
            throw new \RuntimeException('The Apple Pay domain association file could not be stored.');
        }
    }

    public function readStatus(): array
    {
        $stored = $this->configuration()->getGlobalValue(self::STATUS_KEY);
        $status = is_string($stored) ? json_decode($stored, true) : null;

        return is_array($status) ? $status : [];
    }

    public function writeStatus(array $status): void
    {
        $json = json_encode($status, self::STATUS_JSON_FLAGS);
        if (false === $json || !$this->configuration()->updateValue(self::STATUS_KEY, $json, 0, 0)) {
            throw new \RuntimeException('The Apple Pay domain association status could not be stored.');
        }
    }

    /**
     * @description Delete the stored file and its status
     *
     * @return bool
     */
    public function purge()
    {
        $content_deleted = $this->configuration()->deleteByName(self::CONTENT_KEY);
        $status_deleted = $this->configuration()->deleteByName(self::STATUS_KEY);

        return (bool) $content_deleted && (bool) $status_deleted;
    }

    private function configuration()
    {
        return $this->dependencies->getPlugin()->getConfiguration();
    }
}
