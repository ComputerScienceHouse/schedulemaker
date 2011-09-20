<?php
////////////////////////////////////////////////////////////////////////////
// TIME FUNCTIONS
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	inc/timeFunctions.php
// @descrip	Functions for handling time conversions and the like.
////////////////////////////////////////////////////////////////////////////

/**
 * Generates a drop down list of days. Option values are 3-letter day names.
 * @param	string	$fieldname		The name of the select tag, also the id
 * @param	string	$selectedDay	The day that is preselected in the dropdown.
 *									defaults to Monday
 * @param	string	$numeric		Whether to make the values numeric or 3char
 * @return	string	The code to render the select tag and it's child options
 */
function getDayField($fieldname, $selectedDay, $numeric = false) {
	// Array of Days.
	if(!$numeric) {
		$days = array("Mon" => "Monday", "Tue" => "Tuesday", "Wed" => "Wednesday", "Thu" => "Thursday", "Fri" => "Friday", "Sat" => "Saturday", "Sun" => "Sunday");
	} else {
		$days = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
	}
	
	// Generate a bunch of code
	$result = "<select id='{$fieldname}' name='{$fieldname}'>";
	foreach($days as $dayCode => $dayName) {
		$result .= "<option value='{$dayCode}'" . (($dayCode == $selectedDay) ? " selected='selected'" : "" ) . ">{$dayName}</option>";
	}
	$result .= "</select>";
	
	return $result;
}

/**
 * Generates a drop down list of every hour and half hour.
 * @param	string	$fieldname		The name of the select tag, also the id
 * @param	int		$selectedTime	The time that is preselected. Defaults to
 *									noon
 * @param	bool	$twelve			Whether to use a 24-hr or 12-hr clock
 *									defaults to 12-hr
 */
function getTimeField($fieldname, $selectedTime = "720", $twelve = true) {
	// Generate a list of times
	$times = array();
	
	// Start at 0 and add 30 for every hour and every half hour
	for($i = 0; $i <= 1440; $i += 30) {
		$times[] = $i;
	}
	
	// Now turn it into a bunch of code
	$result = "<select id='{$fieldname}' name='{$fieldname}'>";
	foreach($times as $time) {
		$result .= "<option value='{$time}'" . (($time == $selectedTime) ? " selected='selected'" : "") . ">" . translateTime($time, $twelve) . "</option>";
	}
	$result .= "</select>";

	return $result;
}		

/**
 * Swaps a day's format. If it's numerical, you get a 3 letter string. If it's
 * a 3(or 4) letter string, you get a number back.
 * If it can't figure it out, you'll get Sunday.
 * @param	mixed	$day	The day to translate
 * @return	mixed	A numeric representation if $day is a string, a string
 *					representation if $day is a number
 */
function translateDay($day) {
	if(is_numeric($day)) {
		switch($day) {
			case 1:
				$day = 'Mon';
				break;
			case 2:
				$day = 'Tue';
				break;
			case 3:
				$day = 'Wed';
				break;
			case 4:
				$day = 'Thur';
				break;
			case 5:
				$day = 'Fri';
				break;
			case 6:
				$day = 'Sat';
				break;
			case 7:
			default:
				$day = 'Sun';
				break;
		}
	} else {
		switch($day) {
			case 'Mon':
				$day = 1;
				break;
			case 'Tue':
				$day = 2;
				break;
			case 'Wed':
				$day = 3;
				break;
			case 'Thur':
			case 'Thu':
				$day = 4;
				break;
			case 'Fri':
				$day = 5;
				break;
			case 'Sat':
				$day = 6;
				break;
			case 'Sun':
			default:
				$day = 0;
				break;
		}
	}
	return $day;
}

/**
 * Translates from numeric, minute time to the user friendly hr:mn AM/PM
 * @param	int		$time	The time to translate
 * @param	bool	$twelve	Whether to use a 12-hr or 24-hr clock. Defaults
 *							to a 12-hr clock
 * @throws	Exception		Thrown if the time provided is not numeric.
 * @return	string			The time translated
 */
function translateTime($time, $twelve = true) {
	if(is_numeric($time)) {
		// Generate a 12-hour time if it is requested
		if($twelve) {
			if($time >= 780) {
				// It's PM
				$time -= 720;
				$twelve = " pm";
			} elseif($time < 60) {
				// It's 12AM -- NOT 00am
				$time += 720;
				$twelve = " am";
			} elseif($time >= 720) {
				// It's 12PM -- NOT 12am
				$twelve = " pm";
			} else {
				// It's AM
				$twelve = " am";
			}
		} else {
			$twelve = "";
		}
		
		// Calculate the hour and the minute
		$hr = floor($time / 60);
		$mn = str_pad($time % 60, 2, "0");
		return "{$hr}:{$mn}{$twelve}";		
	} else {
		throw new Exception("FUCK OFF! IT's NOT IMPLEMENTED YET. IT'S 2FUCKINGAM AND I'M TIRED AND CRANKY. SO FUCK OFF!");
	}
}


