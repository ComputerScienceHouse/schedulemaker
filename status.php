<?php
////////////////////////////////////////////////////////////////////////////
// STATUS
//
// @file	status.php
// @descrip	IT ALWAYS WORKS!!!
// @author	Ben Russell (benrr101@csh.rit.edu)
////////////////////////////////////////////////////////////////////////////

// FUNCTIONS ///////////////////////////////////////////////////////////////
function timeElapsed($time) {
	// Initialize the return string
	$return = "";

	// Divide off days
	$days = floor($time / (60 * 60 * 24));
	if($days) {
		$return .= "{$days} days ";
		$time -= $days * 60 * 60 * 24;
	}

	// Divide off hours
	$hours = floor($time / (60 * 60));
	if($hours) {
		$return .= "{$hours}:";
		$time -= $hours * 60 * 60;
	} else {
		$return .= "00:";
	}

	// Divide off minutes
	$mins = floor($time / 60);
	if($mins) {
		$return .= "{$mins}:";
		$time -= $mins * 60;
	} else {
		$return .= "00:";
	}

	// Divide off seconds
	$return .= str_pad($time, 2, "0", STR_PAD_LEFT);

	return $return;
}

// REQUIRED FILES //////////////////////////////////////////////////////////
require_once("inc/databaseConn.php");

// MAIN EXECUTION //////////////////////////////////////////////////////////
// Look up the last 20 scrape reports and store into an array
$query = "SELECT * FROM scrapelog ORDER BY timeStarted DESC LIMIT 20";
$result = mysql_query($query);
$lastLogs = array();
while($row = mysql_fetch_assoc($result)) {
	$lastLogs[] = $row;
}

require "inc/header.inc";
?>
<h1>Scraper Status</h1>

<div class='subContainer'>
	<h2>Scraper Last Ran: <?= ((count($lastLogs)) ? date("m/d/y h:ia", $lastLogs[0]['timeStarted']) : "Never") ?></h2>

	<h2>Last 20 Scrape Reports</h2>
	<table id='scraperStatus'>
		<tr class='separated'>
			<th>Scrape Started</th>
			<th>Scrape Finished</th>
			<th>Time Elapsed</th>
			<th>Courses Added</th>
			<th>Courses Updated</th>
			<th>Sections Added</th>
			<th>Sections Updated</th>
			<th>Failures</th>
		</tr>
		<? 
		if(!count($lastLogs)) {
			// No reports here
			?><tr><td colspan='7'>No Logs Exist</td></tr><?
		} else {
			foreach($lastLogs as $log) { ?>
			<tr>
				<td><?= date('m/d/y h:ia', $log['timeStarted']) ?></td>
				<td><?= date('m/d/y h:ia', $log['timeEnded']) ?></td>
				<td><?= timeElapsed($log['timeEnded'] - $log['timeStarted']) ?></td>
				<td><?= $log['coursesAdded'] ?></td>
				<td><?= $log['coursesUpdated'] ?></td>
				<td><?= $log['sectionsAdded'] ?></td>
				<td><?= $log['sectionsUpdated'] ?></td>
				<? if($log['failures'] > 0) { ?>
					<td class='failures'><?= $log['failures'] ?></td>
				<? } else { ?>
					<td>0</td>
				<? } ?>
			</tr>
			<? }
		} ?> 
	</table>
</div>
<?
require "inc/footer.inc";
?>
