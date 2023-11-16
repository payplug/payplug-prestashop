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

class Country
{
    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description Get all country iso-code of ISO 3166-1 alpha-2 norm
     *
     * @return array
     */
    public function getIsoCodeList()
    {
        $country_list_path = $this->dependencies
            ->getPlugin()
            ->getConstant()
            ->get('_PS_MODULE_DIR_') . $this->dependencies->name . '/lib/iso_3166-1_alpha-2/data.csv';

        $iso_code_list = [];
        if (($handle = fopen($country_list_path, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $iso_code_list[] = $this->dependencies
                    ->getPlugin()
                    ->getTools()
                    ->tool('strtoupper', $data[0]);
            }
            fclose($handle);

            return $iso_code_list;
        }

        return [];
    }

    /**
     * @description Get the right country iso-code or null if it does'nt fit the ISO 3166-1 alpha-2 norm.
     *
     * @param $country_id
     *
     * @return string
     */
    public function getIsoCodeByCountryId($country_id)
    {
        $iso_code_list = $this->getIsoCodeList();
        if (!is_array($iso_code_list) || empty($iso_code_list) || !count($iso_code_list)) {
            return '';
        }

        if (!$this->dependencies
            ->getPlugin()
            ->getValidate()
            ->validate('isInt', $country_id)) {
            return '';
        }

        $iso_code = $this->dependencies
            ->getPlugin()
            ->getCountryRepository()
            ->getIsoCodeByCountry((int) $country_id);
        $iso_code = $this->dependencies
            ->getPlugin()
            ->getTools()
            ->tool('strtoupper', $iso_code);

        if (!in_array($iso_code, $iso_code_list, true)) {
            return '';
        }

        return $iso_code;
    }
}
