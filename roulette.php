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

<form id='parameters'>
<div id='rouletteParams'>
	<input type="hidden" value="rouletteSpin" name="action">
	<div id='rouletteHeader'>
		<h2>Set the Class Parameters</h2>
		<span class='disclaimer'>* Denotes required field</span>
	</div>
	<div id='rouletteParamFields'>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'><label for='quarter'>Quarter*:</label></div>
		<div class='rouletteField'><?= getQuarterField('quarter', $CURRENT_QUARTER) ?></div>
	</div>
	<div class='rouletteParamField' style='margin-bottom:0'>
		<div class='rouletteLabel'><label for='college'>College:</label></div>
		<div class='rouletteField'><?= getCollegeField('college', null, true) ?></div>
	</div>
	<div class='rouletteParamField'>OR</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'><label for='department'>Department:</label></div>
		<div class='rouletteField'><?= getDepartmentField('department', null, true) ?></div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'><label for='level'>Level:</label></div>
		<div class='rouletteField'>
			<select name='level'>
				<option value='any'>Any Level</option>
				<option value='beg'>Introductory (0 - 300)</option>
				<option value='int'>Intermediate (300 - 600)</option>
				<option value='grad'>Graduate (&gt;600)</option>
			</select>
		</div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'><label for='credits'>Credit Hours:</label></div>
		<div class='rouletteField'><input type='text' name='credits' size='3' maxlength='2' /></div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'><label for='professor'>Professor:</label></div>
		<div class='rouletteField'><input type='text' name='professor' /></div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'>Days:</div>
		<div class='rouletteField'>
			<table>
				<tr style="border-bottom:solid 1px #0C5DA5;"><td><input type='checkbox' name='daysAny' value='any' onChange='toggleDaysAny(this)' /></td><td colspan='3'>Any Day</td></tr>
				<tr>
					<td><input id='mon' type='checkbox' name='days[]' value='Mon' /></td><td>Monday</td>
					<td><input id='tue' type='checkbox' name='days[]' value='Tue' /></td><td>Tuesday</td>
				</tr><tr>
					<td><input id='wed' type='checkbox' name='days[]' value='Wed' /></td><td>Wednesday</td>
					<td><input id='hur' type='checkbox' name='days[]' value='Thur' /></td><td>Thursday</td>
				</tr><tr>
					<td><input id='fri' type='checkbox' name='days[]' value='Fri' /></td><td>Friday</td>
					<td><input id='sat' type='checkbox' name='days[]' value='Sat' /></td><td>Saturday</td>
				</tr>
			</table>
		</div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'>Times:</div>
		<div class='rouletteField'>
			<table>
				<tr style='border-bottom:1px solid #0C5DA5'><td><input type='checkbox' name='timesAny' value='any' onChange='toggleTimesAny(this)' /></td><td>Any Time</td></tr>
				<tr><td><input id='morn' type='checkbox' name='times[]' value='morn' /></td><td>Morning (8am - noon)</td></tr>
				<tr><td><input id='aftn' type='checkbox' name='times[]' value='aftn' /></td><td>Afternoon (noon - 5pm)</td></tr>
				<tr><td><input id='even' type='checkbox' name='times[]' value='even' /></td><td>Morning (after 5pm)</td></tr>
			</table>
		</div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'><label for='online'>Include Online Courses:</label></div>
		<div class='rouletteField'><input type='checkbox' name='online' value='true' checked='checked'></div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'><label for='honors'>Include Honors Courses:</label></div>
		<div class='rouletteField'><input type='checkbox' name='honors' value='true' checked='checked'></div>
	</div>
	</div>
</div>
	

<div id='rouletteCourse'></div>
<div id='rouletteSpin'>
	<input id='spinButton' class='bigButton' type='button' value="Give Me a Random Course!" onClick="spinRoulette();" />
</div>

</form>

<?
require "inc/footer.inc";
?>
