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

// REQUIRED FILES
$APP_ROOT = "./";
if (file_exists('./inc/config.php')) {
    require_once('./inc/config.php');
} else {
    require_once('./inc/config.env.php');
}
require_once('./inc/databaseConn.php');
require_once('./inc/timeFunctions.php');

// Strips the 'http:' from the root address so it can work on SSL.
$ASSETROOTADDRESS = substr($HTTPROOTADDRESS, 5);

// HACK FOR OPEN-GRAPH TAGS, I KNOW, THIS IS TERRIBLE
$path = explode('/', $_SERVER['REQUEST_URI']);
if ($path[1] == 'schedule') {
	$id = (empty($path[2]))? '': hexdec($path[2]);
	if(!empty($id)) {

		// We are making the assumption that only new schedules with images
		// will be shared. Due relative requires in api/schedule.php, I cannot
		// check to see if the schedule has an image. #WONTFIX #WORKS4ME
		$IMGURL = "{$HTTPROOTADDRESS}img/schedules/{$id}.png";
	}
}
//OLD header.inc
?>
<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#" ng-app="sm" lang="en">
	<head>
		<title><?= (!empty($TITLE)) ? $TITLE . " - " : "" ?>Schedule Maker</title>

		<!-- META DATA -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="theme-color" content="#2C3E50">
        <link rel="manifest" href="manifest.json">

		<!-- STYLE SHEETS -->
		<link rel="stylesheet" href="//brick.a.ssl.fastly.net/Roboto:300,700">
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="<?=$ASSETROOTADDRESS?>assets/prod/<?=$APP_VERSION?>/modules/sm/dist.min.css">

		<!-- OPEN GRAPH TAGS -->
		<meta name="twitter:card" content="photo">
        <meta property="og:title" content="<?= (!empty($TITLE)) ? $TITLE . " - " : "" ?>ScheduleMaker" />
        <meta property="og:type" content="website" />
        <meta property="og:description" content="CSH ScheduleMaker makes picking your RIT class schedule easy! Preview all permutations of your schedule, browse available courses, and search for any course, all with ScheduleMaker.">
        <meta property="og:url" content="http://<?= $_SERVER['HTTP_HOST'] ?><?= $_SERVER['REQUEST_URI'] ?>" />
        <? if(!empty($IMGURL)) { ?>
        <meta property="og:image" content="<?= $IMGURL ?>" />
        <? } else { ?>
        <meta property="og:image" content="<?= $HTTPROOTADDRESS ?>img/csh_og.png">
        <? } ?>
	</head>
	<body ng-init="defaultTerm = '<?=$CURRENT_QUARTER?>'; stateVersion = <?=$JSSTATE_VERSION?>; termList=<?=htmlspecialchars(json_encode(getTerms()))?>; globalUI = {layoutClass:'default'};" ng-class="globalUI.layoutClass">
		<div id="superContainer" ng-controller="AppController">
			<header class="main navbar navbar-fixed-top navbar-default ng-scope">
	            <div class="container">
	                <div class="navbar-header">
	                    <button type="button" class="navbar-toggle btn btn-default" data-toggle="collapse" data-target=".navbar-ex1-collapse">
	                        <span class="sr-only">Toggle navigation</span>
	                        <span class="icon-bar"></span>
	                        <span class="icon-bar"></span>
	                        <span class="icon-bar"></span>
	                    </button>
	                    <a class="navbar-brand" ui-sref="index">Schedule<strong>Maker</strong></a>
	                </div>
	                <div class="collapse navbar-collapse navbar-right navbar-ex1-collapse" nav-close-on-mobile>
	                    <ul class="nav navbar-nav">
	                        <li ui-sref-active="active"><a ui-sref="generate"><i class="fa fa-calendar-o fa-fw"></i> Make a Schedule</a></li>
	                        <li ui-sref-active="active"><a ui-sref="browse"><i class="fa fa-list fa-fw"></i> Browse Courses</a></li>
	                        <li ui-sref-active="active"><a ui-sref="search"><i class="fa fa-search fa-fw"></i> Search Courses</a></li>
	                    </ul>
	                </div>
	            </div>
			</header>
			<div id="container" ng-cloak>
				<div ui-view autoscroll="false"></div>
			</div>
			<footer class="main default">
				<div class="container">
					<div class="csh"><a target="_blank" rel="noopener" href="http://www.csh.rit.edu/"><img width="90" src="<?=$ASSETROOTADDRESS?>img/csh_logo_square.svg" alt="CSH" /></a></div>
					<a target="_blank" rel="noopener" href="https://github.com/ComputerScienceHouse/schedulemaker">Version: <?=$APP_VERSION?></a> | <a ui-sref="help">Help</a> | <a href="/status">Status</a> | <a target="_blank" rel="noopener" href="https://github.com/ComputerScienceHouse/schedulemaker/issues">Report Issues</a>
					<div>
						Development v3.1: Devin Matte (matted at csh.rit.edu)<br>
						Development v3: Ben Grawi (bgrawi at csh.rit.edu)<br>
						Development v2: Ben Russell (benrr101 at csh.rit.edu)<br>
						Idea: John Resig (phytar at csh.rit.edu)<br>
						Hosting: <a href="http://www.csh.rit.edu/">Computer Science House</a><br>
					</div>
				</div>
			</footer>
			<footer class="main print">
				Made Using <a href='<?= $HTTPROOTADDRESS ?>'>CSH ScheduleMaker</a>
				<a target="_blank" rel="noopener" href="http://www.csh.rit.edu/"><img height="25" src="/img/csh_print.png"></a>
			</footer>
		</div>
		<!-- LOAD SCRIPTS LAST -->
		<script>
			//GOOGLE ANALYTICS CODE
			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
				(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
				m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

			ga('create', '<?= $GOOGLEANALYTICS ?>', 'rit.edu');
		</script>
		<script
			src="https://www.datadoghq-browser-agent.com/datadog-rum-us.js"
			type="text/javascript">
		</script>
		<script>
			window.DD_RUM && window.DD_RUM.init({
				clientToken: '<?= $RUM_CLIENT_TOKEN ?>',
				applicationId: '<?= $RUM_APPLICATION_ID ?>',
			});
		</script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/mousetrap/1.4.6/mousetrap.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/angular.js/1.2.32/angular.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/angular.js/1.2.32/angular-animate.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/angular.js/1.2.32/angular-sanitize.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/angular-ui-router/0.2.8/angular-ui-router.min.js"></script>
        <script src="<?=$ASSETROOTADDRESS?>assets/prod/<?=$APP_VERSION?>/modules/sm/dist.min.js"></script>
	</body>
</html>
