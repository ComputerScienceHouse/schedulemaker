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
		
		<!-- META DATA -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
		<meta name="apple-mobile-web-app-capable" content="yes">
		
		<!-- STYLE SHEETS -->
		<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Roboto:300">
		<link rel="stylesheet" href="<?=$HTTPROOTADDRESS?>inc/bootstrap.css">
		<link rel="stylesheet" href="//netdna.bootstrapcdn.com/font-awesome/4.0.1/css/font-awesome.css">
		<link rel="stylesheet" href="<?=$HTTPROOTADDRESS?>inc/global.css">
		
		<!-- OPEN GRAPH TAGS -->
        <meta property="og:title" content="<?= (!empty($TITLE)) ? $TITLE . " - " : "" ?>ScheduleMaker" />
        <meta property="og:type" content="website" />
        <meta property="og:description" content="CSH ScheduleMaker makes picking your RIT class schedule easy! Preview all permutations of your schedule, browse available courses, and select courses at random, all with ScheduleMaker.">
        <meta property="og:url" content="http://<?= $_SERVER['HTTP_HOST'] ?><?= $_SERVER['REQUEST_URI'] ?>" />
        <? if(!empty($IMGURL)) { ?>
        <meta property="og:image" content="<?= $IMGURL ?>" />
        <? } else { ?>
        <meta property="og:image" content="<?= $HTTPROOTADDRESS ?>img/csh_og.png">
        <? } ?>
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
                    <a class="navbar-brand" href="/">ScheduleMaker <?=($SERVER_TYPE != 'production')?"<span class=\"label label-info\">BETA</span>":""?></a>
                </div>
                <div class="collapse navbar-collapse navbar-right navbar-ex1-collapse">
                    <ul class="nav navbar-nav">
                        <li ng-class="{active: path=='/generate.php'}"><a href="generate.php"><i class="fa fa-calendar-o fa-fw"></i> Make a Schedule</a></li>
                        <li ng-class="{active: path=='/browse.php'}"><a href="browse.php"><i class="fa fa-list fa-fw"></i> Browse Courses</a></li>
                        <li ng-class="{active: path=='/roulette.php'}"><a href="search.php"><i class="fa fa-search fa-fw"></i> Search Courses</a></li>
                    </ul>
                </div>
            </div>
		</div>
		<div id="container" ng-cloak ng-controller="AppCtrl">
			<?php if($SERVER_TYPE != 'production') { ?>
			<div class="container" ng-show="!state.ui.betaHide">
				<div class="alert alert-info alert-dismissable hidden-xs">
					<strong>Thanks</strong> for testing the new beta of ScheduleMaker! Please report any bugs on our github page <a target="_blank" href="https://github.com/ComputerScienceHouse/schedulemaker/issues">here</a>. Reports are greatly apreciated!
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true" ng-click="state.ui.betaHide = true"><i class="fa fa-times"></i></button>
				</div>
			</div>
			<? } ?>