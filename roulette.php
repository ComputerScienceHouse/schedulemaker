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
		<div class='rouletteLabel'><label for='term'>Term*:</label></div>
		<div class='rouletteField'><?= getTermField('term', $CURRENT_QUARTER) ?></div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'><label for='college'>College:</label></div>
		<div class='rouletteField'>
            <select id='college' name='college'>
                <option value='any'>Any Colleges</option>
            </select>
        </div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'><label for='department'>Department:</label></div>
		<div class='rouletteField'>
            <select id='department' name='department'>
                <option value='any'>Select a College From Above</option>
            </select>
		</div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'><label for='level'>Level:</label></div>
		<div class='rouletteField'>
			<select name='level' id='level'>
				<option value='any'>Any Level</option>
				<option value='beg'>Introductory (0 - 300)</option>
				<option value='int'>Intermediate (300 - 600)</option>
				<option value='grad'>Graduate (&gt;600)</option>
			</select>
		</div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'><label for='credits'>Credit Hours:</label></div>
		<div class='rouletteField'><input id='credits' type='text' name='credits' size='3' maxlength='2' /></div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'><label for='professor'>Professor:</label></div>
		<div class='rouletteField'><input id='professor' type='text' name='professor' /></div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'>Days:</div>
		<div class='rouletteField'>
			<table>
				<tr class='separated'>
                    <td><input type='checkbox' id='daysAny' name='daysAny' value='any'/></td>
                    <td colspan='3'><label for='daysAny'>Any Day</label></td></tr>
				<tr>
					<td><input id='mon' class='days' type='checkbox' name='days[]' value='Mon' /></td>
                    <td><label for='mon'>Monday</label></td>
					<td><input id='tue' class='days' type='checkbox' name='days[]' value='Tue' /></td>
                    <td><label for='tue'>Tuesday</label></td>
				</tr>
                <tr>
					<td><input id='wed' class='days' type='checkbox' name='days[]' value='Wed' /></td>
                    <td><label for='wed'>Wednesday</label></td>
					<td><input id='thur' class='days' type='checkbox' name='days[]' value='Thur' /></td>
                    <td><label for='thur'>Thursday</label></td>
				</tr>
                <tr>
					<td><input id='fri' class='days' type='checkbox' name='days[]' value='Fri' /></td>
                    <td><label for='fri'>Friday</label></td>
					<td><input id='sat' class='days' type='checkbox' name='days[]' value='Sat' /></td>
                    <td><label for='sat'>Saturday</label></td>
				</tr>
			</table>
		</div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'>Times:</div>
		<div class='rouletteField'>
			<table>
				<tr class='separated'>
                    <td><input type='checkbox' id='timesAny' name='timesAny' value='any'/></td>
                    <td><label for='timesAny'>Any Time</label></td>
                </tr>
				<tr>
                    <td><input id='morn' type='checkbox' name='times[]' value='morn' class='times'/></td>
                    <td><label for='morn'>Morning (8am - noon)</label></td>
                </tr>
				<tr>
                    <td><input id='aftn' type='checkbox' name='times[]' value='aftn' class='times'/></td>
                    <td><label for='aftn'>Afternoon (noon - 5pm)</label></td>
                </tr>
				<tr>
                    <td><input id='even' type='checkbox' name='times[]' value='even' class='times'/></td>
                    <td><label for='even'>Evening (after 5pm)</label></td>
                </tr>
			</table>
		</div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'><label for='online'>Include Online Courses:</label></div>
		<div class='rouletteField'><input id='online' type='checkbox' name='online' value='true' checked='checked'></div>
	</div>
	<div class='rouletteParamField'>
		<div class='rouletteLabel'><label for='honors'>Include Honors Courses:</label></div>
		<div class='rouletteField'><input id='honors' type='checkbox' name='honors' value='true' checked='checked'></div>
	</div>
    <div class='rouletteParamField'>
        <div class='rouletteLabel'><label for='offCampus'>Include Off-Campus Courses:</label></div>
        <div class='rouletteField'><input id='offCampus' type='checkbox' name='offCampus' value='true' checked='checked'/></div>
    </div>
</div>
</div>
	

<div id='rouletteCourse'></div>
<div id='rouletteSpin'>
	<input id='spinButton' class='bigButton' type='button' value="Give Me a Random Course!" />
</div>

</form>

<?
require "inc/footer.inc";
?>
