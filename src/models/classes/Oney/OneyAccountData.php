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
 * @description Value object mapping the `oney` object returned by GET /account
 * (merchant_guid/business codes per country, allowed countries, min/max amounts,
 * legal notices). The official Oney widget computes the schedule itself; this
 * module only needs this data to decide eligibility and to configure the widget.
 */
final class OneyAccountData
{
    /** @var bool */
    private $enabled;

    /** @var string[] */
    private $allowedCountries;

    /** @var array<string, int> currency ISO code => amount in cents */
    private $minAmounts;

    /** @var array<string, int> currency ISO code => amount in cents */
    private $maxAmounts;

    /** @var bool */
    private $showLegalNotices;

    /**
     * @var array<string, array<string, mixed>> country ISO code => ['merchant_guid'
     *                    => string|null, 'psp_guid' => string|null, 'business_codes' => OneyBusinessCodes]
     */
    private $countriesMetadata;

    /**
     * @param bool $enabled
     * @param string[] $allowedCountries
     * @param array<string, int> $minAmounts
     * @param array<string, int> $maxAmounts
     * @param bool $showLegalNotices
     * @param array<string, array<string, mixed>> $countriesMetadata country ISO code => ['merchant_guid'
     *                                                               => string|null, 'psp_guid' => string|null, 'business_codes' => OneyBusinessCodes]
     */
    private function __construct(
        $enabled,
        array $allowedCountries,
        array $minAmounts,
        array $maxAmounts,
        $showLegalNotices,
        array $countriesMetadata
    ) {
        $this->enabled = $enabled;
        $this->allowedCountries = $allowedCountries;
        $this->minAmounts = $minAmounts;
        $this->maxAmounts = $maxAmounts;
        $this->showLegalNotices = $showLegalNotices;
        $this->countriesMetadata = $countriesMetadata;
    }

    /**
     * @param array<string, mixed> $oney raw `oney` object from GET /account. Per-country
     *                                   `merchant_guid`/`oney_business_codes`/`psp_guid` live
     *                                   under `countries_metadata.{ISO}`: a merchant's Oney
     *                                   contract (and business codes) is set up per country.
     *
     * @return self
     */
    public static function fromAccountResponse(array $oney)
    {
        $countriesMetadata = [];
        if (isset($oney['countries_metadata']) && \is_array($oney['countries_metadata'])) {
            foreach ($oney['countries_metadata'] as $countryIso => $metadata) {
                if (!\is_string($countryIso) || !\is_array($metadata)) {
                    continue;
                }

                $countriesMetadata[strtoupper($countryIso)] = [
                    'merchant_guid' => self::stringOrNull($metadata, 'merchant_guid'),
                    'psp_guid' => self::stringOrNull($metadata, 'psp_guid'),
                    'business_codes' => OneyBusinessCodes::fromArray(
                        isset($metadata['oney_business_codes']) && \is_array($metadata['oney_business_codes'])
                            ? $metadata['oney_business_codes']
                            : []
                    ),
                ];
            }
        }

        return new self(
            isset($oney['enabled']) ? (bool) $oney['enabled'] : false,
            isset($oney['allowed_countries']) && \is_array($oney['allowed_countries'])
                ? array_values($oney['allowed_countries'])
                : [],
            isset($oney['min_amounts']) && \is_array($oney['min_amounts'])
                ? $oney['min_amounts']
                : [],
            isset($oney['max_amounts']) && \is_array($oney['max_amounts'])
                ? $oney['max_amounts']
                : [],
            isset($oney['show_legal_notices']) ? (bool) $oney['show_legal_notices'] : false,
            $countriesMetadata
        );
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param string $countryIso
     *
     * @return string|null
     */
    public function getMerchantGuid($countryIso)
    {
        $countryIso = strtoupper($countryIso);

        return isset($this->countriesMetadata[$countryIso])
            ? $this->countriesMetadata[$countryIso]['merchant_guid']
            : null;
    }

    /**
     * @param string $countryIso
     *
     * @return string|null
     */
    public function getPspGuid($countryIso)
    {
        $countryIso = strtoupper($countryIso);

        return isset($this->countriesMetadata[$countryIso])
            ? $this->countriesMetadata[$countryIso]['psp_guid']
            : null;
    }

    /**
     * @param string $countryIso
     *
     * @return OneyBusinessCodes
     */
    public function getBusinessCodes($countryIso)
    {
        $countryIso = strtoupper($countryIso);

        return isset($this->countriesMetadata[$countryIso])
            ? $this->countriesMetadata[$countryIso]['business_codes']
            : OneyBusinessCodes::fromArray([]);
    }

    /**
     * @return bool
     */
    public function showLegalNotices()
    {
        return $this->showLegalNotices;
    }

    /**
     * @return string[]
     */
    public function getAllowedCountries()
    {
        return $this->allowedCountries;
    }

    /**
     * @param string $countryIso
     *
     * @return bool
     */
    public function isCountryAllowed($countryIso)
    {
        foreach ($this->allowedCountries as $allowedCountry) {
            if (0 === strcasecmp($allowedCountry, $countryIso)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $amountInCents
     * @param string $currencyIso
     *
     * @return bool
     */
    public function isAmountEligible($amountInCents, $currencyIso)
    {
        $currencyIso = strtoupper($currencyIso);

        if (!isset($this->minAmounts[$currencyIso]) || !isset($this->maxAmounts[$currencyIso])) {
            return false;
        }

        return $amountInCents >= $this->minAmounts[$currencyIso]
            && $amountInCents <= $this->maxAmounts[$currencyIso];
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
