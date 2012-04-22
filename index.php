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
<div class='notice'>
	<span style='font-weight:bold'>Notice:</span>
	Course information for Fall 2012/2013 is not yet available. We are currently
	working hard to get this information as soon as possible.
</div>
<div id="mainMenu">
	<div class='navItem'>
		<a href='generate.php'><img src='img/calendar.png' alt='Make a Schedule'></a>
		<div><a href='generate.php'>Make a Schedule</a></div>
	</div>
	<div class='navItem'>
		<a href='browse.php'><img src='img/browse.png' alt='Browse Courses'></a>
		<div><a href='browse.php'>Browse Courses</a></div>
	</div>
	<div class='navItem'>
		<a href='roulette.php'><img src='img/roulette.png' alt='Course Roulette'></a>
		<div><a href='roulette.php'>Course Roulette</a></div>
	</div>
</div>
<? require "./inc/footer.inc"; ?>
