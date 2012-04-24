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

function getQuarterType($quarter) {
	// Based on the last number of the quarter, return a title
	switch(substr($quarter, -1)) {
		case 1:
			return "Fall";
		case 2:
			return "Winter";
		case 3:
			return "Spring";
		case 4:
			return "Summer";
		default:
			return "Unknown";
	}
}

// Do we have a quarter specified?
$quarter = (empty($_GET['quarter']) || !is_numeric($_GET['quarter'])) ? null : mysql_real_escape_string($_GET['quarter']);

// MAIN EXECUTION //////////////////////////////////////////////////////////
switch($quarter) {
	case null:
		// No quarter was specified, so load the current quarter
		$quarter = $CURRENT_QUARTER;
		// Now fall into the standard printout of courses

	default:
		// Display the fancy dropdown thingy that allows one to traverse the
		// list of courses
		require "./inc/header.inc";
		
		?>
		<script src='./js/browse.js' type='text/javascript'></script>
		<h1 id='browseHeader'>Browse Courses &gt; <?= getQuarterType($quarter) ?> <?= substr($quarter, 0, 4) ?></h1>

		<div class='subContainer' id='browseQuarter'>
			Select a Different Quarter:
			<select id='quarterSelect' name='quarterSelect' onChange='document.location=this.value'>
			<?
			$query = "SELECT quarter FROM quarters ORDER BY quarter DESC";
			$quarterResult = mysql_query($query);
			if(!$quarterResult) {
				die("An error occurred!");
			}
			while($qtr = mysql_fetch_assoc($quarterResult)) { ?>
				<option value='browse.php?quarter=<?= $qtr['quarter'] ?>' <?= ($qtr['quarter'] == $quarter) ? "selected='selected'" : "" ?>>
					<?= substr($qtr['quarter'], 0, 4) ?> <?= getQuarterType($qtr['quarter']) ?>
				</option>
			<? } ?>
			</select>
		</div>

		<?
		// Display the list of departments
		$query = "SELECT * FROM schools ORDER BY id";
		$schoolResult = mysql_query($query);
		if(!$schoolResult) {
			die("An error occurred!");
		}
		while($school = mysql_fetch_assoc($schoolResult)) {
			?>
			<div class="item school">
				<button>+</button>
				<input type='hidden' value="<?= $school['id'] ?>" />
				<?= $school['id'] ?> (<?= $school['code'] ?>) - <?= $school['title'] ?>
			</div>
			<?
		}

		require "./inc/footer.inc";
		break;
}
?>
