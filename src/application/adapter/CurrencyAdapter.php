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

use PayPlug\src\interfaces\CurrencyInterface;

class CurrencyAdapter implements CurrencyInterface
{
    private $currencies;
    private $has_eur_currency;

    public function get($idCurrency = false)
    {
        if (!is_int($idCurrency)) {
            $idCurrency = false;
        }

        return new \Currency($idCurrency);
    }

    /**
     * @description Result is cached per instance (this adapter is only ever called with its
     * default arguments in this codebase), to avoid re-querying the database every time
     * several consumers need the currency list within a single request.
     *
     * @param mixed $active
     * @param mixed $groupBy
     * @param mixed $currentShopOnly
     */
    public function findAll($active = true, $groupBy = false, $currentShopOnly = true)
    {
        if (null === $this->currencies) {
            $this->currencies = \Currency::findAll($active, $groupBy, $currentShopOnly);
        }

        return $this->currencies;
    }

    /**
     * @description Whether the shop has EUR among its active currencies
     *
     * @return bool
     */
    public function hasEurCurrency()
    {
        if (null === $this->has_eur_currency) {
            $this->has_eur_currency = false;
            foreach ($this->findAll() as $currency) {
                if ('EUR' == $currency['iso_code']) {
                    $this->has_eur_currency = true;

                    break;
                }
            }
        }

        return $this->has_eur_currency;
    }

    public function getCurrency($idCurrency)
    {
        return new \Currency($idCurrency);
    }

    public function getIdByIsoCode($isoCode)
    {
        return \Currency::getIdByIsoCode($isoCode);
    }
}
