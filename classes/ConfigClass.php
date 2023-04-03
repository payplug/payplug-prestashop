<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS
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

namespace PayPlug\classes;

use libphonenumberlight;

class ConfigClass
{
    public $amountCurrencyClass;
    public $configurations;
    public $email;
    public $features_json;
    public $logger;
    public $myLogPHP;
    public $orderStates = [
        'paid' => [
            'cfg' => 'PS_OS_PAYMENT',
            'payplug_cfg' => [
                'ORDER_STATE_PAID',
                'ORDER_STATE_PAID_TEST',
            ],
            'template' => 'payment',
            'logable' => true,
            'send_email' => true,
            'paid' => true,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => true,
            'color' => '#04b404',
            'name' => [
                'en' => 'Payment accepted',
                'fr' => 'Paiement effectué',
                'es' => 'Pago efectuado',
                'it' => 'Pagamento effettuato',
            ],
        ],
        'refund' => [
            'cfg' => 'PS_OS_REFUND',
            'payplug_cfg' => [
                'ORDER_STATE_REFUND',
                'ORDER_STATE_REFUND_TEST',
            ],
            'template' => 'refund',
            'logable' => false,
            'send_email' => true,
            'paid' => false,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => true,
            'color' => '#ea3737',
            'name' => [
                'en' => 'Refunded',
                'fr' => 'Remboursé',
                'es' => 'Reembolsado',
                'it' => 'Rimborsato',
            ],
        ],
        'pending' => [
            'cfg' => 'PS_OS_PENDING',
            'payplug_cfg' => [
                'ORDER_STATE_PENDING',
                'ORDER_STATE_PENDING_TEST',
            ],
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => false,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => true,
            'color' => '#a1f8a1',
            'name' => [
                'en' => 'Payment in progress',
                'fr' => 'Paiement en cours',
                'es' => 'Pago en curso',
                'it' => 'Pagamento in corso',
            ],
        ],
        'error' => [
            'cfg' => 'PS_OS_ERROR',
            'payplug_cfg' => [
                'ORDER_STATE_ERROR',
                'ORDER_STATE_ERROR_TEST',
            ],
            'template' => 'payment_error',
            'logable' => false,
            'send_email' => true,
            'paid' => false,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#8f0621',
            'name' => [
                'en' => 'Payment failed',
                'fr' => 'Paiement échoué',
                'es' => 'Payment failed',
                'it' => 'Payment failed',
            ],
        ],
        'cancelled' => [
            'cfg' => 'PS_OS_CANCELED',
            'payplug_cfg' => [
                'ORDER_STATE_CANCELLED',
                'ORDER_STATE_CANCELLED_TEST',
            ],
            'template' => 'order_canceled',
            'logable' => false,
            'send_email' => true,
            'paid' => false,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#2C3E50',
            'name' => [
                'en' => 'Payment cancelled',
                'fr' => 'Paiement annulé',
                'es' => 'Payment cancelled',
                'it' => 'Payment cancelled',
            ],
        ],
        'auth' => [
            'cfg' => null,
            'payplug_cfg' => [
                'ORDER_STATE_AUTH',
                'ORDER_STATE_AUTH_TEST',
            ],
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => true,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#04b404',
            'name' => [
                'en' => 'Payment authorized',
                'fr' => 'Paiement autorisé',
                'es' => 'Pago',
                'it' => 'Pagamento',
            ],
        ],
        'exp' => [
            'cfg' => null,
            'payplug_cfg' => [
                'ORDER_STATE_EXP',
                'ORDER_STATE_EXP_TEST',
            ],
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => false,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#8f0621',
            'name' => [
                'en' => 'Autorization expired',
                'es' => 'Autorización vencida',
                'fr' => 'Autorisation expirée',
                'it' => 'Autorizzazione scaduta',
            ],
        ],
    ];
    public $orderStatesOney = [
        'oney_pg' => [
            'cfg' => null,
            'payplug_cfg' => [
                'ORDER_STATE_ONEY_PG',
                'ORDER_STATE_ONEY_PG_TEST',
            ],
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => false,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#a1f8a1',
            'name' => [
                'en' => 'Oney - Pending',
                'fr' => 'Oney - En attente',
                'es' => 'Oney - Pending',
                'it' => 'Oney - Pending',
            ],
        ],
    ];
    public $payplugLanguages = ['en', 'fr', 'es', 'it'];
    public $version;
    public $warning;

