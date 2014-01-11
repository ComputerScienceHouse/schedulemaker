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
<div class="alert alert-success alert-dismissable hidden-xs">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
Welcome to the new ScheduleMaker! This version bring lots of new functionality to the website, including full mobile support, a brand-new interface, and a new ridiculously easy-to-use course browsing and selection system. Enjoy! 
</div>
<div id="mainMenu" class="row">
	<div class="col-xs-4">
		<div class='navItem'>
			<a href='generate.php'><i class="fa fa-calendar"></i></a>
			<div><a href='generate.php'>Make a Schedule</a></div>
		</div>
	</div>
	<div class="col-xs-4">
		<div class='navItem'>
			<a href='browse.php'><i class="fa fa-search"></i></a>
			<div><a href='browse.php'>Browse Courses</a></div>
		</div>
	</div>
	<div class="col-xs-4">
		<div class='navItem'>
			<a href='roulette.php'><i class="fa fa-bolt"></i></a>
			<div><a href='roulette.php'>Course Roulette</a></div>
		</div>
	</div>
</div>
<? require "./inc/footer.inc"; ?>
