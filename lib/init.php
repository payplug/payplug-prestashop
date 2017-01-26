<?php
/**
 * 2013 - 2017 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2017 PayPlug SAS
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */
/*
spl_autoload_register(function ($class) {
    if (strpos($class, 'Payplug') !== 0) {
        return;
    }

    $file = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

    if (file_exists($file)) {
        require($file);
    }
});
*/

if (!function_exists('curl_init')) {
    throw new Exception('PHP cURL extension must be enabled on your server.');
} else {
    require_once(dirname(__FILE__) . '/Payplug/Card.php');
    require_once(dirname(__FILE__) . '/Payplug/Customer.php');
    require_once(dirname(__FILE__) . '/Payplug/Notification.php');
    require_once(dirname(__FILE__) . '/Payplug/Payment.php');
    require_once(dirname(__FILE__) . '/Payplug/Payplug.php');
    require_once(dirname(__FILE__) . '/Payplug/Refund.php');

    require_once(dirname(__FILE__) . '/Payplug/Core/APIRoutes.php');
    require_once(dirname(__FILE__) . '/Payplug/Core/Config.php');
    require_once(dirname(__FILE__) . '/Payplug/Core/IHttpRequest.php');
    require_once(dirname(__FILE__) . '/Payplug/Core/CurlRequest.php');
    require_once(dirname(__FILE__) . '/Payplug/Core/HttpClient.php');

    require_once(dirname(__FILE__) . '/Payplug/Exception/PayplugException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/HttpException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/BadRequestException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/ConfigurationException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/ConfigurationNotSetException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/ConnectionException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/DependencyException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/ForbiddenException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/InvalidPaymentException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/NotAllowedException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/NotFoundException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/PayplugServerException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/PHPVersionException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/UnauthorizedException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/UndefinedAttributeException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/UnexpectedAPIResponseException.php');
    require_once(dirname(__FILE__) . '/Payplug/Exception/UnknownAPIResourceException.php');

    require_once(dirname(__FILE__) . '/Payplug/Resource/IAPIResourceFactory.php');
    require_once(dirname(__FILE__) . '/Payplug/Resource/APIResource.php');
    require_once(dirname(__FILE__) . '/Payplug/Resource/Card.php');
    require_once(dirname(__FILE__) . '/Payplug/Resource/Customer.php');
    require_once(dirname(__FILE__) . '/Payplug/Resource/IVerifiableAPIResource.php');
    require_once(dirname(__FILE__) . '/Payplug/Resource/Payment.php');
    require_once(dirname(__FILE__) . '/Payplug/Resource/PaymentCard.php');
    require_once(dirname(__FILE__) . '/Payplug/Resource/PaymentCustomer.php');
    require_once(dirname(__FILE__) . '/Payplug/Resource/PaymentHostedPayment.php');
    require_once(dirname(__FILE__) . '/Payplug/Resource/PaymentNotification.php');
    require_once(dirname(__FILE__) . '/Payplug/Resource/PaymentPaymentFailure.php');
    require_once(dirname(__FILE__) . '/Payplug/Resource/Refund.php');
}
