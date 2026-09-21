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
use PayPlug\src\actions\UnifiedOrderCreator;
use PayPlug\src\utilities\traits\ServiceGetter;

class PayplugUnifiedModuleFrontController extends ModuleFrontController
{
    use ServiceGetter;

    private $dependencies;
    private $toolsAdapter;

    public function __construct()
    {
        parent::__construct();
        $this->dependencies = new DependenciesClass();
        $this->toolsAdapter = $this->dependencies->getPlugin()->getTools();
    }

    public function postProcess()
    {
        $action = (string) $this->toolsAdapter->tool('getValue', 'action');
        $params = [
            'hfToken' => $this->toolsAdapter->tool('getValue', 'hfToken'),
            'selectedBrand' => $this->toolsAdapter->tool('getValue', 'selectedBrand'),
            'save_card' => $this->toolsAdapter->tool('getValue', 'save_card'),
            'cardholder' => $this->toolsAdapter->tool('getValue', 'cardholder'),
            'cardholderName' => $this->toolsAdapter->tool('getValue', 'cardholderName'),
            'id_cart' => $this->toolsAdapter->tool('getValue', 'id_cart'),
        ];

        switch ($action) {
            case 'create':
                $this->handleCreate($params);

                break;

            case 'challenge':
                $this->handleChallenge($params);

                break;

            case 'return':
                $this->handleReturn($params);

                break;

            default:
                header('Content-Type: application/json');

                exit(json_encode(['result' => false, 'message' => 'Unknown or missing action']));
        }
    }

    private function handleCreate($params)
    {
        $result = $this->getService('payplug.action.operation')->dispatchAction('create', $params);

        exit(json_encode($result));
    }

    private function handleChallenge($params)
    {
        $result = $this->getService('payplug.action.operation')->dispatchAction('challenge', $params);

        if (!$result['result']) {
            $this->renderClientRedirect(UnifiedOrderCreator::errorUrl($this->dependencies));

            return;
        }

        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Referrer-Policy: no-referrer');
        echo $result['html'];

        exit;
    }

    private function handleReturn($params)
    {
        $result = $this->getService('payplug.action.operation')->dispatchAction('return', $params);

        $redirect_url = isset($result['redirect_url']) ? $result['redirect_url'] : UnifiedOrderCreator::errorUrl($this->dependencies);

        if ($this->wantsJsonResponse()) {
            header('Content-Type: application/json');

            exit(json_encode([
                'result' => (bool) (isset($result['result']) ? $result['result'] : false),
                'redirect_url' => $redirect_url,
            ]));
        }

        $this->renderClientRedirect($redirect_url);
    }

    private function wantsJsonResponse()
    {
        $accept = isset($_SERVER['HTTP_ACCEPT']) ? (string) $_SERVER['HTTP_ACCEPT'] : '';
        $ajax = $this->toolsAdapter->tool('getValue', 'ajax');

        return false !== strpos($accept, 'application/json') || !empty($ajax);
    }

    private function renderClientRedirect($url)
    {
        $safe_url = htmlspecialchars((string) $url, ENT_QUOTES, 'UTF-8');

        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta http-equiv="refresh" content="0;url=' . $safe_url . '"><script>window.location.replace(' . json_encode((string) $url) . ');</script></head><body></body></html>';

        exit;
    }
}
