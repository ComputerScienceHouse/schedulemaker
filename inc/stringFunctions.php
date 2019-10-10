<?php
////////////////////////////////////////////////////////////////////////////
// STRING FUNCTIONS 
//
// @file	inc/stringFunctions.php
// @descrip	This file contains functions for processing strings per RIT's
//			total bullshit string setups.
// @author	Ben Russell (brr1922@csh.rit.edu)
////////////////////////////////////////////////////////////////////////////

// FUNCTIONS ///////////////////////////////////////////////////////////////

function trimNBSP($string) {
    $string = str_replace("\xA0", " ", $string);
    $string = trim($string);
    return $string;
}

function upperCaseName($string) {
    // Iterate over each word, ucfirst'ing each one
    $newString = "";
    foreach (explode(' ', $string) as $word) {
        // If it's a roman numeral, then add it as is
        if (preg_match("/M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3})[A-Z]?/", $word)) {
            $newString .= " " . $word;
        } else {
            $newString .= " " . ucfirst($word);
        }
    }

    // Trim it down
    return trim($newString);
}
