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
	<div class="row">
		<div class="col-md-8">
			<div class="panel panel-default">
				<div class="panel-heading">
					<div class="row form-horizontal">
						<div class="col-md-6">
							<h2 class="panel-title control-label pull-left">Browse Courses</h2>
						</div>
						<div class="col-md-6">
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
						<div ng-if="schools.length == 0" class="center">
							<h1>
								<i class="fa fa-spin fa-refresh"></i>
							</h1>
						</div>
						<div browse-list="school" ng-repeat="school in schools" class="list-group-item" ng-class="{active: school.ui.expanded && school.departments.length > 0}">
							<div class="browse-heading" ng-click="school.ui.toggleDisplay()">
								<button class="btn pull-right btn-default">
									<i class="fa" ng-class="school.ui.buttonClass"></i>
								</button>
								<h4 class="list-group-item-heading">{{school.code}}</h4>
								<p class="list-group-item-text">{{school.title}}</p>
							</div>
							<div class="browse-sublist" ng-show="school.departments.length > 0 && school.ui.expanded">
								<div class="list-group">
									<div browse-list="department" class="list-group-item" ng-repeat="department in school.departments" ng-class="{active: department.ui.expanded && department.courses.length > 0}">
										<div class="browse-heading" ng-click="department.ui.toggleDisplay()">
											<button class="btn pull-right btn-default">
												<i class="fa" ng-class="department.ui.buttonClass"></i>
											</button>
											<h4 class="list-group-item-heading">{{department.code}}</h4>
											<p class="list-group-item-text">{{department.title}}</p>
										</div>
										<div class="browse-sublist" ng-show="department.courses.length > 0 && department.ui.expanded">
											<div class="list-group">
												<div browse-list="course" class="list-group-item" ng-repeat="course in department.courses" ng-class="{'active-nostyle': course.ui.expanded && course.sections.length > 0}" ng-init="course.selected = cart.courseInCart(course)">
													<div class="browse-heading" ng-click="course.ui.toggleDisplay()">
														<button class="btn pull-right btn-default">
															<i class="fa" ng-class="course.ui.buttonClass"></i>
														</button>
														<h4 class="list-group-item-heading">{{department.code}}-{{course.course}}</h4>
														<p class="list-group-item-text">{{course.title}}</p>
													</div>
													<div ng-init="showDesc = true" ng-show="course.sections.length > 0 && course.ui.expanded">
														<button type="button" class="btn btn-block btn-default visible-sm visible-xs" ng-click="showDesc = !showDesc" style="margin-bottom: 10px;">Toggle Desciption</button>
														<p ng-class="{'hidden-xs':showDesc, 'hidden-sm': showDesc}" class="course-description">{{course.description}}</p>
														<div class="center">
															<button type="button" class="btn" ng-click="cart.toggleCourse(course)" ng-class="{'btn-danger':course.selected, 'btn-success':!course.selected}">
																<i class="fa" ng-class="{'fa-minus':course.selected, 'fa-plus':!course.selected}"></i> <i class="fa fa-shopping-cart"></i> 
																{{course.selected ? 'Remove all sections from cart':'Add all sections to cart'}}
															</button>
														</div>
														<div class="vert-spacer-static-sm clearfix">
														</div>
														<div class="course-results-cont row">
															<div class="inline-col col-md-6" ng-repeat="section in course.sections">
																<ul class="list-group" ng-init="section.selected = cart.sectionInCart(section); section.courseId = course.id">
																	<li class="list-group-item course-info">
																		<div class="row">
																			<div class="col-sm-8">
																				<h4 class="list-group-item-heading">{{$index + 1}}. {{department.code}}-{{course.course}}-{{section.section}}</h4>
																				<small>{{section.title}}</small>
																				<p class="list-group-item-text label-line ">
																					<span class="label label-default" professor-lookup="section.instructor"></span>
																				</p>
																				<div ng-init="parsedTimes = (section.times | parseSectionTimes:true)">
																					<div ng-repeat="time in parsedTimes" style="font-size: small">
																						{{time.days}} <span style="white-space: nowrap">{{time.start | formatTime}}-{{time.end | formatTime}}</span> <span style="font-style: italic; white-space: nowrap">Location: {{time.location}}</span>
																					</div>
																				</div>
																			</div>
																			<div class="col-sm-4">
																				<div class="row">
																					<div class="col-xs-12">
																						<button type="button" class="btn btn-block" ng-click="cart.toggleSection(section)" ng-class="{'btn-danger':section.selected, 'btn-success':!section.selected}">
																							<i class="fa" ng-class="{'fa-minus':section.selected, 'fa-plus':!section.selected}"></i> <i class="fa fa-shopping-cart"></i>
																						</button>
																					</div>
																				</div>
																			</div>
																		</div>
																	</li>
																</ul>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div pinned class="panel panel-default course-cart">
				<div class="panel-heading">
					<h2 class="panel-title">Course Cart</h2>
				</div>
				<div class="panel-body">
					<div class="course-cart-window" ng-switch="cart.length > 0">
						<ul ng-switch-when="true" class="list-group">
							<li class="list-group-item repeat-item" ng-repeat="(id, item) in cart.items">
								<button class="btn pull-right btn-danger" ng-click="cart.toggleSection(item)">
									<i class="fa fa-minus"></i> <i class="fa fa-shopping-cart"></i>
								</button>
								<h4 class="list-group-item-heading">{{item.department.code}}-{{item.course}}-{{item.section}}</h4>
								<p class="list-group-item-text">{{item.title}}</p>
							</li>
						</ul>
						<div class="alert" ng-switch-when="false">Add courses to your cart and make a schedule with them. They will show up here.</div>
					</div>
				</div>
				<div class="panel-footer">
					<button type="button" class="btn btn-primary btn-block">Make a schedule with these</button>
				</div>
			</div>
		</div>
	</div>
</div>


<?
require "./inc/footer.inc";
?>
