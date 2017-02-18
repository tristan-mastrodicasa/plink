<?php
//--------------------------------------------------

/* This php code provide variables which are added to the website
 * site wide (e.g opengraph for for portal pages, e.t.c) */

//--------------------------------------------------

require_once __DIR__ . "/../config.php";

//--------------------------------------------------

// Password reset email //
$HTML_PASS_RESET = [
	"<p>To reset your password click on the link below or copy-paste it into the browser's search bar</p><br><a href='", // The Target Url
	"' target='_blank'>", // Target Url
	"</a>"
];

// Validate email //
$HTML_SCHOOL_VERIFY = [
	"<p>To validate your email click on the link below or copy-paste it into the browser's search bar</p><br><a href='", // The Target Url
	"' target='_blank'>", // Target Url
	"</a>"
];
?>