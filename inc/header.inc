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

		<script type='text/javascript' src='./js/jquery.js'></script>
	</head>

	<body>
		<div id='oldBadge'>
			Report Issues: <a href='http://github.com/benrr101/schedulemaker'>Github</a><br />
			<a href='http://oldschedule.csh.rit.edu/'>Old Schedule Maker</a>
		</div>
		<div id='superContainer'>
		<div id='header'>
			<div class='logo'><a href='index.php'>ScheduleMaker</a></div>
			<? if(basename($_SERVER['SCRIPT_FILENAME']) == "roulette.php") { ?>
				<div class='nav underline'>
			<? } else { ?>
				<div class='nav'>
			<? } ?>
			<a href='roulette.php'>Course Roulette</a></div>
			<? if(basename($_SERVER['SCRIPT_FILENAME']) == "browse.php") { ?>
				<div class='nav underline'>
			<? } else { ?>
				<div class='nav'>
			<? } ?>
			<a href='browse.php'>Browse Courses</a></div>
			<? if(basename($_SERVER['SCRIPT_FILENAME']) == "generate.php") { ?>
				<div class='nav underline'>
			<? } else { ?>
				<div class='nav'>
			<? } ?>
			<a href='generate.php'>Build Schedules</a></div>
		</div>
		<div id="container">
