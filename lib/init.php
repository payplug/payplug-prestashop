<?php
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
require_once(dirname(__FILE__).'/Payplug/Card.php');
require_once(dirname(__FILE__).'/Payplug/Customer.php');
require_once(dirname(__FILE__).'/Payplug/Notification.php');
require_once(dirname(__FILE__).'/Payplug/Payment.php');
require_once(dirname(__FILE__).'/Payplug/Payplug.php');
require_once(dirname(__FILE__).'/Payplug/Refund.php');

require_once(dirname(__FILE__).'/Payplug/Core/APIRoutes.php');
require_once(dirname(__FILE__).'/Payplug/Core/Config.php');
require_once(dirname(__FILE__).'/Payplug/Core/IHttpRequest.php');
require_once(dirname(__FILE__).'/Payplug/Core/CurlRequest.php');
require_once(dirname(__FILE__).'/Payplug/Core/HttpClient.php');

require_once(dirname(__FILE__).'/Payplug/Exception/PayplugException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/HttpException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/BadRequestException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/ConfigurationException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/ConfigurationNotSetException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/ConnectionException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/DependencyException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/ForbiddenException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/InvalidPaymentException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/NotAllowedException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/NotFoundException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/PayplugServerException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/PHPVersionException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/UnauthorizedException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/UndefinedAttributeException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/UnexpectedAPIResponseException.php');
require_once(dirname(__FILE__).'/Payplug/Exception/UnknownAPIResourceException.php');

require_once(dirname(__FILE__).'/Payplug/Resource/IAPIResourceFactory.php');
require_once(dirname(__FILE__).'/Payplug/Resource/APIResource.php');
require_once(dirname(__FILE__).'/Payplug/Resource/Card.php');
require_once(dirname(__FILE__).'/Payplug/Resource/Customer.php');
require_once(dirname(__FILE__).'/Payplug/Resource/IVerifiableAPIResource.php');
require_once(dirname(__FILE__).'/Payplug/Resource/Payment.php');
require_once(dirname(__FILE__).'/Payplug/Resource/PaymentCard.php');
require_once(dirname(__FILE__).'/Payplug/Resource/PaymentCustomer.php');
require_once(dirname(__FILE__).'/Payplug/Resource/PaymentHostedPayment.php');
require_once(dirname(__FILE__).'/Payplug/Resource/PaymentNotification.php');
require_once(dirname(__FILE__).'/Payplug/Resource/PaymentPaymentFailure.php');
require_once(dirname(__FILE__).'/Payplug/Resource/Refund.php');
