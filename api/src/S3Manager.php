<?php

namespace Connections;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Imagick;

class S3Manager
{

    private $s3Client;
    private $imageBucket;

    function __construct($S3_KEY, $S3_SECRET, $S3_SERVER, $S3_IMAGE_BUCKET)
    {
        $this->s3Client = new S3Client([
            'region' => '',
            'version' => '2006-03-01',
            'endpoint' => $S3_SERVER,
            'credentials' => [
                'key' => $S3_KEY,
                'secret' => $S3_SECRET
            ],
            'use_path_style_endpoint' => true
        ]);
        $this->imageBucket = $S3_IMAGE_BUCKET;

        //Creating S3 Bucket
        try {
            $this->s3Client->createBucket([
                'Bucket' => $S3_IMAGE_BUCKET,
            ]);
        } catch (AwsException $e) {
            // output error message if fails
            echo $e->getMessage();
            echo "\n";
        }
    }

    public function saveImage(Imagick $im, $id)
    {
        //TODO: Error Handling
        $this->s3Client->putObject([
            'Bucket' => $this->imageBucket,
            'Key' => "{$id}.png",
            'Body' => $im->getImageBlob()
        ]);
    }
}
