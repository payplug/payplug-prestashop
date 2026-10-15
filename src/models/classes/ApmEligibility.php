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
 * Decides whether a PPRO payment method can be offered for a cart amount and a billing
 * country. The API configuration (`amounts` and `countries`, cached from GET /account)
 * sets the outer limits; the merchant settings (`apm_custom_settings`) can only narrow
 * them. Amounts are in cents.
 *
 * Merchant settings shape: `{"<apm>":{"min":{"EUR":500},"max":{"EUR":20000},"countries":["IT"]}}`.
 * A missing field means "use the API value"; an empty `countries` list means "no country".
 */
class ApmEligibility
{
    const PPRO_METHODS = ['satispay', 'bancontact', 'ideal', 'mybank', 'wero', 'bizum', 'scalapay'];

    const DEFAULT_CURRENCY = 'EUR';

    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isPpro($name = '')
    {
        return is_string($name) && in_array($name, self::PPRO_METHODS, true);
    }

    /**
     * @description Get the limits the API authorizes for a payment method.
     *
     * @param string $name
     * @param string $currency
     *
     * @return array `configured` is false when the cache holds nothing for the method,
     *               `currency_supported` is false when its bounds exclude the currency,
     *               a null bound or null `countries` means no restriction
     */
    public function getApiLimits($name = '', $currency = '')
    {
        $amounts = $this->getJsonConfiguration('amounts');
        $countries = $this->getJsonConfiguration('countries');
        $entry = isset($amounts[$name]) && is_array($amounts[$name]) ? $amounts[$name] : null;
        $default = isset($amounts['default']) && is_array($amounts['default']) ? $amounts['default'] : [];

        $limits = [
            'configured' => null !== $entry,
            'currency_supported' => true,
            'min' => null,
            'max' => null,
            'countries' => isset($countries[$name]) && is_array($countries[$name]) ? array_values($countries[$name]) : null,
        ];
        if (null === $entry) {
            return $limits;
        }

        foreach (['min', 'max'] as $bound) {
            $limit = isset($entry[$bound]) ? $entry[$bound] : (isset($default[$bound]) ? $default[$bound] : '');
            if ('' === $limit) {
                continue;
            }
            $limits[$bound] = $this->getAmountHelper()->getAmountForCurrency($limit, $currency);
            if (null === $limits[$bound]) {
                $limits['currency_supported'] = false;
            }
        }

        return $limits;
    }

    /**
     * @description Get the merchant settings of a payment method for a currency.
     *
     * @param string $name
     * @param string $currency
     *
     * @return array ['min' => null|int, 'max' => null|int, 'countries' => null|string[]]
     */
    public function getMerchantSettings($name = '', $currency = '')
    {
        $entry = $this->getStoredMerchantSettings($name);

        return [
            'min' => $this->getMerchantAmount($entry, 'min', $currency),
            'max' => $this->getMerchantAmount($entry, 'max', $currency),
            'countries' => isset($entry['countries']) && is_array($entry['countries']) ? array_values($entry['countries']) : null,
        ];
    }

    /**
     * @description Get the limits applied at checkout: the merchant settings narrowed
     *              against the API limits, never widened.
     *
     * @param string $name
     * @param string $currency
     *
     * @return array ['min' => null|int, 'max' => null|int, 'countries' => null|string[], 'valid' => bool]
     */
    public function getEffectiveLimits($name = '', $currency = '')
    {
        $api = $this->getApiLimits($name, $currency);
        $merchant = $this->getMerchantSettings($name, $currency);

        $min = $api['min'];
        if (null !== $merchant['min'] && (null === $min || $merchant['min'] > $min)) {
            $min = $merchant['min'];
        }
        $max = $api['max'];
        if (null !== $merchant['max'] && (null === $max || $merchant['max'] < $max)) {
            $max = $merchant['max'];
        }

        if (null === $merchant['countries']) {
            $countries = $api['countries'];
        } elseif (null === $api['countries']) {
            $countries = $merchant['countries'];
        } else {
            $countries = array_values(array_intersect($merchant['countries'], $api['countries']));
        }

        return [
            'min' => $min,
            'max' => $max,
            'countries' => $countries,
            'valid' => null === $min || null === $max || $min <= $max,
        ];
    }

