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

namespace PayPlug\src\unified\helpers;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Maps a Unified API operation, as returned by getOperation(), to the card and 3-D Secure details
 * shown for its payment.
 *
 * Only ever called with a getOperation() response the module fetched itself, never with a webhook
 * body. A field absent, empty or of an unexpected type maps to null.
 */
final class UnifiedPaymentDetails
{
    public const TDS_YES = 'yes';
    public const TDS_NO = 'no';

    private const AUTHENTICATED = 'OK';

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * @param mixed $operation decoded getOperation() response
     *
     * @return array{brand: string|null, last4: string|null, exp_month: string|null, exp_year: string|null, tds: string|null}
     */
    public static function fromOperation($operation)
    {
        $payment_method = self::objectAt($operation, 'paymentMethod');
        $card = self::objectAt($payment_method, 'card');
        $details = self::objectAt($payment_method, 'details');

        $brand = self::stringAt($card, 'type');
        $expiry = self::expiry(self::stringAt($details, 'validityDate'));

        return [
            'brand' => null !== $brand ? $brand : self::stringAt($details, 'selectedBrand'),
            'last4' => self::lastFour(self::stringAt($card, 'code6x4')),
            'exp_month' => $expiry['month'],
            'exp_year' => $expiry['year'],
            'tds' => self::threeDs(self::stringAt(self::objectAt($operation, 'authentication'), 'globalStatus')),
        ];
    }

    /**
     * @param mixed $data
     * @param string $key
     *
     * @return array
     */
    private static function objectAt($data, $key)
    {
        return is_array($data) && isset($data[$key]) && is_array($data[$key]) ? $data[$key] : [];
    }

    /**
     * @param string $key
     *
     * @return string|null the trimmed value, null when absent, empty or not a string
     */
    private static function stringAt(array $data, $key)
    {
        $value = isset($data[$key]) && is_string($data[$key]) ? trim($data[$key]) : '';

        return '' !== $value ? $value : null;
    }

    /**
     * @param string|null $code6x4 masked card number
     *
     * @return string|null
     */
    private static function lastFour($code6x4)
    {
        return 1 === preg_match('/(\d{4})$/', (string) $code6x4, $matches) ? $matches[1] : null;
    }

    /**
     * @param string|null $validity_date YYYY-MM or MMYY
     *
     * @return array{month: string|null, year: string|null}
     */
    private static function expiry($validity_date)
    {
        $expiry = ['month' => null, 'year' => null];

        if (1 === preg_match('/^(\d{4})-(\d{2})$/', (string) $validity_date, $matches)) {
            $expiry = ['month' => $matches[2], 'year' => $matches[1]];
        } elseif (1 === preg_match('/^(\d{2})(\d{2})$/', (string) $validity_date, $matches)) {
            $expiry = ['month' => $matches[1], 'year' => '20' . $matches[2]];
        }

        $month = (int) $expiry['month'];

        return $month >= 1 && $month <= 12 ? $expiry : ['month' => null, 'year' => null];
    }

    /**
     * @param string|null $global_status authentication.globalStatus
     *
     * @return string|null null when the operation carries no authentication status
     */
    private static function threeDs($global_status)
    {
        if (null === $global_status) {
            return null;
        }

        return self::AUTHENTICATED === $global_status ? self::TDS_YES : self::TDS_NO;
    }
}
