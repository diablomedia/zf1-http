<?php
declare(strict_types = 1);

$config = array();

if (version_compare(PHP_VERSION, '8.0', '>=')) {
    // Change of signature in PHP 8.0
    $config['parameters']['ignoreErrors'][] = array(
        'message' => '#Property Zend_Http_Client_Adapter_Curl::\$_curl \(resource\|null\) does not accept CurlHandle\.#',
        'path'    => __DIR__ . '/src/Zend/Http/Client/Adapter/Curl.php',
        'count'   => 1,
    );
}

return $config;