    /**
     * @description Check whether a payment method can be offered.
     *
     * @param string $name
     * @param int $cents cart amount in cents
     * @param string $currency
     * @param string $billing_iso billing country ISO code, empty when there is no billing address yet
     *
     * @return array ['result' => bool, 'reason' => 'ok|amount_invalid|amount_low|amount_high|country|currency|no_address|no_config|invalid_settings']
     */
    public function isEligible($name = '', $cents = 0, $currency = '', $billing_iso = '')
    {
        if (!$this->isPpro($name)) {
            return $this->eligibility(true, 'ok');
        }
        if (!is_int($cents)) {
            return $this->eligibility(false, 'amount_invalid');
        }

        $api = $this->getApiLimits($name, $currency);
        if (!$api['configured']) {
            return $this->eligibility(false, 'no_config');
        }
        if (!$api['currency_supported']) {
            return $this->eligibility(false, 'currency');
        }

        $limits = $this->getEffectiveLimits($name, $currency);
        if (!$limits['valid']) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('ApmEligibility::isEligible: ' . $name . ' minimum amount is above its maximum amount.', 'warning');

            return $this->eligibility(false, 'invalid_settings');
        }

        $billing_iso = is_string($billing_iso) ? strtoupper($billing_iso) : '';
        if ('' === $billing_iso) {
            return $this->eligibility(false, 'no_address');
        }
        if (null !== $limits['min'] && $cents < $limits['min']) {
            return $this->eligibility(false, 'amount_low');
        }
        if (null !== $limits['max'] && $cents > $limits['max']) {
            return $this->eligibility(false, 'amount_high');
        }
        if (null !== $limits['countries'] && !in_array($billing_iso, $limits['countries'], true)) {
            return $this->eligibility(false, 'country');
        }

