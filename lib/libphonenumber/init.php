<?php
/**
 * 2013 - 2020 PayPlug SAS
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
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

// get PHP Phone Number Libs
$phonenumber_files = array(
    '/CountryCodeSource.php',
    '/CountryCodeToRegionCodeMap.php',
    '/MetadataLoaderInterface.php',
    '/DefaultMetadataLoader.php',
    '/Matcher.php',
    '/MatcherAPIInterface.php',
    '/MetadataSourceInterface.php',
    '/MultiFileMetadataSourceImpl.php',
    '/NumberFormat.php',
    '/NumberParseException.php',
    '/PhoneMetadata.php',
    '/PhoneNumber.php',
    '/PhoneNumberDesc.php',
    '/PhoneNumberFormat.php',
    '/PhoneNumberType.php',
    '/PhoneNumberUtil.php',
    '/RegexBasedMatcher.php',
    '/ValidationResult.php',
);
foreach ($phonenumber_files as $file) {
    $path = dirname(__FILE__) . $file;
    if (file_exists($path)) {
        require_once($path);
    }
}
