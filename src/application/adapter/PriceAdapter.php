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

namespace PayPlug\src\application\adapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\src\interfaces\PriceInterface;

class PriceAdapter implements PriceInterface
{
    public function formatPrice(float $price, ?string $iso_code = ''): string
    {
        $context = \Context::getContext();

        try {
            if (isset($context->currentLocale)) {
                $price_formated = $context->currentLocale->formatPrice($price, $iso_code);
            } else {
                $currency_id = $iso_code ? \Currency::getIdByIsoCode($iso_code) : null;
                $price_formated = \Tools::displayPrice($price, $currency_id ?: null);
            }
        } catch (\Exception $e) {
            $price_formated = '';
        }

        return $price_formated;
    }
}
