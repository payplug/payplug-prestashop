<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 * @author    PayPlug SAS
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\repositories;

use PayPlug\src\application\dependencies\BaseClass;

class TranslationsRepository extends BaseClass
{
    /**
     * @description
     *
     * @return self
     */
    public static function factory()
    {
        return new TranslationsRepository();
    }

    /**
     * @description Return trnaslation for index
     *
     * @param $id
     *
     * @return bool|mixed
     */
    public function translate($id)
    {
        if (!is_int($id)) {
            return false;
        }

        $translation = [
            // controllers/front/ajax.php
            1 => $this->l('Empty payment data', 'translationsrepository'),
            2 => $this->l('At least one of the fields is not correctly completed.', 'translationsrepository'),
            3 => $this->l('Your information has been saved', 'translationsrepository'),
            4 => $this->l('An error occurred. Please retry in few seconds.', 'translationsrepository'),
            5 => $this->l('Oney is momentarily unavailable.', 'translationsrepository'),
        ];

        return $translation[$id];
    }
}
