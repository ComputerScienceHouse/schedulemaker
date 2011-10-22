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

// Do we have a quarter specified?
$quarter = (empty($_GET['quarter']) || !is_numeric($_GET['quarter'])) ? null : mysql_real_escape_string($_GET['quarter']);

// MAIN EXECUTION //////////////////////////////////////////////////////////
switch($quarter) {
	case null:
		// No quarter was specified, so we need to print the list of quarters
		require "./inc/header.inc";
		?>		
		<h1>Browse Courses &gt; Select a Quarter</h1>
		<?
		// Query for the quarters
		$query = "SELECT quarter FROM quarters ORDER BY quarter DESC";
		$result = mysql_query($query);
		if(!$result) {
			echo "Sorry! An error occurred!" . mysql_error();
			return;
		}
		
		// Start dumping the quarter list
		while($q = mysql_fetch_assoc($result)) {
			// Determine which quarter this is
			switch(substr($q['quarter'], -1)) {
				case 1:
					$q['string'] = "Fall";
					break;
				case 2:
					$q['string'] = "Winter";
					break;
				case 3:
					$q['string'] = "Spring";
					break;
				case 4:
					$q['string'] = "Summer";
					break;
				default:
					$q['string'] = "Unknown";
					break;
			}
			$q['year'] = substr($q['quarter'], 0, -1);
			?>
			
			<p>
				<a href="browse.php?quarter=<?= $q['quarter'] ?>"><?= $q['string'] ?> <?= $q['year'] ?> (<?= $q['quarter'] ?>)</a>
			</p>
			<?
		}

		require "./inc/footer.inc";

		break;

	default:
		// Display the fancy dropdown thingy that allows one to traverse the
		// list of courses
		require "./inc/header.inc";
		
		// Display the list of departments
		$query = "SELECT * FROM schools ORDER BY id";
		$schoolResult = mysql_query($query);
		if(!$schoolResult) {
			die("An error occurred!");
		}
		?>
		<script src='./js/browse.js' type='text/javascript'></script>
		<h1>Browse Courses &gt; <?= $quarter ?></h1>
		<input id='quarter' type='hidden' value="<?= $quarter ?>" />

		<?
		while($school = mysql_fetch_assoc($schoolResult)) {
			?>
			<div class="item school">
				<a href='#'>+</a> 
				<input type='hidden' value="<?= $school['id'] ?>" />
				<?= $school['id'] ?> - <?= $school['title'] ?>
			</div>
			<?
		}

		require "./inc/footer.inc";
		break;
}
?>
