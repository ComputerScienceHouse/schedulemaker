<?php

$get_env = function (&$var, $default = null) {
    return !empty($var) ? $var : $default;
};

////////////////////////////////////////////////////////////////////////////
// DATABASE CONFIG

$COOKIE_STORE    = $get_env(getenv("COOKIE_STORE"), '/tmp/siscookies.txt');
$DATABASE_SERVER = $get_env(getenv("DATABASE_SERVER"), 'mysql.csh.rit.edu');
$DATABASE_USER   = $get_env(getenv("DATABASE_USER"), '');
$DATABASE_PASS   = $get_env(getenv("DATABASE_PASS"), '');
$DATABASE_DB     = $get_env(getenv("DATABASE_DB"), '');
$DUMPCLASSES     = $get_env(getenv("DUMPCLASSES"), '/mnt/share/cshclass.dat');
$DUMPCLASSATTR   = $get_env(getenv("DUMPCLASSATTR"), '/mnt/share/cshattrib.dat');
$DUMPINSTRUCT    = $get_env(getenv("DUMPINSTRUCT"), '/mnt/share/cshinstr.dat');
$DUMPMEETING     = $get_env(getenv("DUMPMEETING"), '/mnt/share/cshmtgpat.dat');
$DUMPNOTES       = $get_env(getenv("DUMPNOTES"), '/mnt/share/cshnotes.dat');

$HTTPROOTADDRESS = $get_env(getenv("HTTPROOTADDRESS"), 'http://schedule.csh.rit.edu/');
$SERVER_TYPE     = $get_env(getenv("SERVER_TYPE"), 'development');

////////////////////////////////////////////////////////////////////////////
// S3 CONFIG
$S3_SERVER = $get_env(getenv("S3_SERVER"), 'https://s3.csh.rit.edu');
$S3_KEY = $get_env(getenv("S3_KEY"), '');
$S3_SECRET = $get_env(getenv("S3_SECRET"), '');
$S3_IMAGE_BUCKET = $get_env(getenv("S3_IMAGE_BUCKET"), 'schedulemaker');

////////////////////////////////////////////////////////////////////////////
//// APP VERSIONS
$APP_CONFIG = json_decode(file_get_contents((empty($APP_ROOT)?"../":$APP_ROOT)."package.json"), true);
$APP_VERSION     = $APP_CONFIG['version'];
$JSSTATE_VERSION = $APP_CONFIG['config']['stateVersion'];


////////////////////////////////////////////////////////////////////////////
////// GOOGLE ANALYTICS
////
$GOOGLEANALYTICS = ($SERVER_TYPE == 'production')?
    $get_env(getenv("GOOGLEANALYTICS1"), ''):
    $get_env(getenv("GOOGLEANALYTICS2"), '');

////////////////////////////////////////////////////////////////////////////
////// DATADOG RUM ANALYTICS
////

$RUM_CLIENT_TOKEN = $get_env(getenv("RUM_CLIENT_TOKEN"), '');
$RUM_APPLICATION_ID = $get_env(getenv("RUM_APPLICATION_ID"), '');

////////////////////////////////////////////////////////////////////////////
// WORK AROUNDS
if (get_magic_quotes_gpc()) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}
////////////////////////////////////////////////////////////////////////////
// CALCULATIONS

// Calculate the current quarter
switch(date('n')) {
    case 2:
    case 3:
        $CURRENT_QUARTER = date("Y")-1 . '3';		// Point them to the spring
        break;
    case 4:
    case 5:
    case 6:
    case 7:
    case 8:
    case 9:
        $CURRENT_QUARTER = date("Y") . '1';			// Point them to the fall
        break;
    case 10:
    case 11:
    case 12:
    case 1:
        $CURRENT_QUARTER = date("Y") . '2';		// Point them to the summer
        break;
}

