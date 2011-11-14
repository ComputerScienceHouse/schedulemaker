<?php
////////////////////////////////////////////////////////////////////////////
// HTTP HELPER
//
// @file	inc/httphelper.php
// @descrip	This file provides functionality for performing HTTP post requests
//			instead of the normal fopen stuff.
// @author	Jonas John (http://www.jonasjohn.de/)
// @contrib	Ben Russell (benrr101@csh.rit.edu)
//
////////////////////////////////////////////////////////////////////////////

// REQUIRED FILES //////////////////////////////////////////////////////////
require_once "config.php";
require_once "stringFunctions.php";

class courseListHandle {
	// MEMBER VARIABLES ////////////////////////////////////////////////

	// CONSTRUCTOR /////////////////////////////////////////////////////
	function __construct() {
		global $COOKIE_STORE;

		// Create a cURL handle that will initalize the connection
		// with SIS
		$handle = curl_init();

		// Set all the options -- this includes setting up a cookie jar
		curl_setopt($handle, CURLOPT_URL, "https://sis.rit.edu/info/info.do?init=openCourses");
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($handle, CURLOPT_COOKIEJAR, $COOKIE_STORE);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

		// Execute and return
		curl_exec($handle);
		curl_close($handle);
	}

	// MEMBER FUNCTIONS ////////////////////////////////////////////////
	function getCourseList($department) {
		global $COOKIE_STORE;

		// RETRIEVE THE LIST ///////////////////////////////////////////////
		$handle = curl_init();
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_COOKIEFILE, $COOKIE_STORE);
		curl_setopt($handle, CURLOPT_URL, "https://sis.rit.edu/info/getOpenCourseList.do?init=openCourses");
		curl_setopt($handle, CURLOPT_POST, true);
		curl_setopt($handle, CURLOPT_POSTFIELDS, "discipline={$department}");

		$result = curl_exec($handle);
		
		// Process the course list
		$matches = array();
		$pattern = "/<tr class=\"scheduleBoldText\">\s*<td>.*(\d\d\d)-(\d\d).*<\/td>\s*<td>\s*(.*)\s*<\/td>\s*<td>.*<\/td>\s*<td>.*<\/td>\s*<td>\s*(.*)\s*<\/td>\s*<td>\s*(.*)\s*<\/td>.*<\/tr>/msU";
		if(!preg_match_all($pattern, $result, $matches)) {
			return NULL;
		}

		// This will give us a crazy array that needs to be processed some more
		$courseList = array();
		for($i = 0; $i < count($matches[1]); $i++) {
			$courseList[$matches[1][$i] . $matches[2][$i]] = array(
				"title"      => upperCaseName(trimNBSP(html_entity_decode($matches[3][$i]))),
				"courseNum"  => trimNBSP(html_entity_decode($matches[1][$i])),
				"sectionNum" => trimNBSP(html_entity_decode($matches[2][$i])),
				"maxEnroll"  => trimNBSP(html_entity_decode($matches[4][$i])),
				"curEnroll"  => trimNBSP(html_entity_decode($matches[5][$i]))
			);
		}
		
		// Close up and return
		curl_close($handle);
		return $courseList;
	}

	function setQuarter($quarter) {
		global $COOKIE_STORE;

		// SET THE QUARTER /////////////////////////////////////////
		$handle = curl_init();
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_COOKIEFILE, $COOKIE_STORE);
		curl_setopt($handle, CURLOPT_URL, "https://sis.rit.edu/info/setTerm.do?source=open?init=openCourses");
		curl_setopt($handle, CURLOPT_POST, true);
		curl_setopt($handle, CURLOPT_POSTFIELDS, "term={$quarter}");

		$result = curl_exec($handle);

		curl_close($handle);
	}

	// DESTRUCTOR //////////////////////////////////////////////////////
	function __destruct() {
		global $COOKIE_STORE;

		// DELETE THE JAR //////////////////////////////////////////
		unlink($COOKIE_STORE);
	}
}
