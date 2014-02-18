<?php
////////////////////////////////////////////////////////////////////////////
// SCHEDULE MAKER
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	roulette.php
// @descrip	Course roulette -- specify a few things to refine the course list
//			then spin the wheel! Get a totally random course each time!
////////////////////////////////////////////////////////////////////////////

require "./inc/header.inc";
?>
<script type='text/javascript' src='./js/roulette.js'></script>
<div class="container">
	<div class="row">
		<div class="col-md-8">
		<form class="form-horizontal">
			<div class="panel panel-default">
				<div class="panel-heading">
					<div class="row form-horizontal">
						<div class="col-md-6">
							<h2 class="panel-title control-label pull-left">Search Courses</h2>
						</div>
						<div class="col-md-6">
							<div class="control-group">
								<label class="col-sm-6 control-label" for="term">Term:</label>
								<div class="col-sm-6">
									<?= getTermField("state.requestOptions.term"); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="panel-body">
					<div class="form-group">
						<label class="control-label col-sm-4" for="college">College:</label>
						<div class="col-sm-8">
				            <select class="form-control" id='college' name='college'>
				                <option value='any'>Any Colleges</option>
				            </select>
				        </div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="department">Department:</label>
						<div class="col-sm-8">
				            <select class="form-control" id='department' name='department'>
				                <option value='any'>Select a College From Above</option>
				            </select>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="level">Level:</label>
						<div class="col-sm-8">
							<select class="form-control" name='level' id='level'>
								<option value='any'>Any Level</option>
								<option value='beg'>Introductory (0 - 300)</option>
								<option value='int'>Intermediate (300 - 600)</option>
								<option value='grad'>Graduate (&gt;600)</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="credits">Credit Hours:</label>
						<div class="col-sm-8"><input class="form-control" id='credits' type='text' name='credits' size='3' maxlength='2' /></div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="professor">Professor:</label>
						<div class="col-sm-8"><input class="form-control" id='professor' type='text' name='professor' /></div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-4">Days:</label>
						<div class="col-md-8">
							<div ng-init="days = []" dow-select-fields="days"></div>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-4">Times:</label>
						<div class="col-md-8">
							<div class="btn-group">
								<button type="button" class="btn btn-default">8am - noon</button>
								<button type="button" class="btn btn-default">noon - 5pm</button>
								<button type="button" class="btn btn-default">5pm - midnight</button>
							</div>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="online">Course Options:</label>
						<div class="col-sm-8">
							<div class="row">
								<div class="col-sm-4"><button type="button" class="btn btn-block btn-success">Online <i class="fa fa-check-square"></i></button></div>
								<div class="col-sm-4"><button type="button" class="btn btn-block btn-success">Honors <i class="fa fa-check-square"></i></button></div>
								<div class="col-sm-4"><button type="button" class="btn btn-block btn-success">Off Campus <i class="fa fa-check-square"></i></button></div>
							</div>
						</div>
					</div>	
			    </div>
			</div>
			<div class="center" role="toolbar">
				<div class="btn-group">
					<button type="button" class="btn-lg btn btn-primary btn-default">Search for Courses</button>
				</div>
				<div class="btn-group">
					<button type="button" class="btn-lg btn btn-default btn-danger" ng-click="resetState()">Reset</button>
				</div>
			</div>
		</form>
	</div>
	<div class="col-md-4" ng-init="showCourseCart = true">
		<div class="visible-xs visible-sm vert-spacer-static-md"></div>
		<div class="panel panel-default course-cart">
			<div class="panel-heading">
				<h2 class="panel-title clearfix">
					Course Cart
					<button type="button" class="btn btn-xs btn-primary hidden-md hidden-lg pull-right" ng-click="showCourseCart = !showCourseCart">
						<i class="fa" ng-class="{'fa-angle-down': showCourseCart, 'fa-angle-up': !showCourseCart}"></i>
					</button>
				</h2>
			</div>
			<div class="panel-body" ng-class="{'hidden-xs':showCourseCart, 'hidden-sm': showCourseCart}">
				<div class="course-cart-window animate-show-hide" ng-switch="getSelectedCount() > 0">
					<ul ng-switch-when="true" class="list-group">
						<li class="list-group-item repeat-item course-cart-item" ng-style="{'border-left-color':course.color}" ng-if="course.sections.length > 0 && !course.sections[0].isError" ng-repeat="course in state.courses">
							<div class="btn-group pull-right">
								<button type="button" class="btn btn-danger" ng-click="courses_helpers.removeThis(course)">
									<i class="fa fa-minus"></i> <i class="fa fa-shopping-cart"></i>
								</button>
								<button type="button" class="btn btn-primary" ng-click="showCourseSections = !showCourseSections">
									<i class="fa" ng-class="{'fa-angle-down': !showCourseSections, 'fa-angle-up': showCourseSections}"></i>
								</button>
							</div>
							<h4 class="list-group-item-heading">{{course.search}}:</h4>
							<p class="list-group-item-text">{{getSelectedSectionCount(course)}} selected</p>
							<ul class="list-group" ng-if="showCourseSections">
								<li class="list-group-item repeat-item" ng-repeat="section in course.sections | filter:{selected: true}">
									<button type="button" class="btn pull-right btn-danger visible-md visible-lg" ng-click="section.selected = !section.selected">
										<i class="fa fa-minus"></i> <i class="fa fa-shopping-cart"></i>
									</button>
									<h4 class="list-group-item-heading">{{section.courseNum}}</h4>
									<p class="list-group-item-text">{{section.instructor}}</p>
									<button type="button" class="btn btn-danger btn-block visible-xs visible-sm" ng-click="section.selected = !section.selected">
										<i class="fa fa-minus"></i> <i class="fa fa-shopping-cart"></i>
									</button>
								</li>
							</ul>
						</li>
					</ul>
					<div class="alert" ng-switch-when="false">Add courses to your cart and make a schedule with them. They will show up here.</div>
				</div>
			</div>
			<div class="panel-footer" ng-class="{'hidden-xs':showCourseCart, 'hidden-sm': showCourseCart}">
				<button type="button" class="btn btn-primary btn-block" ng-click="generateSchedules()">Show Matching Schedules</button>
			</div>
		</div>
	</div>
	</div>
</div>
<script src='js/handlebars.js' type="text/javascript"></script>
<script src='js/translateFunctions.js' type="text/javascript"></script>

<?
require "inc/footer.inc";
?>
