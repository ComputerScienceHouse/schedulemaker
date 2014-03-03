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

        <meta property="og:title" content="<?= (!empty($TITLE)) ? $TITLE . " - " : "" ?>ScheduleMaker" />
        <meta property="og:type" content="website" />
        <meta property="og:description" content="CSH ScheduleMaker makes picking your RIT class schedule easy! Preview all permutations of your schedule, browse available courses, and select courses at random, all with ScheduleMaker."
        <meta property="og:url" content="http://<?= $_SERVER['HTTP_HOST'] ?><?= $_SERVER['REQUEST_URI'] ?>" />
        <? if(!empty($IMGURL)) { ?>
        <meta property="og:image" content="<?= $IMGURL ?>" />
        <? } else { ?>
        <meta property="og:image" content="<?= $HTTPROOTADDRESS ?>img/csh_og.png" />
        <? } ?>
		<link href='http://fonts.googleapis.com/css?family=Roboto:300' rel='stylesheet' type='text/css'>
		<link href='./inc/bootstrap.css' rel='stylesheet' type='text/css' />
		<link href="//netdna.bootstrapcdn.com/font-awesome/4.0.1/css/font-awesome.css" rel="stylesheet">
		<link href='./inc/global.css' rel='stylesheet' type='text/css' />
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
		<script type='text/javascript' src='./js/jquery.js'></script>
		<script type="text/javascript" src="./js/bootstrap.js"></script>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.0/angular.js"></script>
        <script type="text/javascript" src="http://code.angularjs.org/1.2.0/angular-animate.min.js"></script>
        <script type="text/javascript" src="http://code.angularjs.org/1.2.0/angular-sanitize.min.js"></script>
        <script type="text/javascript" src="./js/mousetrap.js"></script>
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
		    <script>
      if (navigator.userAgent.match(/IEMobile\/10\.0/)) {
  var msViewportStyle = document.createElement("style")
  msViewportStyle.appendChild(
    document.createTextNode(
      "@-ms-viewport{width:auto!important}"
    )
  )
  document.getElementsByTagName("head")[0].appendChild(msViewportStyle)
}
    </script>
	</head>

	<body ng-app="sm" ng-init="defaultTerm = '<?=$CURRENT_QUARTER?>'">
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
                        <li ng-class="{active: path=='/generate.php'}"><a href="generate.php"><i class="fa fa-calendar-o fa-fw"></i> Make a Schedule</a></li>
                        <li ng-class="{active: path=='/browse.php'}"><a href="browse.php"><i class="fa fa-list fa-fw"></i> Browse Courses</a></li>
                        <li ng-class="{active: path=='/roulette.php'}"><a href="roulette.php"><i class="fa fa-search fa-fw"></i> Search Courses</a></li>
                    </ul>
                </div>
            </div>
		</div>
		<div id="container" ng-cloak ng-controller="AppCtrl">
				<?php if($SERVER_TYPE != 'production') { ?>
			<!-- Leaving this disabled until merge with develop after feature lock 
				<div class='alert alert-warning'>
				<span style='bold'>Notice:</span>
				This is the development server for ScheduleMaker.
				All information available here is testing material and comes with no guarantee of accuracy.
				The production server can be found at:
				<a href='http://schedule.csh.rit.edu/'>http://schedule.csh.rit.edu</a>
			</div>-->
		<? } ?>