<?php

namespace Connections;

use Aws\S3\S3Client;

class S3Client {

private $s3Client;

function __construct($S3_KEY, $S3_SECRET, $S3_SERVER, $S3_BUCKET)
{	
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
}

public function saveImage($im, $id) {
	//TODO: Error Handling
	$client->putObject([
    		'Bucket' => $S3_BUCKET,
    		'Key' => "{$id}.png",
    		'Body' => $im->getImageBlob()
	]);
}
}
