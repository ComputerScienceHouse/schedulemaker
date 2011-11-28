<?php
// REQUIRED FILES
require_once('./inc/config.php');
require_once('./inc/databaseConn.php');
require_once('./inc/timeFunctions.php');
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?= (!empty($TITLE)) ? $TITLE : "Schedule Maker" ?></title>
		
		<link href='./inc/global.css' rel='stylesheet' type='text/css' />
		<meta property=og:image content="img/csh_print.png"/>

		<script type='text/javascript' src='./js/jquery.js'></script>
	</head>

	<body>
		<div id='superContainer'>
		<div id='header'>
			<div class='logo'><a href='index.php'>ScheduleMaker</a></div>
			<div class='nav'><a href='roulette.php'>Course Roulette</a></div>
			<div class='nav'><a href='browse.php'>Browse Courses</a></div>
			<div class='nav'><a href='generate.php'>Build Schedules</a></div>
		</div>
		<div id="container">
