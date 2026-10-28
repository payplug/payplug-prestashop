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

namespace PayPlug\src\actions;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\classes\DependenciesClass;
use PayPlug\src\utilities\traits\ServiceGetter;
use PayplugUnifiedCore\Output\RefreshResult;

class ApplePayDomainAssociationAction
{
    use ServiceGetter;

    public const WELL_KNOWN_PATH = '/.well-known/apple-developer-merchantid-domain-association';

    private const FACTORY_SERVICE = 'payplug.unified.payment_service_factory';

    public $dependencies;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    /**
     * @description Check whether a request URI targets the Apple Pay domain association file
     *
     * @param mixed $request_uri
     *
     * @return bool
     */
    public static function isWellKnownRequest($request_uri = '')
    {
        if (!is_string($request_uri) || '' === $request_uri) {
            return false;
        }

        return self::WELL_KNOWN_PATH === parse_url($request_uri, PHP_URL_PATH);
    }

    /**
     * @description Build the response to the Apple Pay domain association request
     *
     * @param string $http_method
     * @param string $request_uri
     *
     * @return array{status: int, headers: string[], body: string}|null null when the request is not for this route
     */
    public function getRouteResponse($http_method = '', $request_uri = '')
    {
        if (!self::isWellKnownRequest($request_uri)) {
            return null;
        }

        $method = strtoupper((string) $http_method);
        if ('GET' !== $method && 'HEAD' !== $method) {
            return null;
        }

        try {
            $content = $this->getService(self::FACTORY_SERVICE)->createDomainAssociationService()->resolve();
        } catch (\Throwable $exception) {
            $this->logError('getRouteResponse', $exception);
            $content = null;
        }

        if (!is_string($content)) {
            return [
                'status' => 404,
                'headers' => ['Cache-Control: no-store'],
                'body' => '',
            ];
        }

        return [
            'status' => 200,
            'headers' => [
                'Content-Type: text/plain',
                'Cache-Control: public, max-age=3600',
                'Content-Length: ' . strlen($content),
                'X-Content-Type-Options: nosniff',
            ],
            'body' => 'HEAD' === $method ? '' : $content,
        ];
    }

    /**
     * @description Send the response to the Apple Pay domain association request
     *
     * @param string $http_method
     * @param string $request_uri
     *
     * @return bool true when a response was sent and the request must end there
     */
    public function serveAction($http_method = '', $request_uri = '')
    {
        try {
            $response = $this->getRouteResponse($http_method, $request_uri);
        } catch (\Throwable $exception) {
            $this->logError('serveAction', $exception);

            return false;
        }

        if (null === $response) {
            return false;
        }

        $this->discardOutputBuffers();
        $this->sendStatus($response['status']);
        foreach ($response['headers'] as $header) {
            $this->sendHeader($header);
        }
        echo $response['body'];

        return true;
    }

    /**
     * @description Download the file only when no valid copy is stored
     *
     * @return bool false when the synchronisation could not be attempted
     */
    public function ensureStoredAction()
    {
        try {
            $this->getService(self::FACTORY_SERVICE)->createDomainAssociationService()->ensureStored();
        } catch (\Throwable $exception) {
            $this->logError('ensureStoredAction', $exception);

            return false;
        }

        return true;
    }

    /**
     * @description Download the file again and store it when it changed
     *
     * @return array{success: bool, status: string, error: null|string} error is raw text, escape it before display
     */
    public function refreshAction()
    {
        try {
            $result = $this->getService(self::FACTORY_SERVICE)->createDomainAssociationService()->refresh();
            if (!$result instanceof RefreshResult) {
                throw new \UnexpectedValueException('The domain association service returned no refresh result.');
            }
        } catch (\Throwable $exception) {
            $this->logError('refreshAction', $exception);

            return [
                'success' => false,
                'status' => RefreshResult::STATUS_FAILED,
                'error' => $exception->getMessage(),
            ];
        }

        return [
            'success' => !$result->isFailed(),
            'status' => $result->status,
            'error' => $result->error,
        ];
    }

