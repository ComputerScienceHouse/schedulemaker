<?php

use Aws\S3\S3Client;

// Bring in the config data
if (file_exists(dirname(__FILE__) . "/config.php")) {
    require_once dirname(__FILE__) . "/config.php";
} else {
    require_once dirname(__FILE__) . "/config.env.php";
}

require __DIR__.'/vendor/autoload.php';

global $S3_KEY, $S3_SECRET, $S3_SERVER;

$client = new S3Client([
    'region' => '',
    'version' => '2006-03-01',
    'endpoint' => $S3_SERVER,
    'credentials' => [
        'key' => $S3_KEY,
        'secret' => $S3_SECRET
    ],
    'use_path_style_endpoint' => true
]);
