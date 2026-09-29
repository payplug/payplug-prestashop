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

/**
 * Classifies the execCode of a Unified API refund, synchronous response or notification alike.
 *
 * Not ExecCodeMapper: it maps every code but 0000/0001 to FAILED, and a refund marked failed
 * no longer counts as refunded - so a code meaning "in progress", or one the platform adds
 * later, would make an amount that may already have left refundable a second time. Here only
 * codes the platform documents as failures decline a refund; anything unknown stays uncertain.
 *
 * Source: "EXECCODES - Codes d'exécution de la plateforme" (Confluence DP space, 2025-10-28).
 */
final class UpcRefundExecCode
{
    /** The money moved, or will once the platform settles it. */
    public const ACCEPTED = 'accepted';
    /** A documented failure: nothing moved. */
    public const DECLINED = 'declined';
    /** Not documented as either: the refund may or may not have gone through. */
    public const UNKNOWN = 'unknown';

    public const SUCCESS = '0000';

    /**
     * 0002 WAITING_PROVIDER, 0003 WAITING_STATUS, and 5004 "Timeout. The result will be sent to
     * the notification URL." - filed under technical errors, yet the outcome is still to come.
     */
    public const IN_PROGRESS = ['0002', '0003', '5004'];

    private const DOCUMENTED_FAILURES = [
        // Validation errors
        '1001', '1002', '1003', '1004', '1005', '1006', '1007',
        // Editing transaction, batch and API errors
        '2001', '2002', '2003', '2004', '2005', '2006', '2007', '2008', '2009', '2010',
        '2011', '2012', '2013', '2014', '2015', '2016', '2017', '2018', '2019', '2020',
        // Account configuration errors
        '3001', '3002', '3003', '3004', '3006', '3007',
        // Functional errors
        '4001', '4002', '4003', '4004', '4005', '4006', '4007', '4008', '4009', '4010',
        '4011', '4012', '4013', '4014', '4015', '4016', '4017', '4018', '4019', '4020', '4021',
        // Technical errors (5004 excluded, see IN_PROGRESS)
        '5001', '5002', '5003', '5005', '5006',
        // Fraud errors
        '6001', '6002', '6003', '6004', '6005', '6006', '6007', '6008',
    ];

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * @param string $exec_code
     *
     * @return string one of ACCEPTED, DECLINED, UNKNOWN
     */
    public static function classify($exec_code)
    {
        $exec_code = (string) $exec_code;

        if (self::SUCCESS === $exec_code || in_array($exec_code, self::IN_PROGRESS, true)) {
            return self::ACCEPTED;
        }

        if (in_array($exec_code, self::DOCUMENTED_FAILURES, true)) {
            return self::DECLINED;
        }

        return self::UNKNOWN;
    }
}
