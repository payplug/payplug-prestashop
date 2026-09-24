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

namespace PayPlug\src\utilities\services;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\src\utilities\traits\DotenvGetter;
use PayplugUnifiedCore\Contracts\IOAuthHttpClient;
use PayplugUnifiedCore\Contracts\IUnifiedApiHttpClient;

class CurlHttpClient implements IOAuthHttpClient, IUnifiedApiHttpClient
{
    use DotenvGetter;

    public function post(string $url, array $formParams, array $headers = []): array
    {
        return $this->send(
            $url,
            http_build_query($formParams),
            $this->headerLines(array_merge(['Content-Type' => 'application/x-www-form-urlencoded'], $headers))
        );
    }

    public function get(string $url, array $headers = []): array
    {
        return $this->send($url, null, $this->headerLines($headers));
    }

    public function postJson(string $url, array $body, array $headers = []): array
    {
        return $this->send(
            $url,
            (string) json_encode($body),
            $this->headerLines(array_merge(['Content-Type' => 'application/json'], $headers))
        );
    }

    /**
     * @param array<int, string> $headerLines
     *
     * @return array{status: int, body: string}
     */
    protected function send(string $url, ?string $body, array $headerLines): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        // Secure by default: this client talks to PayPlug's own API/identity-provider endpoints
        // (OAuth2 credentials, payment operations) - verification is only disabled by an explicit
        // '0' in payplugroutes/.env (e.g. for a local sandbox behind a self-signed cert), never
        // just because the var is unset.
        $sslVerifyPeer = $this->isSslVerificationEnabled('SSL_VERIFYPEER');
        $sslVerifyHost = $this->isSslVerificationEnabled('SSL_VERIFYHOST');

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerifyPeer);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerifyHost ? 2 : 0);

        if (null !== $body) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response_body = curl_exec($ch);

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (false === $response_body) {
            $error = curl_error($ch);
            curl_close($ch);

            return [
                'status' => 0,
                'body' => 'cURL transport error: ' . $error,
            ];
        }

        curl_close($ch);

        return [
            'status' => $status,
            'body' => (string) $response_body,
        ];
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    protected function isSslVerificationEnabled($key)
    {
        return '0' !== $this->getEnv($key);
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<int, string>
     */
    private function headerLines(array $headers): array
    {
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }

        return $lines;
    }
}
