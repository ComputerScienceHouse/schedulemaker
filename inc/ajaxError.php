<?php
////////////////////////////////////////////////////////////////////////////
// AJAX ERROR HANDLING
//
// @file	inc/ajaxError.php
// @author	Ben Russell (benrr101@csh.rit.edu)
// @descrip	Defines an error handler for use in ajax call files. This handler
//			will halt execution on any error and output a useful (to both the
//			the user and developer) message in the form of jSON.
////////////////////////////////////////////////////////////////////////////

function errorHandler($errno, $errstr, $errfile, $errline) {
    die(json_encode([
        "error" => "php",
        "msg" => "A internal server error occurred",
        "guru" => $errstr,
        "num" => $errno,
        "file" => "{$errfile}:{$errline}"
    ]));
}

set_error_handler("errorHandler");
