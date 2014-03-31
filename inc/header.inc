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
		<script type="text/javascript">
			var _gaq = _gaq || [];
			_gaq.push(['_setAccount', '<?= $GOOGLEANALYTICS ?>']);
			_gaq.push(['_trackPageview']);

			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();
		</script>
	</head>

	<body>
		<?php if($SERVER_TYPE != 'production') { ?>
			<div id='devNotice' class='devNotice'>
				<span style='bold'>Notice:</span>
				This is the development server for ScheduleMaker.
				All information available here is testing material and comes with no guarantee of accuracy.
				The production server can be found at:
				<a href='http://schedule.csh.rit.edu/'>http://schedule.csh.rit.edu</a>
			</div>
		<? } ?>
		<div id='devNotice' class='devNotice' style="font-size:16px;background-color:#2B4E69; margin-bottom:15px;">
			<span style="font-weight:bold">Notice:</span>
			This version of ScheduleMaker will be retired before April 12th, 2014 in preparation for fall registration. All of your saved schedules will still be available. <br>
			If you want to try out the new version <em>today</em>, check out <a style="color: white"  target="_blank" href='http://schedule-dev.csh.rit.edu/'>http://schedule-dev.csh.rit.edu</a> which is still in beta. It has mobile support, RateMyProfessors integration, and a whole lot more! 
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