    /**
     * @description Describe the synchronisation state for the back office, last error HTML-escaped
     *
     * @return array{state: string, last_attempt_at: null|int, last_success_at: null|int, last_error: null|string, root_file_present: bool}
     */
    public function getDiagnosticsAction()
    {
        try {
            $factory = $this->getService(self::FACTORY_SERVICE);
            $synced = null !== $factory->createDomainAssociationService()->resolve();
            $status = $factory->createDomainAssociationStorage()->readStatus();
        } catch (\Throwable $exception) {
            $this->logError('getDiagnosticsAction', $exception);
            $synced = false;
            $status = [];
        }

        $last_error = isset($status['lastError']) && is_string($status['lastError'])
            ? htmlspecialchars($status['lastError'], ENT_QUOTES, 'UTF-8')
            : null;

        return [
            'state' => $synced ? 'synced' : 'not_synced',
            'last_attempt_at' => $this->getTimestamp($status, 'lastAttemptAt'),
            'last_success_at' => $this->getTimestamp($status, 'lastSuccessAt'),
            'last_error' => $last_error,
            'root_file_present' => $this->isRootFilePresent(),
        ];
    }

    /**
     * @description Get the directories that may be the web root of the shop
     *
     * @return string[]
     */
    protected function getRootCandidates()
    {
        $candidates = [];
        if (isset($_SERVER['DOCUMENT_ROOT']) && is_string($_SERVER['DOCUMENT_ROOT'])) {
            $candidates[] = $_SERVER['DOCUMENT_ROOT'];
        }
        $candidates[] = (string) $this->dependencies->getPlugin()->getConstant()->get('_PS_ROOT_DIR_');

        $candidates = array_map(function ($directory) {
            return rtrim($directory, '/\\');
        }, $candidates);

        return array_values(array_unique(array_filter($candidates, 'strlen')));
    }

    protected function discardOutputBuffers()
    {
        $level = $this->getOutputBufferLevel();
        while ($level > 0 && $this->endOutputBuffer()) {
            $remaining_level = $this->getOutputBufferLevel();
            if ($remaining_level >= $level) {
                return;
            }
            $level = $remaining_level;
        }
    }

    /**
     * @return int
     */
    protected function getOutputBufferLevel()
    {
        return ob_get_level();
    }

    /**
     * @description Discard the active output buffer, which fails on a buffer started as non-removable
     *
     * @return bool
     */
    protected function endOutputBuffer()
    {
        return @ob_end_clean();
    }

    /**
     * @param int $status
     */
    protected function sendStatus($status)
    {
        http_response_code($status);
    }

    /**
     * @param string $header
     */
    protected function sendHeader($header)
    {
        header($header);
    }

    /**
     * @description Check whether a file already sits at the web root, where the web server serves it first
     *
     * @return bool
     */
    private function isRootFilePresent()
    {
        try {
            foreach ($this->getRootCandidates() as $directory) {
                if (is_file($directory . self::WELL_KNOWN_PATH)) {
                    return true;
                }
            }
        } catch (\Throwable $exception) {
            $this->logError('isRootFilePresent', $exception);
        }

        return false;
    }

    /**
     * @param string $key
     *
     * @return int|null
     */
    private function getTimestamp(array $status, $key)
    {
        return isset($status[$key]) && is_int($status[$key]) ? $status[$key] : null;
    }

    /**
     * @param string $method
     * @param \Throwable $exception
     */
    private function logError($method, $exception)
    {
        try {
            $this->dependencies->getPlugin()->getLogger()->addLog(
                'ApplePayDomainAssociationAction::' . $method . ' - ' . get_class($exception) . ': ' . $exception->getMessage(),
                'error'
            );
        } catch (\Throwable $log_exception) {
            // Synchronisation runs during install and upgrade, which a logging failure must not abort.
        }
    }
}
