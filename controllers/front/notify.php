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
if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\classes\DependenciesClass;
use PayPlug\src\utilities\traits\ServiceGetter;

/**
 * @description
 * Dedicated, parameter-free endpoint for the Unified API's asynchronous webhook notifier
 * ("Receiver"), exposed at module/payplug/notify. Mirrors ipn.php's PRECEDENT, not its
 * implementation: like ipn.php, this is a fixed, query-string-free URL a merchant/support can
 * hand to PayPlug's webhook configuration without it being one branch of a multi-action
 * controller - but unlike ipn.php (a bare two-line call into ConfigClass's static notification
 * handling), this endpoint still needs OperationAction/ServiceGetter's DI wiring, since it's
 * dispatching into this module's own `payplug.action.operation` service rather than a static
 * classic-API helper. Previously a branch of unified.php?action=notify, shared with the
 * browser-facing create/challenge/return actions; split out because that query string is
 * fragile to hand off to support/Cockpit for the Receiver configuration (a forgotten or
 * mistyped ?action=notify fails silently - no notification, no error anywhere) and because a
 * server-to-server webhook and browser endpoints are two different trust domains that don't
 * belong on the same URL. The business logic itself is untouched: this controller only calls
 * OperationAction::notifyAction() via the same service and shapes the HTTP response the same
 * way unified.php's own handleNotify() used to.
 */
class PayplugNotifyModuleFrontController extends ModuleFrontController
{
    use ServiceGetter;

    private $dependencies;

    public function __construct()
    {
        parent::__construct();
        $this->dependencies = new DependenciesClass();
    }

    public function postProcess()
    {
        $result = $this->getService('payplug.action.operation')->dispatchAction('notify', []);

        // dispatchAction()'s own try/catch (generic to every action) returns
        // {result: false, message: ...} on an unexpected exception, with no 'http_status' key —
        // fall back to 500 (retryable) in that case rather than a PHP notice on a missing key.
        $status = isset($result['http_status']) ? (int) $result['http_status'] : 500;

        http_response_code($status);

        exit;
    }
}
