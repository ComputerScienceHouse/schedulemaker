<?php

require_once "../inc/config.php";
require_once "../inc/databaseConn.php";

$query = "SELECT * FROM departments";
$result = mysql_query($query);
$schoolid = array();
while($row = mysql_fetch_assoc($result)) {
	$schoolid[] = substr(strval($row['id']), 0, 2);
	$query = "UPDATE departments SET school = " . substr(strval($row['id']), 0, 2) . " WHERE id = {$row['id']}";
	mysql_query($query);
}
$query = "INSERT IGNORE INTO schools (id) VALUES (" . implode("),(", $schoolid) . ")";
mysql_query($query);
?>
