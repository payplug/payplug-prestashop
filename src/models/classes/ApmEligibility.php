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

    const BRAND_NAMES = [
        'satispay' => 'Satispay',
        'bancontact' => 'Bancontact',
        'ideal' => 'iDEAL',
        'mybank' => 'MyBank',
        'wero' => 'Wero',
        'bizum' => 'Bizum',
        'scalapay' => 'Scalapay',
    ];

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
     * @param string $name
     *
     * @return string the brand name of a PPRO payment method, empty for any other method
     */
    public function getBrandName($name = '')
    {
        return $this->isPpro($name) ? self::BRAND_NAMES[$name] : '';
    }

    /**
     * @description Get the limits the API authorizes for a payment method.
     *
     * @param string $name
     * @param string $currency
     * @param int|null $id_shop_group null: current context
     * @param int|null $id_shop null: current context
     *
     * @return array `configured` is false when the cache holds nothing for the method,
     *               `currency_supported` is false when its bounds exclude the currency,
     *               a null bound or null `countries` means no restriction
     */
    public function getApiLimits($name = '', $currency = '', $id_shop_group = null, $id_shop = null)
    {
        $amounts = $this->getJsonConfiguration('amounts', $id_shop_group, $id_shop);
        $countries = $this->getJsonConfiguration('countries', $id_shop_group, $id_shop);
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
     * @description Get the enabled payment methods whose minimum amount is above their
     *              maximum amount in the back office currency, as they are not offered at
     *              checkout.
     *
     * @return array ['<name>' => ['min' => int, 'max' => int, 'currency' => string]]
     */
    public function getInvalidSettings()
    {
        $payment_methods = $this->getJsonConfiguration('payment_methods');
        $invalid = [];
        foreach (self::PPRO_METHODS as $name) {
            if (empty($payment_methods[$name])) {
                continue;
            }

            $currency = $this->getBackOfficeCurrency($name);
            $api = $this->getApiLimits($name, $currency);
            if (!$api['configured'] || !$api['currency_supported']) {
                continue;
            }

            $limits = $this->getEffectiveLimits($name, $currency);
            if (!$limits['valid']) {
                $invalid[$name] = [
                    'min' => $limits['min'],
                    'max' => $limits['max'],
                    'currency' => $currency,
                ];
            }
        }

        return $invalid;
    }

    /**
     * @description Log a warning for each payment method invalid now but not in the
     *              previous result of getInvalidSettings().
     *
     * @param array $previous
     *
     * @return string[] names of the payment methods logged
     */
    public function logNewlyInvalidSettings($previous = [])
    {
        $previous = is_array($previous) ? $previous : [];
        $names = [];
        foreach ($this->getInvalidSettings() as $name => $settings) {
            if (isset($previous[$name])) {
                continue;
            }

            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog(
                    'ApmEligibility: ' . $name . ' min amount ' . $settings['min'] . ' is above max amount '
                    . $settings['max'] . ' (' . $settings['currency'] . ' cents) with the new account limits, method hidden at checkout',
                    'warning'
                );
            $names[] = $name;
        }

        return $names;
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
     *              the active countries of the shop the API allows, every active country
     *              of the shop when the API sets no restriction.
     *
     * @param string $name
     * @param int $id_lang
     *
     * @return array country names indexed by ISO code, in the order of the shop countries
     */
    public function getSelectableCountries($name = '', $id_lang = 0)
    {
        if (!$this->isPpro($name)) {
            return [];
        }

        $shop_countries = $this->dependencies
            ->getPlugin()
            ->getCountry()
            ->getCountries((int) $id_lang, true);
        if (!is_array($shop_countries)) {
            return [];
        }

        $api_countries = $this->getApiLimits($name, '')['countries'];
        $countries = [];
        foreach ($shop_countries as $shop_country) {
            if (!isset($shop_country['iso_code'], $shop_country['name'])) {
                continue;
            }
            $iso_code = strtoupper($shop_country['iso_code']);
            if (null === $api_countries || in_array($iso_code, $api_countries, true)) {
                $countries[$iso_code] = (string) $shop_country['name'];
            }
        }

        return $countries;
    }

    /**
     * @description Get the names of countries in a language, the ISO code standing for
     *              a country the shop does not know or has no name for.
     *
     * @param string[] $iso_codes
     * @param int $id_lang
     *
     * @return string[] names in the order of the ISO codes
     */
    public function getCountryNames($iso_codes = [], $id_lang = 0)
    {
        if (!is_array($iso_codes)) {
            return [];
        }

        $country_adapter = $this->dependencies
            ->getPlugin()
            ->getCountry();
        $names = [];
        foreach ($iso_codes as $iso_code) {
            if (!is_string($iso_code) || '' === $iso_code) {
                continue;
            }
            $id_country = (int) $country_adapter->getByIso($iso_code);
            $name = $id_country ? $country_adapter->getNameById((int) $id_lang, $id_country) : false;
            $names[] = is_string($name) && '' !== $name ? $name : $iso_code;
        }

        return $names;
    }

    /**
     * @description Validate the changes the merchant submits for a payment method and
     *              return the settings to store once merged with the stored ones. Values
     *              equal to the API ones are not stored, so later API changes still apply:
     *              the countries are not stored when they cover every selectable country
     *              and none were stored, nor when they cover every country the API allows
     *              (every active shop country when the API sets no restriction). When no
     *              country is selectable, an empty selection leaves the stored countries
     *              untouched.
     *
     * @param string $name
     * @param array $changes submitted fields only: `min`/`max` in cents of the back office
     *                       currency (null resets the bound, `max` must be positive),
     *                       `countries` as ISO codes
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
            if (!is_int($amount) || $amount < 0 || ('max' === $bound && 0 === $amount)) {
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

            if (!empty($selectable)) {
                $restricted = isset($settings['countries']) && is_array($settings['countries']);
                $allowed = null === $api['countries'] || !$restricted ? $selectable : $api['countries'];
                if (!empty($countries) && empty(array_diff($allowed, $countries))) {
                    unset($settings['countries']);
                } else {
                    $settings['countries'] = $countries;
                }
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
     *              `scalapay_custom_max_amounts` (`ISO:cents`) into the merchant settings,
     *              for every scope they are stored in. A bound already in the merchant
     *              settings is kept, a bound equal to the API one is not stored, and the
     *              source keys are left untouched.
     *
     * @return bool false when the merchant settings could not be written
     */
    public function importLegacyScalapaySettings()
    {
        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();

        return $this->importLegacySettings(
            'importLegacyScalapaySettings',
            ['scalapay_custom_min_amounts', 'scalapay_custom_max_amounts'],
            function ($stored, $id_shop_group, $id_shop) use ($configuration) {
                $entry = isset($stored['scalapay']) && is_array($stored['scalapay']) ? $stored['scalapay'] : [];
                $imported = false;

                foreach (['min', 'max'] as $bound) {
                    $legacy = $configuration->getScopedValue('scalapay_custom_' . $bound . '_amounts', $id_shop_group, $id_shop);
                    if (isset($entry[$bound]) || !is_string($legacy) || !preg_match('/^([A-Z]{3}):(\d+)$/', $legacy, $matches)) {
                        continue;
                    }

                    $currency = $matches[1];
                    $amount = (int) $matches[2];
                    if ($amount === $this->getApiLimits('scalapay', $currency, $id_shop_group, $id_shop)[$bound]) {
                        continue;
                    }

                    $entry[$bound] = [$currency => $amount];
                    $imported = true;
                }

                if ($imported) {
                    $stored['scalapay'] = $entry;
                }

                return $stored;
            }
        );
    }

    /**
     * @description Restrict the Bancontact billing countries to Belgium in the merchant
     *              settings of every scope where the `bancontact_country` setting is
     *              enabled. Countries already in the merchant settings are kept, and the
     *              source key is left untouched.
     *
     * @return bool false when the merchant settings could not be written
     */
    public function importLegacyBancontactCountry()
    {
        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();

        return $this->importLegacySettings(
            'importLegacyBancontactCountry',
            ['bancontact_country'],
            function ($stored, $id_shop_group, $id_shop) use ($configuration) {
                if (!(bool) $configuration->getScopedValue('bancontact_country', $id_shop_group, $id_shop)) {
                    return $stored;
                }

                $entry = isset($stored['bancontact']) && is_array($stored['bancontact']) ? $stored['bancontact'] : [];
                if (array_key_exists('countries', $entry)) {
                    return $stored;
                }

                $entry['countries'] = ['BE'];
                $stored['bancontact'] = $entry;

                return $stored;
            }
        );
    }

    /**
     * @description Merge legacy settings into the merchant settings of every scope (global,
     *              shop group, shop) the legacy keys or the merchant settings are stored in,
     *              a shop being read within its current shop group. Each merge starts from
     *              the merchant settings the scope reads before any write, so a scope keeps
     *              the settings it inherits. Scopes are written from the global one down to
     *              the shops, and a scope is only written when what it would inherit after
     *              the writes of its parents differs from its merge.
     *
     * @param string $method
     * @param string[] $legacy_keys
     * @param callable $merge function (array $stored, int $id_shop_group, int $id_shop): array
     *
     * @return bool false when the merchant settings of a scope could not be written
     */
    private function importLegacySettings($method, $legacy_keys, $merge)
    {
        $plugin = $this->dependencies->getPlugin();
        $configuration = $plugin->getConfigurationClass();
        $logger = $plugin->getLogger();

        $scopes = [];
        $shop_groups = [];
        foreach (array_merge($legacy_keys, ['apm_custom_settings']) as $key) {
            $rows = $plugin->getOrderStateRepository()->getConfigurationsByName($configuration->getName($key));
            foreach ($rows as $row) {
                $id_shop_group = (int) $row['id_shop_group'];
                $id_shop = (int) $row['id_shop'];
                if ($id_shop) {
                    if (!isset($shop_groups[$id_shop])) {
                        $shop_groups[$id_shop] = (int) $plugin->getShop()->get($id_shop)->id_shop_group;
                    }
                    $id_shop_group = $shop_groups[$id_shop] ?: $id_shop_group;
                }
                $scopes[$id_shop ? 'shop ' . $id_shop : 'group ' . $id_shop_group] = [$id_shop ? 2 : ($id_shop_group ? 1 : 0), $id_shop_group, $id_shop];
            }
        }
        usort($scopes, function ($a, $b) {
            return $a <=> $b;
        });

        $targets = [];
        foreach ($scopes as $index => $scope) {
            $targets[$index] = $merge($this->getJsonConfiguration('apm_custom_settings', $scope[1], $scope[2]), $scope[1], $scope[2]);
        }

        $flag = true;
        foreach ($scopes as $index => $scope) {
            list(, $id_shop_group, $id_shop) = $scope;
            $target = $targets[$index];
            if ($target == $this->getJsonConfiguration('apm_custom_settings', $id_shop_group, $id_shop)) {
                continue;
            }

            $label = 'ApmEligibility::' . $method . '() - shop group ' . $id_shop_group . ' / shop ' . $id_shop;
            ksort($target);
            if (!$configuration->set('apm_custom_settings', empty($target) ? '{}' : json_encode($target), $id_shop_group, $id_shop)) {
                $logger->addLog($label . ': failed to write the merchant settings', 'error');
                $flag = false;

                continue;
            }
            $logger->addLog($label . ': merchant settings imported');
        }

        return $flag;
    }

    /**
     * @param string $key
     * @param int|null $id_shop_group null: current context
     * @param int|null $id_shop null: current context
     *
     * @return array
     */
    private function getJsonConfiguration($key, $id_shop_group = null, $id_shop = null)
    {
        $configuration = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass();
        $value = null === $id_shop_group && null === $id_shop
            ? $configuration->getValue($key)
            : $configuration->getScopedValue($key, $id_shop_group, $id_shop);
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
