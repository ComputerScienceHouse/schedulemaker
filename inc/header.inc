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
		
		<link href='./inc/bootstrap.css' rel='stylesheet' type='text/css' />
		<link href='./inc/global.css' rel='stylesheet' type='text/css' />
		
		<script type='text/javascript' src='./js/jquery.js'></script>
		<script type="text/javascript" src="./js/bootstrap.js"></script>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.0-rc.3/angular.js"></script>
        <script type="text/javascript" src="http://code.angularjs.org/1.2.0-rc.3/angular-animate.min.js"></script>
        <script type="text/javascript" src="./js/app.js"></script>
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

	<body ng-app="sm">
		<?php if($SERVER_TYPE != 'production') { ?>
			<!--<div id='devNotice' class='devNotice'>
				<span style='bold'>Notice:</span>
				This is the development server for ScheduleMaker.
				All information available here is testing material and comes with no guarantee of accuracy.
				The production server can be found at:
				<a href='http://schedule.csh.rit.edu/'>http://schedule.csh.rit.edu</a>
			</div>-->
		<? } ?>
		<div id='superContainer'>
		<div class="navbar navbar-fixed-top navbar-default ng-scope" ng-controller="MainMenuCtrl">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle btn btn-default" data-toggle="collapse" data-target=".navbar-ex1-collapse">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="/">ScheduleMaker</a>
                </div>
                <div class="collapse navbar-collapse navbar-right navbar-ex1-collapse">
                    <ul class="nav navbar-nav">
                        <li ng-class="{active: path=='/generate.php'}"><a href="generate.php">Make a Schedule</a></li>
                        <li ng-class="{active: path=='/browse.php'}"><a href="browse.php">Browse Courses</a></li>
                        <li ng-class="{active: path=='/roulette.php'}"><a href="roulette.php">Course Roulette</a></li>
                    </ul>
                </div>
            </div>
		</div>
		<div id="container" class="container">
