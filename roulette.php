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

<h1>Course Roulette</h1>
<form id='restrictions' name='restrictions' action='roulette.php' method='POST'>
<input type='hidden' name='action' value='rouletteSpin' />

<table id='rouletteForm'>
	<tr><td colspan='6'><h2>Refine the course list:</h2></td></tr>
	<tr>
		<td class='lbl'><label for='quarter'>Quarter*:</label></td>
		<td><?= getQuarterField('quarter', $CURRENT_QUARTER) ?></td>
		<td class='lbl'><label for='school'>College:</label></td>
		<td><?= getCollegeField('school', null, true) ?></td>
		<td class='lbl'><span style='font-weight:bold'>OR</span> <label for='department'>Department:</label></td>
		<td><?= getDepartmentField('department', null, true) ?></td>
		<td class='lbl'<label for='level'>Level:</label></td>
		<td>
			<select name='level'>
				<option value='any'>Any Level</option>
				<option value='beg'>Introductory (0 - 300)</option>
				<option value='int'>Intermediate (300 - 600)</option>
				<option value='grad'>Graduate (>600)</option>
			</select>
		</td>
	</tr>
	<tr>
		<td class='lbl'><label for='credits'>Credit Hours:</label></td>
		<td><input type='text' name='credits' size='3' maxlength='2' /></td>
		<td class='lbl'><label for='professor'>Professor:</label></td>
		<td><input type='text' name='professor' /></td>
		<td class='lbl'><label for='days'>Days:</label></td>
		<td>
			<table style='font-size:12px; width:100%; border-collapse:collapse'>
				<tr style="border-bottom:solid 1px grey;"><td><input type='checkbox' name='daysAny' value='any' onChange='toggleDaysAny(this)' /></td><td colspan='3'>Any Day</td></tr>
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
		</td>
		<td class='lbl'><label for='times'>Times:</label></td>
		<td>
			<table style='font-size:12px; width:100%; border-collapse:collapse'>
				<tr style='border-bottom:solid 1px grey'><td><input type='checkbox' name='timesAny' value='any' onChange='toggleTimesAny(this)' /></td><td>Any Time</td></tr>
				<tr><td><input id='morn' type='checkbox' name='times[]' value='morn' /></td><td>Morning (8am - noon)</td></tr>
				<tr><td><input id='aftn' type='checkbox' name='times[]' value='aftn' /></td><td>Afternoon (noon - 5pm)</td></tr>
				<tr><td><input id='even' type='checkbox' name='times[]' value='even' /></td><td>Morning (after 5pm)</td></tr>
			</table>
		</td>
	</tr>
	<tr><td colspan='6' style='font-size:10px'>* denotes required fields</td></tr>
</table>
<div id='rouletteImg'>BIG IMAGE GOES HERE</div>
<div id='rouletteCourse'></div>
<div id='rouletteSpin'>
		<input id='spinButton' type='button' value="Spin The Wheel" onClick='spinRoulette();' />
		<noscript><input id='spinButton' type='submit' value="Spin The Wheel" /></noscript>
</div>
</form>

<? require "./inc/footer.inc"; ?>
