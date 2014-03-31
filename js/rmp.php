<?php
////////////////////////////////////////////////////////////////////////////
// RATE MY PROFESSORS PROXY
//
// @author	Ben Grawi (bgrawi@csh.rit.edu)
//
// @file	js/rmp.php
// @descrip	Provides a proxy to the RMP site to avoid XSS
////////////////////////////////////////////////////////////////////////////
$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HEADER, false);
$name = $_GET['professor'];
curl_setopt ($curl, CURLOPT_URL, "http://www.ratemyprofessors.com/SelectTeacher.jsp?searchName=".$name."&search_submit1=Search&sid=807");
echo curl_exec($curl);