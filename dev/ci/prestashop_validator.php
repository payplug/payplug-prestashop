<?php

require_once __DIR__ . '/../../vendor/autoload.php';

const VALIDATOR_URL = 'https://validator.prestashop.com';
const VALIDATOR_API_KEY = '0d9124fa19c5e89eb18b7137b0f7201b';

$client = new \GuzzleHttp\Client([
    'base_uri' => VALIDATOR_URL,
    'headers' => ['User-Agent' => null],
    //'http_errors' => false,
]);

// As example,
//$moduleName = 'payplug.zip';
$moduleName = 'blockreassurance.zip';
//$modulePath = 'https://major-scarcely-minnow.ngrok-free.app/modules/payplug/' . $moduleName;
$modulePath = $_GET['modulePath'];
echo 'module path = ' . $modulePath;

// Call validator
try {
    $datas = [
        'multipart' => [
            [
                'name' => 'archive',
                'contents' => file_get_contents($modulePath),
                'filename' => $moduleName,
            ],
            // PrestaShop 1.7 compliant
            [
                'name' => 'compatibility_1_7',
                'contents' => true,
            ],
            [
                'name' => 'key',
                'contents' => '0d9124fa19c5e89eb18b7137b0f7201b',
            ],
        ],
    ];
    $response = $client->post('/api/modules', $datas);

    $stdResponse = json_decode($response->getBody()->getContents(), true);
} catch (\Exception $e) {
    echo '<pre>';
    var_dump('on passe dans le catch');
    echo '</pre>';
    echo '<pre>';
    var_dump($e);
    echo '</pre>';
    exit;
}

// Results are an associate array with differents categories as Details, Errors, Optimizations, ...
echo '<pre>';
var_dump($stdResponse);
echo '</pre>';
