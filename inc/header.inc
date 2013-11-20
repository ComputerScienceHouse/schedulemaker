<?php
// REQUIRED FILES
require_once('./inc/config.php');
require_once('./inc/databaseConn.php');
require_once('./inc/timeFunctions.php');
?>
<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#">
	<head>
		<title><?= (!empty($TITLE)) ? $TITLE . " " : "" ?>Schedule Maker</title>

        <meta property="og:title" content="<?= (!empty($TITLE)) ? $TITLE . " " : "" ?>Schedule Maker" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="http://<?= $_SERVER['HTTP_HOST'] ?><?= $_SERVER['REQUEST_URI'] ?>" />
        <? if(!empty($IMGURL)) { ?>
        <meta property="og:image" content="<?= $IMGURL ?>" />
        <? } else { ?>
        <meta property="og:image" content="<?= $HTTPROOTADDRESS ?>img/csh_og.png" />
        <? } ?>

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
