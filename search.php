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
		<div course-cart></div>
	</div>
	</div>
</div>
<?
require "inc/footer.inc";
?>
