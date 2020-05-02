<?php
////////////////////////////////////////////////////////////////////////////
// CONFIGURATION
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	inc/config.php
// @descrip	configuration data for the system
////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////
// DATABASE CONFIG

// NOTE: THESE ARE ALL SAMPLE VALUES, REPLACE THEM WITH YOUR VALUES!
$COOKIE_STORE    = '/tmp/siscookies.txt';
$DATABASE_SERVER = 'yourdatabaseserver';
$DATABASE_USER   = 'yourusername';
$DATABASE_PASS   = 'yourpassword';
$DATABASE_DB     = 'yourdatabase';
$DUMPLOCATION    = 'pathtothedumpfile';
$GOOGLEANALYTICS = 'google_analytics_site_id';
$HTTPROOTADDRESS = 'http://path.to.your/schedule/';
$SERVER_TYPE     = '(development|production)';

////////////////////////////////////////////////////////////////////////////
// S3 CONFIG
$S3_SERVER = 'https://s3.csh.rit.edu';
$S3_KEY = '';
$S3_SECRET = '';
$S3_IMAGE_BUCKET = 'schedulemaker';

////////////////////////////////////////////////////////////////////////////
//// APP VERSIONS
$APP_CONFIG = json_decode(file_get_contents((empty($APP_ROOT)?"../":$APP_ROOT)."package.json"), true);
$APP_VERSION     = $APP_CONFIG['version'];
$JSSTATE_VERSION = $APP_CONFIG['config']['stateVersion'];

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