    private $api_live;
    private $api_test;
    private $check_configuration;
    private $config;
    private $constant;
    private $context;
    private $country;
    private $dependencies;
    private $html = '';
    private $img_lang;
    private $install;
    private $media;
    private $module;
    private $oney;
    private $payment_status;
    private $query;
    private $tools;
    private $validate;
    private $validationErrors = [];
    private $validators = [];

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;

        $this->api_rest = $this->dependencies->getPlugin()->getApiRest();
        $this->config = $this->dependencies->getPlugin()->getConfiguration();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->country = $this->dependencies->getPlugin()->getCountry();
        $this->install = $this->dependencies->getPlugin()->getInstall();
        $this->media = $this->dependencies->getPlugin()->getMedia();
        $this->module = $this->dependencies->getPlugin()->getModule();
        $this->oney = $this->dependencies->getPlugin()->getOney();
        $this->query = $this->dependencies->getPlugin()->getQuery();
        $this->tools = $this->dependencies->getPlugin()->getTools();
        $this->validate = $this->dependencies->getPlugin()->getValidate();
        $this->validators = $this->dependencies->getValidators();

        $this->setLoggers();
        $this->setConfigurationProperties();

        if (file_exists(dirname(__FILE__) . '/../features.json')) {
            $this->features_json = json_decode($this->tools->tool('file_get_contents', dirname(__FILE__) . '/../features.json'), true);
        } else {
            $this->features_json = [];
        }
    }

    public function getAdapterPrestaClasse()
    {
        return $this->dependencies->loadAdapterPresta();
    }

    public function getImgLang()
    {
        return $this->img_lang;
    }

    public function getPaymentStatus()
    {
        return $this->payment_status;
    }

    /**
     * @param bool $force_all
     *
     * @return bool
     */
    public function disable()
    {
        return $this->config->updateValue($this->dependencies->getConfigurationKey('enable'), 0);
    }

    /**
     * @description
     *
     * @param $cart
     *
     * @return array
     */
    public function getAvailableOptions($cart)
    {
        if (!$this->isAllowed()) {
            return false;
        }

        $permissions = $this->dependencies->apiClass->getAccountPermissions();
        $available_options = [
            'standard' => (bool) $this->config->get($this->dependencies->getConfigurationKey('standard')),
            'live' => !(bool) $this->config->get($this->dependencies->getConfigurationKey('sandboxMode')),
            'embedded' => (string) $this->config->get($this->dependencies->getConfigurationKey('embeddedMode')),
            'one_click' => (bool) $this->config->get($this->dependencies->getConfigurationKey('oneClick')),
            'installment' => (bool) $this->config->get($this->dependencies->getConfigurationKey('inst')),
            'deferred' => (bool) $this->config->get($this->dependencies->getConfigurationKey('deferred')),
            'oney' => (bool) $this->config->get($this->dependencies->getConfigurationKey('oney')),
            'bancontact' => (bool) $this->config->get($this->dependencies->getConfigurationKey('bancontact')),
            'applepay' => (bool) $this->config->get($this->dependencies->getConfigurationKey('applepay')),
            'amex' => (bool) $this->config->get($this->dependencies->getConfigurationKey('amex')),
        ];

        if ($this->config->get($this->dependencies->getConfigurationKey('email')) === null
            || !$this->dependencies->amountCurrencyClass->checkCurrency($cart)
            || !$this->dependencies->amountCurrencyClass->checkAmount($cart)
        ) {
            $available_options['standard'] = false;
            $available_options['sandbox'] = false;
            $available_options['embedded'] = false;
            $available_options['one_click'] = false;
            $available_options['installment'] = false;
            $available_options['deferred'] = false;
            $available_options['oney'] = false;
            $available_options['bancontact'] = false;
            $available_options['applepay'] = false;
            $available_options['amex'] = false;
        } else {
            if (!$this->validators['payment']->hasPermissions($permissions, 'use_live_mode')['result']
                || $this->config->get($this->dependencies->getConfigurationKey('liveApiKey')) === null
            ) {
                $available_options['live'] = false;
            }
            if (!$this->validators['payment']->hasPermissions($permissions, 'can_save_cards')['result']) {
                $available_options['one_click'] = false;
            }
            if (!$this->validators['payment']->hasPermissions($permissions, 'can_create_installment_plan')['result']) {
                $available_options['installment'] = false;
            }
            if (!$this->validators['payment']->hasPermissions($permissions, 'can_create_deferred_payment')['result']) {
                $available_options['deferred'] = false;
            }
            if (!$this->validators['payment']->hasPermissions($permissions, 'can_use_oney')['result']) {
                $available_options['oney'] = false;
            }
            if (!$this->validators['payment']->hasPermissions($permissions, 'can_use_bancontact')['result']) {
                $available_options['bancontact'] = false;
            }
            if (!$this->validators['payment']->hasPermissions($permissions, 'can_use_applepay')['result'] || !$available_options['live']) {
                $available_options['applepay'] = false;
            }
            if (!$this->validators['payment']->hasPermissions($permissions, 'can_use_amex')['result'] || !$available_options['live']) {
                $available_options['amex'] = false;
            }
        }

        return $available_options;
    }

    /**
     * @description
     * Check if Payplug is allowed
     *
     * @return bool
     */
    public function isAllowed()
    {
        $is_shown = $this->validators['module']->canBeShown(
            (bool) $this->config->get($this->dependencies->getConfigurationKey('enable'))
        );
        $is_allowed = $this->validators['module']->isAllowed(
            (bool) $this->module->isEnabled($this->dependencies->name),
            $is_shown['result']
        );

        return $is_allowed['result'];
    }

    /**
     * @return bool
     */
    public function checkState()
    {
        $state = $this->validators['module']->isAllRequirementsChecked(
            $this->getReportRequirements()
        );

        return $state['result'];
    }

    /**
     * @description check if account
     * is linked to Psaccount
     *
     * @return bool
     */
    public function checkPsAccount()
    {
        if ($this->dependencies->name == 'pspaylater') {
            $module = $this->module->getInstanceByName($this->dependencies->name);
            $check_ps_account = $this->validators['module']->isAccountLinkedToPsAccount($module);

            return $check_ps_account['result'];
        }

        return true;
    }

    /**
     * Get iso code from language code
     *
     * @param $language
     *
     * @return string
     */
    public function getIsoFromLanguageCode($language)
    {
        if (!$this->validate->validate('isLoadedObject', $language)) {
            return false;
        }
        $parse = explode('-', $language->language_code);

        return $this->tools->tool('strtolower', $parse[0]);
    }

    public static function setNotification()
    {
        return new PayPlugNotifications();
    }

    public static function setValidation()
    {
        return new PayPlugValidation();
    }

    /**
     * Get live permissions
     *
     * @return array
     */
    public function getLivePermissions()
    {
        $live_api_key = $this->config->get($this->dependencies->getConfigurationKey('liveApiKey'));
        $livepermissions = $this->dependencies->apiClass->getAccount($live_api_key);

        return $livepermissions ? $livepermissions : [];
    }

    /**
     * Check if current device used is mobile
     *
     * @return bool
     */
    public function isMobiledevice()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];

        $reg1 = '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|';
        $reg1 .= 'iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|';
        $reg1 .= 'palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|';
        $reg1 .= 'up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i';

        $reg2 = '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|';
        $reg2 .= 'an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|';
        $reg2 .= 'br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|';
        $reg2 .= 'dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|';
        $reg2 .= 'ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|';
        $reg2 .= 'hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|';
        $reg2 .= 'iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|';
        $reg2 .= 'klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|';
        $reg2 .= 'ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|';
        $reg2 .= 'mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|';
        $reg2 .= 'ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|';
        $reg2 .= 'pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|';
        $reg2 .= 'qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|';
        $reg2 .= 'sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|';
        $reg2 .= 'sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|';
        $reg2 .= 'tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|';
        $reg2 .= 'vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|';
        $reg2 .= 'wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i';

        if (preg_match($reg1, $useragent) || preg_match($reg2, $this->tools->substr($useragent, 0, 4))) {
            return true;
        }

        return false;
    }

    /**
     * Check if given phone number is valid mobile phone number
     *
     * @param string $phone_number
     * @param string $iso_code
     *
     * @throws libphonenumberlight\NumberParseException
     *
     * @return bool
     */
    public function isValidMobilePhoneNumber($iso_code, $phone_number = false)
    {
        if (empty($phone_number) || !preg_match('/^[+0-9. ()\/-]{6,}$/', $phone_number)) {
            return false;
        }

        try {
            $phone_util = libphonenumberlight\PhoneNumberUtil::getInstance();
            $parsed = $phone_util->parse($phone_number, $iso_code);

            if ($phone_util->getRegionCodeForCountryCode($parsed->getCountryCode()) != $iso_code) {
                return false;
            }

            $is_mobile = $phone_util->getNumberType($parsed);

            return (bool) (in_array($is_mobile, [1, 2], true));
        } catch (libphonenumberlight\NumberParseException $e) {
            // @todo : Add Log
            return false;
        }
    }

    /**
     * Return international formatted phone number (norm E.164)
     *
     * @param $phone_number
     * @param $country
     *
     * @throws libphonenumberlight\NumberParseException
     *
     * @return null|string
     */
    public function formatPhoneNumber($phone_number, $country)
    {
        if (empty($phone_number) || !preg_match('/^[+0-9. ()\/-]{6,}$/', $phone_number)) {
            return null;
        }
        if (!is_object($country)) {
            $country = $this->country->get((int) $country);
        }
        if (!$this->validate->validate('isLoadedObject', $country)) {
            return null;
        }

        try {
            $iso_code = $this->getIsoCodeByCountryId($country->id);

            if (!$iso_code) {
                return null;
            }

            $phone_util = \libphonenumberlight\PhoneNumberUtil::getInstance();
            $parsed = $phone_util->parse($phone_number, $iso_code);

            if (!$phone_util->isValidNumber($parsed)) {
                // todo: add log
                return null;
            }

            return $phone_util->format($parsed, \libphonenumberlight\PhoneNumberFormat::E164);
        } catch (libphonenumberlight\NumberParseException $e) {
            // todo: add log
            return null;
        }
    }

    /**
     * Get the right country iso-code or null if it does'nt fit the ISO 3166-1 alpha-2 norm
     *
     * @param int $country_id
     *
     * @return false|int
     */
    public function getIsoCodeByCountryId($country_id)
    {
        $iso_code_list = $this->getIsoCodeList();
        if (!is_array($iso_code_list) || empty($iso_code_list) || !count($iso_code_list)) {
            return '';
        }
        if (!$this->validate->validate('isInt', $country_id)) {
            return '';
        }

        $iso_code = $this->dependencies->getRepositories()['country']->getIsoCodeByCountry((int) $country_id);
        $iso_code = $this->tools->tool('strtoupper', $iso_code);

        if (!in_array($iso_code, $iso_code_list, true)) {
            return '';
        }

        return $iso_code;
    }

    /**
     * Get all country iso-code of ISO 3166-1 alpha-2 norm
     * Source: DB PayPlug
     *
     * @return null|array
     */
    public function getIsoCodeList()
    {
        $country_list_path = _PS_MODULE_DIR_ . $this->dependencies->name . '/lib/iso_3166-1_alpha-2/data.csv';
        $iso_code_list = [];
        if (($handle = fopen($country_list_path, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $iso_code_list[] = $this->tools->tool('strtoupper', $data[0]);
            }
            fclose($handle);

            return $iso_code_list;
        }

        return null;
    }

    /**
     * @param $id_customer
     *
     * @throws PrestaShopDatabaseException
     *
     * @return null|array|bool
     */
    public function gdprCardExport($id_customer)
    {
        if (!is_int($id_customer) || $id_customer === null) {
            return [];
        }

        $cards = $this->dependencies->getRepositories()['card']->getAllByCustomer($id_customer);
        if (!$cards) {
            return [];
        }

        $i = 1;
        $result = [];
        foreach ($cards as &$card) {
            $card['expiry_date'] = date(
                'm / y',
                mktime(0, 0, 0, (int) $card['exp_month'], 1, (int) $card['exp_year'])
            );
            $result[] = [
                '#' => $i,
                $this->dependencies->l('payplug.gdprCardExport.brand', 'configclass') => $card['brand'],
                $this->dependencies->l('payplug.gdprCardExport.country', 'configclass') => $card['country'],
                $this->dependencies->l('payplug.gdprCardExport.card', 'configclass') => '**** **** **** ' . $card['last4'],
                $this->dependencies->l('payplug.gdprCardExport.expiryDate', 'configclass') => $card['expiry_date'],
            ];
            ++$i;
        }

        return $result;
    }

    /**
     * @description Check if current configuration requirements are respected
     *
     * @return array
     */
    public function getReportRequirements()
    {
        $php_min_version = 50600;
        $curl_min_version = '7.21';
        $openssl_min_version = 0x1000100f;
        $report = [
            'php' => [
                'version' => 0,
                'installed' => true,
                'up2date' => false,
            ],
            'curl' => [
                'version' => 0,
                'installed' => false,
                'up2date' => false,
            ],
            'openssl' => [
                'version' => 0,
                'installed' => false,
                'up2date' => false,
            ],
        ];

        //PHP
        if (!defined('PHP_VERSION_ID')) {
            $report['php']['version'] = PHP_VERSION;
            $php_version = explode('.', PHP_VERSION);
            define('PHP_VERSION_ID', ($php_version[0] * 10000 + $php_version[1] * 100 + $php_version[2]));
        }
        $report['php']['up2date'] = PHP_VERSION_ID >= $php_min_version ? true : false;

        //cURL
        $curl_exists = extension_loaded('curl');
        if ($curl_exists) {
            $curl_version = curl_version();
            $report['curl']['version'] = $curl_version['version'];
            $report['curl']['installed'] = true;
            $report['curl']['up2date'] = version_compare(
                $curl_version['version'],
                $curl_min_version,
                '>='
            ) ? true : false;
        }

        //OpenSSl
        $openssl_exists = extension_loaded('openssl');
        if ($openssl_exists) {
            $report['openssl']['version'] = OPENSSL_VERSION_NUMBER;
            $report['openssl']['installed'] = true;
            $report['openssl']['up2date'] = OPENSSL_VERSION_NUMBER >= $openssl_min_version ? true : false;
        }

        return $report;
    }

    public function isValidFeature($name)
    {
        $is_valid_feature = $this->validators['module']->isFeature($this->features_json, $name);

        return $is_valid_feature['result'];
    }

    public function fetchTemplate($file)
    {
        if ($this->context->smarty->tpl_vars) {
            foreach (array_keys($this->context->smarty->tpl_vars) as $key) {
                if (strpos($key, 'feature_') !== false && !$this->isValidFeature($key)) {
                    unset($this->context->smarty->tpl_vars[$key]);
                }
            }
        }

        $this->context->smarty->assign([
            'module_name' => $this->dependencies->name,
        ]);

        return $this
            ->module
            ->getInstanceByName($this->dependencies->name)
            ->display(_PS_MODULE_DIR_ . $this->dependencies->name . '/' . $this->dependencies->name . '.php', $file);
    }

    /**
     * @description Disconnect user
     */
    public function logout()
    {
        $this->install->setConfig();
        $this->config->updateValue($this->dependencies->getConfigurationKey('enable'), 0);
        $this->config->loadConfiguration();
    }

    /**
     * Create log files to be used everywhere in PayPlug module
     */
    private function setLoggers()
    {
        $this->logger = $this->dependencies->getPlugin()->getLogger();
        $this->myLogPHP = new MyLogPHP();

        $this->logger->setProcess('config');
    }

    /**
     * Set very adapter properties
     */
    private function setConfigurationProperties()
    {
        $this->api_live = $this->config->get($this->dependencies->getConfigurationKey('liveApiKey'));
        $this->api_test = $this->config->get($this->dependencies->getConfigurationKey('testApiKey'));
        $this->email = $this->config->get($this->dependencies->getConfigurationKey('email'));

        $available_img_lang = [
            'fr',
            'gb',
            'en',
            'it',
        ];
        $this->img_lang = in_array($this->context->language->iso_code, $available_img_lang)
            ? $this->context->language->iso_code : 'default';
        $this->ssl_enable = $this->config->get('PS_SSL_ENABLED');

        if ((!isset($this->email) || (!isset($this->api_live) && empty($this->api_test)))) {
            $this->warning = $this->dependencies->l('payplug.setConfigurationProperties.configureModule', 'configclass');
        }

        $this->payment_status = [
            1 => $this->dependencies->l('payplug.setConfigurationProperties.notPaid', 'configclass'),
            2 => $this->dependencies->l('payplug.setConfigurationProperties.paid', 'configclass'),
            3 => $this->dependencies->l('payplug.setConfigurationProperties.failed', 'configclass'),
            4 => $this->dependencies->l('payplug.setConfigurationProperties.partiallyRefunded', 'configclass'),
            5 => $this->dependencies->l('payplug.setConfigurationProperties.refunded', 'configclass'),
            6 => $this->dependencies->l('payplug.setConfigurationProperties.onGoing', 'configclass'),
            7 => $this->dependencies->l('payplug.setConfigurationProperties.cancelled', 'configclass'),
            8 => $this->dependencies->l('payplug.setConfigurationProperties.authorized', 'configclass'),
            9 => $this->dependencies->l('payplug.setConfigurationProperties.authorizationExpired', 'configclass'),
            10 => $this->dependencies->l('payplug.setConfigurationProperties.oneyPending', 'configclass'),
            11 => $this->dependencies->l('payplug.setConfigurationProperties.abandoned', 'configclass'),
        ];
    }
}
