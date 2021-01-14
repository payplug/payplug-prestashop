<?php

// get PHP Payplug Lib
$files = array(
    '/../lib/init.php',
    '/tests/mock/PaymentMock.php',
    '/tests/mock/PayPlugCacheMock.php',
);

foreach ($files as $file) {
    $path = dirname(__FILE__) . $file;
    if (file_exists($path)) {
        require_once($path);
    }
}
