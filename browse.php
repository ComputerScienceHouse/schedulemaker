<?php
////////////////////////////////////////////////////////////////////////////
// SCHEDULE MAKER
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	roulette.php
// @descrip	Browse Courses. This page is gonna be awesome. You can browse the
//			different courses in the database and then do fun things with them
////////////////////////////////////////////////////////////////////////////

// REQUIRED FILES //////////////////////////////////////////////////////////
require_once "./inc/config.php";
require_once "./inc/databaseConn.php";
require_once "./inc/timeFunctions.php";

// FUNCTIONS ///////////////////////////////////////////////////////////////

/**
 * Parses a term and returns an array of the results
 * @param string $term
 * @return string[]
 */
function parseTerm($term) {
    // Determine the term based on the year
    $termType = substr($term, -1);
    $year = substr($term, 0, 4);
    $nextYear = (string) ((int) $year + 1);
    if($term > 20130) {
        // Semesters
        switch($termType) {
            case 1:
                return ["Fall", $year];
            case 3:
                return ["Winter Intersession", $nextYear];
            case 5:
                return ["Spring", $nextYear];
            case 8:
                return ["Summer", $nextYear];
            default:
                return ["Unknown", ""];
        }
    } else {
        // Based on the last number of the quarter, return a title
        switch($termType) {
            case 1:
                return ["Fall", $year];
            case 2:
                return ["Winter", $year];
            case 3:
                return ["Spring", $nextYear];
            case 4:
                return ["Summer", $nextYear];
            default:
                return ["Unknown", ""];
        }
    }
}

// Do we have a term specified?
$term = (empty($_GET['term']) || !is_numeric($_GET['term'])) ? $CURRENT_QUARTER : $_GET['term'];

$parsedTerm = parseTerm($term);

// MAIN EXECUTION //////////////////////////////////////////////////////////
require "./inc/header.inc";

// Display the fancy dropdown thingy that allows one to traverse the
// list of courses
?>
<div class="container" ng-controller="BrowseCtrl">
	<div class="panel panel-default">
		<div class="panel-heading">
			<div class="row form-horizontal">
				<div class="col-md-7">
					<h2 class="panel-title control-label pull-left">Browse Courses</h2>
				</div>
				<div class="col-md-5">
					<div class="control-group">
						<label class="col-sm-6 control-label" for="term">Select Term</label>
						<div class="col-sm-6">
							<?= getTermField("term", $term); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="panel-body">
			<div id="browse-contents" class="list-group">
				<a href="#" ng-click="toggleSchool($event)" class="list-group-item" ng-repeat="school in contents">
					<button class="btn pull-right btn-default"><i class="fa" ng-class="{'fa-plus':!expanded, 'fa-minus':expanded}"></i></button>
   	 				<h4 class="list-group-item-heading">{{school.code}}</h4>
    				<p class="list-group-item-text">{{school.title}}</p>
				</a>
			</div>
		</div>
	</div>
</div>


<?
require "./inc/footer.inc";
?>
