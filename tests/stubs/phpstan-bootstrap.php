<?php

namespace {
    define('_PS_VERSION_', '8.1.0');
    define('_PS_MODULE_DIR_', __DIR__ . '/../');
    define('_PS_MAIL_DIR_', __DIR__ . '/../mails/');
    define('_DB_PREFIX_', 'ps_');

    class ObjectModel
    {
        /** @var int|null */
        public $id;

        public function __construct($id = null, $id_lang = null, $id_shop = null)
        {
        }
    }

    class Address extends ObjectModel
    {
    }
    class Carrier extends ObjectModel
    {
    }
    class Cart extends ObjectModel
    {
        const BOTH = 1;
        const BOTH_WITHOUT_SHIPPING = 2;
        const ONLY_DISCOUNTS = 3;
    }
    class Customer extends ObjectModel
    {
        public static function customerExists($email, $returnId = false, $ignoreGuest = true)
        {
            return false;
        }
    }
    class Order extends ObjectModel
    {
        public static function getIdByCartId($id)
        {
            return 1;
        }

        public static function getOrderByCartId($id)
        {
            return new self();
        }
    }
    class Language extends ObjectModel
    {
        /** @var string */
        public $iso_code;

        public static function getIDs($active = true)
        {
            return [];
        }

        public static function getLanguages($active = true, $id_shop = false)
        {
            return [];
        }

        public static function loadLanguages()
        {
        }
    }

    class Tools
    {
        public static function getValue($k, $d = false)
        {
            return '';
        }

        public static function file_get_contents($u)
        {
            return '';
        }

        public static function displayPrice($p, $c = null)
        {
            return '';
        }

        public static function substr($s, $start, $len = null)
        {
            return '';
        }

        public static function getShopDomainSsl($http = false, $entities = false)
        {
            return '';
        }
    }

    class Configuration
    {
        public static function get($k)
        {
            return '';
        }

        public static function updateValue($k, $v)
        {
            return true;
        }

        public static function deleteByName($k)
        {
        }
    }

    class Cookie
    {
        /** @var int|null */
        public $id_cart;
        /** @var int|null */
        public $id_customer;
        /** @var int|null */
        public $previous_cart_id;

        public function write()
        {
        }
    }

    class Context
    {
        public $customer;
        public $cart;
        /** @var \Cookie */
        public $cookie;
        public $currency;
        public $currentLocale;
        public $link;
        public $language;
        public $shop;

        public static function getContext()
        {
            return new self();
        }
    }

    class Db
    {
        public static function getInstance()
        {
            return new self();
        }

        public function executeS($s)
        {
            return [];
        }
    }

    class Module extends ObjectModel
    {
        public static function getInstanceByName($n)
        {
            return new self();
        }

        public static function isEnabled($n)
        {
            return true;
        }
    }

    class Media
    {
        public static function addJsDef($p)
        {
        }

        public static function getMediaPath($p)
        {
            return '';
        }

        public static function getJsDef()
        {
            return [];
        }
    }

    class Country extends ObjectModel
    {
        public static function getByIso($iso)
        {
            return 1;
        }

        public static function getNameById($id_lang, $id_country)
        {
            return '';
        }
    }
    class Currency extends ObjectModel
    {
        public static function getIdByIsoCode($iso)
        {
            return 1;
        }
    }
    class Translate
    {
        public static function getModuleTranslation($m, $s, $f)
        {
            return '';
        }
    }
    class Shop extends ObjectModel
    {
        const CONTEXT_ALL = 1;

        public static function isFeatureActive()
        {
            return true;
        }

        public static function setContext($c)
        {
        }
    }
    class Tab extends ObjectModel
    {
        public static function getIdFromClassName($c)
        {
            return 1;
        }
    }
    class Dispatcher
    {
        public static function getInstance()
        {
            return new self();
        }
    }
    class PrestaShopException extends Exception
    {
    }
    class CartRule extends ObjectModel
    {
    }
    class Message extends ObjectModel
    {
    }
    class OrderHistory extends ObjectModel
    {
    }
    class OrderSlip extends ObjectModel
    {
        public static function getOrdersSlip($id_customer, $id_order)
        {
            return [];
        }
    }
    class OrderState extends ObjectModel
    {
        public static function getOrderStates($id_lang)
        {
            return [];
        }
    }
    class Mail extends ObjectModel
    {
    }

    class Product extends ObjectModel
    {
        public static $method;

        // Ajout de $id_shop et passage des arguments avec des valeurs par défaut pour être flexible
        public static function getIdProductAttributesByIdAttributes($id_product, $id_attributes, $id_shop = null)
        {
            return [];
        }

        public static function getIdProductAttributeByIdAttributes($id_product, $id_attributes, $id_shop = null)
        {
            return 1;
        }

        // getPriceStatic peut recevoir jusqu'à 8+ paramètres dans PrestaShop
        public static function getPriceStatic(
            $id_product,
            $usetax = true,
            $id_product_attribute = null,
            $decimals = 6,
            $divisor = null,
            $only_reduc = false,
            $usereduc = true,
            $quantity = 1,
            $force_reduc = false,
            $id_customer = null,
            $id_cart = null,
            $id_address = null,
            &$specific_price_output = null,
            $with_ecotax = true,
            $group_reduction = true,
            $context = null,
            $use_group_reduction = true
        ) {
            return 0.0;
        }
    }

    function pSQL($s)
    {
        return $s;
    }
    function bqSQL($s)
    {
        return $s;
    }
}

// Namespaces

namespace PrestaShop\PrestaShop\Core\Payment { class PaymentOption
{
} }

namespace PrestaShop\PrestaShop\Adapter { class SymfonyContainer
{
    public static function getInstance()
    {
        return new self();
    }
} }

namespace Symfony\Component\Routing\Generator { interface UrlGeneratorInterface
{
    const ABSOLUTE_URL = 1;
} }

namespace PayPlug\src\utilities\helpers\libphonenumber {
    class PhoneNumberUtil
    {
        public static function getInstance()
        {
        }
    }
    class NumberParseException extends \Exception
    {
    }
}

namespace PayPlug\src\utilities\services {
    class NotAllowedException extends \Exception
    {
    }
    class ForbiddenException extends \Exception
    {
    }
}

namespace PrestaShop\PsAccountsInstaller\Installer\Exception {
    class ModuleNotInstalledException extends \Exception
    {
    }
    class ModuleVersionException extends \Exception
    {
    }
}
