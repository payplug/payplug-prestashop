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
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2020 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
$files = array();

// get PHP Payplug Lib
$payplug_lib_files = array(
    '/Payplug/Authentication.php',
    '/Payplug/Card.php',
    '/Payplug/Customer.php',
    '/Payplug/InstallmentPlan.php',
    '/Payplug/Notification.php',
    '/Payplug/Payment.php',
    '/Payplug/Payplug.php',
    '/Payplug/Refund.php',
    '/Payplug/OneySimulation.php',
    '/Payplug/Core/APIRoutes.php',
    '/Payplug/Exception/PayplugException.php',
    '/Payplug/Exception/DependencyException.php',
    '/Payplug/Core/Config.php',
    '/Payplug/Core/IHttpRequest.php',
    '/Payplug/Core/CurlRequest.php',
    '/Payplug/Core/HttpClient.php',
    '/Payplug/Exception/HttpException.php',
    '/Payplug/Exception/BadRequestException.php',
    '/Payplug/Exception/ConfigurationException.php',
    '/Payplug/Exception/ConfigurationNotSetException.php',
    '/Payplug/Exception/ConnectionException.php',
    '/Payplug/Exception/ForbiddenException.php',
    '/Payplug/Exception/InvalidPaymentException.php',
    '/Payplug/Exception/NotAllowedException.php',
    '/Payplug/Exception/NotFoundException.php',
    '/Payplug/Exception/PayplugServerException.php',
    '/Payplug/Exception/PHPVersionException.php',
    '/Payplug/Exception/UnauthorizedException.php',
    '/Payplug/Exception/UndefinedAttributeException.php',
    '/Payplug/Exception/UnexpectedAPIResponseException.php',
    '/Payplug/Exception/UnknownAPIResourceException.php',
    '/Payplug/Resource/IAPIResourceFactory.php',
    '/Payplug/Resource/APIResource.php',
    '/Payplug/Resource/Card.php',
    '/Payplug/Resource/Customer.php',
    '/Payplug/Resource/IVerifiableAPIResource.php',
    '/Payplug/Resource/InstallmentPlan.php',
    '/Payplug/Resource/InstallmentPlanSchedule.php',
    '/Payplug/Resource/Payment.php',
    '/Payplug/Resource/PaymentCard.php',
    '/Payplug/Resource/PaymentCustomer.php',
    '/Payplug/Resource/PaymentHostedPayment.php',
    '/Payplug/Resource/PaymentNotification.php',
    '/Payplug/Resource/PaymentPaymentFailure.php',
    '/Payplug/Resource/Refund.php',
    '/Payplug/Resource/PaymentAuthorization.php',
    '/Payplug/Resource/PaymentBilling.php',
    '/Payplug/Resource/PaymentShipping.php',
    '/Payplug/Resource/OneySimulationResource.php',
);
$files = array_merge($files, $payplug_lib_files);

if (!function_exists('curl_init')) {
    throw new Exception('PHP cURL extension must be enabled on your server.');
} else {
    foreach ($files as $file) {
        $path = dirname(__FILE__) . $file;
        if (file_exists($path)) {
            require_once($path);
        }
    }
}
