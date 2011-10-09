<?php
// REQUIRED FILES
require_once('./inc/config.php');
require_once('./inc/databaseConn.php');
require_once('./inc/timeFunctions.php');
?>
<html>
	<head>
		<title><?= (!empty($TITLE)) ? $TITLE : "Schedule Maker" ?></title>
		
		<link href='./inc/global.css' rel='stylesheet' type='text/css' />
		<script type='text/javascript' src='./js/jquery.js'></script>
	</head>

	<body>
		<div id="container">
