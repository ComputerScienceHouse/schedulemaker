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

// If we were posted the noscript value, then do a non-ajax ajax call
if(isset($_POST['noscript']) && $_POST['noscript'] == 'true') {
	$formData = true;	
	// Do an HTTP get request
	

} else {
	$formData = false;
}

// Now we need to process the selected quarter
if($formData) {
	$quarterSelected = $_POST['quarter'];
	$departmentSelected = $_POST['department'];
	$schoolSelected = $_POST['school'];
	$professorSelected = $_POST['professor'];
	$credits = $_POST['credits'];
	$level = $_POST['level'];
	$daysAny = (!empty($_POST['daysAny'])) ? true : false;
	$days = (!empty($_POST['days'])) ? $_POST['days'] : array();
	$timesAny = (!empty($_POST['timesAny'])) ? true : false;
	$daysAny = (!empty($_POST['times'])) ? $_POST['times'] : array();
} else {
	global $CURRENT_QUARTER;
	$quarterSelected = $CURRENT_QUARTER;
	$departmentSelected = null;
	$schoolSelected = null;
	$professorSelected = null;
	$credits = null;
	$level = null;
	$daysAny = null;
	$days = array();
	$timesAny = null;
	$times = array();
}

?>
<script type='text/javascript' src='./js/roulette.js'></script>
<noscript><style>#spinButton { display:none; }</style></noscript>

<h1>Course Roulette</h1>
<form id='restrictions' name='restrictions' action='roulette.php' method='POST'>
<input type='hidden' name='action' value='rouletteSpin' />
<noscript><input type='hidden' name='noscript' value='true' /></noscript>

<table id='rouletteForm'>
	<tr><td colspan='6'><h2>Refine the course list:</h2></td></tr>
	<tr>
		<td class='lbl'><label for='quarter'>Quarter*:</label></td>
		<td><?= getQuarterField('quarter', $quarterSelected) ?></td>
		<td class='lbl'><label for='school'>College:</label></td>
		<td><?= getCollegeField('school', $schoolSelected, true) ?></td>
		<td class='lbl'><span style='font-weight:bold'>OR</span> <label for='department'>Department:</label></td>
		<td><?= getDepartmentField('department', $departmentSelected, true) ?></td>
		<td class='lbl'<label for='level'>Level:</label></td>
		<td>
			<select name='level'>
				<option value='any' <?= ($level == 'any') ? "selected='selected'" : "" ?>>Any Level</option>
				<option value='beg' <?= ($level == 'beg') ? "selected='selected'" : "" ?>>Introductory (0 - 300)</option>
				<option value='int' <?= ($level == 'int') ? "selected='selected'" : "" ?>>Intermediate (300 - 600)</option>
				<option value='grad' <?= ($level == 'grad') ? "selected='selected'" : "" ?>>Graduate (>600)</option>
			</select>
		</td>
	</tr>
	<tr>
		<td class='lbl'><label for='credits'>Credit Hours:</label></td>
		<td><input type='text' name='credits' value="<?= $credits ?>" size='3' maxlength='2' /></td>
		<td class='lbl'><label for='professor'>Professor:</label></td>
		<td><input type='text' name='professor' value="<?= $professorSelected ?>" /></td>
		<td class='lbl'><label for='days'>Days:</label></td>
		<td>
			<table style='font-size:12px; width:100%; border-collapse:collapse'>
				<tr style="border-bottom:solid 1px grey;"><td><input type='checkbox' name='daysAny' value='any' onChange='toggleDaysAny(this)' <?= ($daysAny) ? "checked='checked' " : "" ?>/></td><td colspan='3'>Any Day</td></tr>
				<tr>
					<td><input id='mon' type='checkbox' name='days[]' value='Mon' <?= (in_array('Mon', $days)) ? "checked='checked' " : "" ?>/></td><td>Monday</td>
					<td><input id='tue' type='checkbox' name='days[]' value='Tue' <?= (in_array('Tue', $days)) ? "checked='checked' " : "" ?>/></td><td>Tuesday</td>
				</tr><tr>
					<td><input id='wed' type='checkbox' name='days[]' value='Wed' <?= (in_array('Wed', $days)) ? "checked='checked' " : "" ?>/></td><td>Wednesday</td>
					<td><input id='hur' type='checkbox' name='days[]' value='Thur' <?= (in_array('Thur', $days)) ? "checked='checked' " : "" ?>/></td><td>Thursday</td>
				</tr><tr>
					<td><input id='fri' type='checkbox' name='days[]' value='Fri' <?= (in_array('Fri', $days)) ? "checked='checked' " : "" ?>/></td><td>Friday</td>
					<td><input id='sat' type='checkbox' name='days[]' value='Sat' <?= (in_array('Sat', $days)) ? "checked='checked' " : "" ?>/></td><td>Saturday</td>
				</tr>
			</table>
		</td>
		<td class='lbl'><label for='times'>Times:</label></td>
		<td>
			<table style='font-size:12px; width:100%; border-collapse:collapse'>
				<tr style='border-bottom:solid 1px grey'><td><input type='checkbox' name='timesAny' value='any' onChange='toggleTimesAny(this)' <?= ($timesAny) ? "checked='checked' " : "" ?>/></td><td>Any Time</td></tr>
				<tr><td><input id='morn' type='checkbox' name='times[]' value='morn' <?= (in_array('morn', $times)) ? "checked='checked' " : "" ?>/></td><td>Morning (8am - noon)</td></tr>
				<tr><td><input id='aftn' type='checkbox' name='times[]' value='aftn' <?= (in_array('aftn', $times)) ? "checked='checked' " : "" ?>/></td><td>Afternoon (noon - 5pm)</td></tr>
				<tr><td><input id='even' type='checkbox' name='times[]' value='even' <?= (in_array('even', $times)) ? "checked='checked' " : "" ?>/></td><td>Morning (after 5pm)</td></tr>
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
