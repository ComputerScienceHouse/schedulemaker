<?php
////////////////////////////////////////////////////////////////////////////
// SCHEDULE MAKER
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	index.php
// @descrip	Index page for schedule maker. Displays a static home page with
//			links to everything.
////////////////////////////////////////////////////////////////////////////

// If the link is to ?s=yadayada Redirect to the schedule page
if(isset($_GET['s'])) {
	require_once("./inc/config.php");
	header("Location: {$HTTPROOTADDRESS}schedule.php?mode=old&id={$_GET['s']}");
	die();
} 

require "./inc/header.inc";
?>
<h1>Schedule Maker</h1>
<table id="mainMenu">
	<tr>
		<td><!-- image goes here --></td>
		<td><!-- image goes here --></td>
		<td><!-- image goes here --></td>
	</tr>
	<tr>
		<td><a href='generate.php'>Make a Schedule</a></td>
		<td><a href='browse.php'>Browse Courses</a></td>
		<td><a href='roulette.php'>Course Roulette</a></td>
	</tr>
</table>
<h2>Project Progress</h2>
<ul>
	<li>Index Page/Styling - <span class='b'>Partial</span></li>
	<li>Schedule Form - <span class='c'>COMPLETE (08-12-11)</span></li>
	<li>Schedule Generator - <span class='c'>COMPLETE (09-01-11)</span></li>
	<li>Course Roulette - <span class='c'>COMPLETE (08-06-11)</span></li>
	<li>Cronjob Status - Not complete</li>
	<li>AJAX Integration - <span class='c'>COMPLETE</span></li>
	<li>Courses DB - <span class='c'>COMPLETE (08-03-11)</span></li>
	<li>Saved Schedule Lookup - <span class='c'>COMPLETE (10-07-11)</span></li>
	<li>Saved Schedule Cleaner - <span class='c'>COMPLETE (10-07-11)</span></li>
	<li>Schedule Output - <span class='c'>COMPLETE (09-17-11)</span></li>
	<li>Social Media Sharing - <span class='c'>COMPLETE (09-17-11)</span></li>
	<li>iCal Exporting - <span class='b'>Partial</span></li>
	<li>Browse Courses - <span class='c'>COMPLETE (10-08-11)</span></li>
	<li>Scraper Migration - Not complete</li>
	<li>Migrator: Courses - <span class='c'>COMPLETE (08-03-11)</span></li>
	<li>Migrator: Saves Schedules - <span class='c'>COMPLETE (08-04-11)</span></li>
</ul>
<? require "./inc/footer.inc"; ?>
