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

function getTermType($term) {
    // Determine the term based on the year
    $termType = substr($term, -1);
    if($term > 20130) {
        // Semesters
        switch($termType) {
            case 1:
                return "Fall";
            case 3:
                return "Winter Intersession";
            case 5:
                return "Spring";
            case 8:
                return "Summer";
            default:
                return "Unknown";
        }
    } else {
        // Based on the last number of the quarter, return a title
        switch($termType) {
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
}

// Do we have a term specified?
$term = (empty($_GET['term']) || !is_numeric($_GET['term'])) ? $CURRENT_QUARTER : $_GET['term'];

// MAIN EXECUTION //////////////////////////////////////////////////////////
require "./inc/header.inc";

// Display the fancy dropdown thingy that allows one to traverse the
// list of courses
?>
<div class="container">
<script src='./js/browse.js' type='text/javascript'></script>
<h1 id='browseHeader'>Browse Courses &gt; <?= getTermType($term) ?> <?= substr($term, 0, 4) ?></h1>

<div class='subContainer' id='browseQuarter'>
    <label for='termSelect'>Select a Different Term:</label>
    <?= getTermField("term", $term); ?>
</div>

<?
// Display the list of departments
if($term > 20130) {
    // School codes
    $query = "SELECT id, code AS code, title FROM schools WHERE code IS NOT NULL ORDER BY code";
} else {
    // School numbers
    $query = "SELECT id, number AS code, title FROM schools WHERE number IS NOT NULL ORDER BY number";
}
$schoolResult = mysql_query($query);
if(!$schoolResult) {
    die("An error occurred!");
}
while($school = mysql_fetch_assoc($schoolResult)) {
    ?>
    <div class="item school">
        <button>+</button>
        <input type='hidden' value="<?= $school['id'] ?>" />
        <?= $school['code'] ?> - <?= $school['title'] ?>
    </div>
    <?
}
?>
</div>
<?
require "./inc/footer.inc";
?>
