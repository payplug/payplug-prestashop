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

namespace PayPlug\src\models\repositories;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderPaymentRepository extends EntityRepository
{
    private $fields = [
        'id_order' => 'integer',
        'id_payment' => 'string',
    ];

    public function __construct($prefix = '', $dependencies = null)
    {
        parent::__construct($prefix, $dependencies);
        $this->table_name = $this->prefix . $this->dependencies->name . '_order_payment';
    }

    /**
     * @description Insert new order payment in the database
     *
     * @param array $parameters
     *
     * @return bool
     */
    public function createOrderPayment($parameters = [])
    {
        if (!is_array($parameters) || empty($parameters)) {
            return false;
        }

        $this
            ->insert()
            ->into($this->table_name);

        foreach ($parameters as $key => $value) {
            if (array_key_exists($key, $this->fields)) {
                switch ($this->fields[$key]) {
                    case 'string':
                        if (is_string($value) && $value) {
                            $this->fields($key)->values($this->escape($value));
                        }

                        break;
                    case 'integer':
                        if (is_int($value)) {
                            $this->fields($key)->values((int) $value);
                        }

                        break;
                    case 'bool':
                        if (is_bool($value)) {
                            $this->fields($key)->values($value ? 1 : 0);
                        }

                        break;
                    default:
                        break;
                }
            }
        }

        return (bool) $this->build();
    }

    /**
     * @description Get all the order payment from a given id
     *
     * @param int $order_id
     *
     * @return array
     */
    public function getAllByOrder($order_id = 0)
    {
        if (!is_int($order_id) || !$order_id) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('*')
            ->from($this->table_name)
            ->where('`id_order` = ' . (int) $order_id)
            ->build();

        return $result ?: [];
    }

    /**
     * @description Create the table in the database
     *
     * @param string $engine
     *
     * @return bool
     */
    public function initialize($engine = '')
    {
        if (!is_string($engine) || !$engine) {
            return false;
        }

        $this
            ->create()
            ->table($this->table_name)
            ->fields('`id_payplug_order_payment` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`id_order` INT(11) UNSIGNED NOT NULL')
            ->fields('`id_payment` VARCHAR(255) NOT NULL')
            ->engine($engine);

        return $this->build();
    }
}
