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

namespace PayPlug\src\models\classes\Oney;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @description Value object for the 4 Oney business transaction codes of a single
 * country, as returned by GET /account (oney.countries_metadata.{ISO}.oney_business_codes).
 */
final class OneyBusinessCodes
{
    /** @var string|null */
    private $x3WithFees;

    /** @var string|null */
    private $x4WithFees;

    /** @var string|null */
    private $x3WithoutFees;

    /** @var string|null */
    private $x4WithoutFees;

    /**
     * @param string|null $x3WithFees
     * @param string|null $x4WithFees
     * @param string|null $x3WithoutFees
     * @param string|null $x4WithoutFees
     */
    private function __construct(
        $x3WithFees,
        $x4WithFees,
        $x3WithoutFees,
        $x4WithoutFees
    ) {
        $this->x3WithFees = $x3WithFees;
        $this->x4WithFees = $x4WithFees;
        $this->x3WithoutFees = $x3WithoutFees;
        $this->x4WithoutFees = $x4WithoutFees;
    }

    /**
     * @param array<string, mixed> $data raw `oney_business_codes` object from GET /account
     *                                   (keys: x3_with_fees, x4_with_fees, x3_without_fees, x4_without_fees)
     *
     * @return self
     */
    public static function fromArray(array $data)
    {
        return new self(
            self::stringOrNull($data, 'x3_with_fees'),
            self::stringOrNull($data, 'x4_with_fees'),
            self::stringOrNull($data, 'x3_without_fees'),
            self::stringOrNull($data, 'x4_without_fees')
        );
    }

    /**
     * @description Single code for a given operation and fee mode, used as the checkout
     * `business_transaction_code` (singular).
     *
     * @param string $operation 'x3' or 'x4'
     * @param bool $withFees
     *
     * @return string|null
     */
    public function get($operation, $withFees)
    {
        if ('x3' === $operation) {
            return $withFees ? $this->x3WithFees : $this->x3WithoutFees;
        }

        if ('x4' === $operation) {
            return $withFees ? $this->x4WithFees : $this->x4WithoutFees;
        }

        return null;
    }

    /**
     * @description All known codes (charged and free), used as the `business_transaction_codes`
     * (plural) list for the PDP/cart simulation pop-in.
     *
     * @return string[]
     */
    public function toList()
    {
        return array_values(array_filter([
            $this->x3WithFees,
            $this->x4WithFees,
            $this->x3WithoutFees,
            $this->x4WithoutFees,
        ]));
    }

    /**
     * @param array<string, mixed> $data
     * @param string $key
     *
     * @return string|null
     */
    private static function stringOrNull(array $data, $key)
    {
        if (!isset($data[$key]) || '' === $data[$key]) {
            return null;
        }

        return (string) $data[$key];
    }
}
