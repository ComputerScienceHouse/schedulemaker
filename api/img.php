<?php
////////////////////////////////////////////////////////////////////////////
// SCHEDULE LOOKUP
//
// @author	Devin Matte (matted@csh.rit.edu)
//
// @file	img.php
// @descrip	Loads up the requested img from s3
////////////////////////////////////////////////////////////////////////////

// REQUIRED FILES //////////////////////////////////////////////////////////
if (file_exists('../inc/config.php')) {
    include_once "../inc/config.php";
} else {
    include_once "../inc/config.env.php";
}

require_once '../vendor/autoload.php';
require_once '../api/src/S3Manager.php';

// IMPORTS
use Connections\S3Manager;

// GLOBALS /////////////////////////////////////////////////////////////////
global $s3ImageManager;
$s3ImageManager = new S3Manager($S3_KEY, $S3_SECRET, $S3_SERVER, $S3_IMAGE_BUCKET);

$path = explode('/', $_SERVER['REQUEST_URI']);
$id = substr($path[3], 0, -4);

header("Content-Type: image/png");
echo $s3ImageManager->returnImage($id);