        return $this->eligibility(true, 'ok');
    }

    /**
     * @description Get the currency the back office edits the amounts in: the first
     *              currency of the API bounds of the payment method.
     *
     * @param string $name
     *
     * @return string
     */
    public function getBackOfficeCurrency($name = '')
    {
        $amounts = $this->getJsonConfiguration('amounts');
        foreach (['min', 'max'] as $bound) {
            if (isset($amounts[$name][$bound]) && is_string($amounts[$name][$bound])
                && preg_match('/^([A-Z]{3}):/', $amounts[$name][$bound], $matches)) {
                return $matches[1];
            }
        }

        return self::DEFAULT_CURRENCY;
    }

    /**
     * @description Get the countries the merchant can choose from for a payment method:
     *              the countries the API allows or, when the API sets no restriction, the
     *              active countries of the shop.
     *
     * @param string $name
     * @param int $id_lang
     *
     * @return array country names indexed by ISO code
     */
    public function getSelectableCountries($name = '', $id_lang = 0)
    {
        if (!$this->isPpro($name)) {
            return [];
        }

        $country_adapter = $this->dependencies->getPlugin()->getCountry();
        $api = $this->getApiLimits($name, '');
        $countries = [];

        if (null !== $api['countries']) {
            foreach ($api['countries'] as $iso_code) {
                $id_country = (int) $country_adapter->getByIso($iso_code);
                $country_name = $id_country ? $country_adapter->getNameById((int) $id_lang, $id_country) : '';
                $countries[$iso_code] = $country_name ? (string) $country_name : $iso_code;
            }

            return $countries;
        }

        $shop_countries = $country_adapter->getCountries((int) $id_lang, true);
        if (!is_array($shop_countries)) {
            return [];
        }
        foreach ($shop_countries as $shop_country) {
            if (isset($shop_country['iso_code'], $shop_country['name'])) {
                $countries[strtoupper($shop_country['iso_code'])] = (string) $shop_country['name'];
            }
        }

        return $countries;
    }

    /**
     * @description Validate the changes the merchant submits for a payment method and
     *              return the settings to store once merged with the stored ones. Values
     *              equal to the API ones are not stored, so later API changes still apply.
     *
     * @param string $name
     * @param array $changes submitted fields only: `min`/`max` in cents of the back office
     *                       currency (null resets the bound), `countries` as ISO codes
     * @param int $id_lang
     *
     * @return array ['result' => bool, 'code' => 'ok|unknown_method|no_config|amount_invalid|amount_out_of_range|max_not_above_min|country_not_allowed', 'settings' => array]
     */
    public function validateMerchantSettings($name = '', $changes = [], $id_lang = 0)
    {
        if (!$this->isPpro($name) || !is_array($changes)) {
            return $this->validation(false, 'unknown_method');
        }

        $currency = $this->getBackOfficeCurrency($name);
        $api = $this->getApiLimits($name, $currency);
        if (!$api['configured']) {
            return $this->validation(false, 'no_config');
        }

        $settings = $this->getStoredMerchantSettings($name);

        foreach (['min', 'max'] as $bound) {
            if (!array_key_exists($bound, $changes)) {
                continue;
            }
            $amount = $changes[$bound];
            if (null === $amount) {
                unset($settings[$bound]);

                continue;
            }
            if (!is_int($amount) || $amount < 0) {
                return $this->validation(false, 'amount_invalid');
            }
            if ((null !== $api['min'] && $amount < $api['min']) || (null !== $api['max'] && $amount > $api['max'])) {
                return $this->validation(false, 'amount_out_of_range');
            }
            if ($amount === $api[$bound]) {
                unset($settings[$bound]);
            } else {
                $settings[$bound] = [$currency => $amount];
            }
        }

        if (isset($settings['min']) || isset($settings['max'])) {
            $min = $this->getMerchantAmount($settings, 'min', $currency);
            $max = $this->getMerchantAmount($settings, 'max', $currency);
            $min = null !== $min ? $min : $api['min'];
            $max = null !== $max ? $max : $api['max'];
            if (null !== $min && null !== $max && $max <= $min) {
                return $this->validation(false, 'max_not_above_min');
            }
        }

        if (array_key_exists('countries', $changes)) {
            if (!is_array($changes['countries'])) {
                return $this->validation(false, 'country_not_allowed');
            }
            $selectable = array_keys($this->getSelectableCountries($name, $id_lang));
            $countries = [];
            foreach ($changes['countries'] as $iso_code) {
                $iso_code = is_string($iso_code) ? strtoupper(trim($iso_code)) : '';
                if (!in_array($iso_code, $selectable, true)) {
                    return $this->validation(false, 'country_not_allowed');
                }
                if (!in_array($iso_code, $countries, true)) {
                    $countries[] = $iso_code;
                }
            }

            if (!empty($countries) && empty(array_diff($selectable, $countries))) {
                unset($settings['countries']);
            } else {
                $settings['countries'] = $countries;
            }
        }

        $result = $this->validation(true, 'ok');
        $result['settings'] = [];
        foreach (['min', 'max', 'countries'] as $field) {
            if (isset($settings[$field])) {
                $result['settings'][$field] = $settings[$field];
            }
        }

        return $result;
    }

    /**
     * @description Store the merchant settings of the given payment methods in one write.
     *
     * @param array $settings_by_name settings returned by validateMerchantSettings(), by payment method
     *
     * @return bool
     */
    public function saveMerchantSettings($settings_by_name = [])
    {
        if (!is_array($settings_by_name)) {
            return false;
        }

        $stored = $this->getJsonConfiguration('apm_custom_settings');
        foreach ($settings_by_name as $name => $settings) {
            if (!$this->isPpro($name)) {
                continue;
            }
            if (empty($settings)) {
                unset($stored[$name]);
            } else {
                $stored[$name] = $settings;
            }
        }
        ksort($stored);

        return (bool) $this->dependencies
            ->getPlugin()
            ->getConfigurationClass()
            ->set('apm_custom_settings', empty($stored) ? '{}' : json_encode($stored));
    }

    /**
     * @description Copy the Scalapay amounts stored in `scalapay_custom_min_amounts` and
     *              `scalapay_custom_max_amounts` (`ISO:cents`) into the merchant settings.
     *              A bound already in the merchant settings is kept, a bound equal to the
     *              API one is not stored, and the source keys are left untouched.
     *
     * @return bool false when the merchant settings could not be written
     */
    public function importLegacyScalapaySettings()
    {
        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $stored = $this->getJsonConfiguration('apm_custom_settings');
        $entry = isset($stored['scalapay']) && is_array($stored['scalapay']) ? $stored['scalapay'] : [];
        $imported = false;

        foreach (['min', 'max'] as $bound) {
            $legacy = $configuration->getValue('scalapay_custom_' . $bound . '_amounts');
            if (isset($entry[$bound]) || !is_string($legacy) || !preg_match('/^([A-Z]{3}):(\d+)$/', $legacy, $matches)) {
                continue;
            }

            $currency = $matches[1];
            $amount = (int) $matches[2];
            if ($amount === $this->getApiLimits('scalapay', $currency)[$bound]) {
                continue;
            }

            $entry[$bound] = [$currency => $amount];
            $imported = true;
        }

        if (!$imported) {
            return true;
        }

        $stored['scalapay'] = $entry;
        ksort($stored);

        return (bool) $configuration->set('apm_custom_settings', json_encode($stored));
    }

    /**
     * @description Restrict the Bancontact billing countries to Belgium in the merchant
     *              settings when the `bancontact_country` setting is enabled. Countries
     *              already in the merchant settings are kept, and the source key is left
     *              untouched.
     *
     * @return bool false when the merchant settings could not be written
     */
    public function importLegacyBancontactCountry()
    {
        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        if (!(bool) $configuration->getValue('bancontact_country')) {
            return true;
        }

        $stored = $this->getJsonConfiguration('apm_custom_settings');
        $entry = isset($stored['bancontact']) && is_array($stored['bancontact']) ? $stored['bancontact'] : [];
        if (array_key_exists('countries', $entry)) {
            return true;
        }

        $entry['countries'] = ['BE'];
        $stored['bancontact'] = $entry;
        ksort($stored);

        return (bool) $configuration->set('apm_custom_settings', json_encode($stored));
    }

    /**
     * @param string $key
     *
     * @return array
     */
    private function getJsonConfiguration($key)
    {
        $value = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass()
            ->getValue($key);
        $decoded = is_string($value) ? json_decode($value, true) : null;

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param string $name
     *
     * @return array
     */
    private function getStoredMerchantSettings($name)
    {
        $settings = $this->getJsonConfiguration('apm_custom_settings');

        return isset($settings[$name]) && is_array($settings[$name]) ? $settings[$name] : [];
    }

    /**
     * @param array $entry
     * @param string $bound
     * @param string $currency
     *
     * @return int|null
     */
    private function getMerchantAmount($entry, $bound, $currency)
    {
        if (!isset($entry[$bound][$currency]) || !is_int($entry[$bound][$currency])) {
            return null;
        }

        return $entry[$bound][$currency];
    }

    /**
     * @return \PayPlug\src\utilities\helpers\AmountHelper
     */
    private function getAmountHelper()
    {
        return $this->dependencies->getHelpers()['amount'];
    }

    /**
     * @param bool $result
     * @param string $reason
     *
     * @return array
     */
    private function eligibility($result, $reason)
    {
        return [
            'result' => $result,
            'reason' => $reason,
        ];
    }

    /**
     * @param bool $result
     * @param string $code
     *
     * @return array
     */
    private function validation($result, $code)
    {
        return [
            'result' => $result,
            'code' => $code,
        ];
    }
}
