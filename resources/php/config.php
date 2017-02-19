<?php
//--------------------------------------------------

/* Below is the system wide functions and constants */
/* The code below will make it easy to manage website wide */
/* data such as XP values, database permissions, accepted schools etc */

//--------------------------------------------------

define("SERVER_URL", "http://222.153.11.66/");
define("SERVER_NAME", "localhost");
define("USERNAME", "root");
define("PASSWORD", "");
define("DATABASE", "plink");
define("URL_REGEX", '#((https?|ftp)://(\S*?\.\S*?))([\s)\[\]{},;"\':<]|\.\s|$)#i');
define("USER_CONTENT", $_SERVER['DOCUMENT_ROOT'] . "user/");

define("REFERENCE_XP", 200);
define("ENDORSE_XP", 2);
define("REPOST_XP", 3);
define("SUBSCRIBE_XP", 5);
define("ADMIRATION_XP", 20);

// Toggle when testing for server or local machine //
define("PRODUCTION", false);

// $URL_REGEX = "/((http|https)\:\/\/)?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.([a-zA-Z0-9\&\.\/\?\:@\-_=#])*/i"

$ACCEPTED_DOMAINS = array("paraparaumucollege.school.nz", "pcol.school.nz", "kapiticollege.school.nz", "kc.school.nz", "otakicollege.school.nz");
$MONTH_DATES = ["", "Janurary", "Feburary", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

// s, n, rn are all reserved //
$TOPIC_INITIALS = ['ap', 'ac', 'c', 'e', 'f', 'fc', 'fu', 'g', 'hf', 'hc', 'lo', 'm', 'mu', 'ne', 'o', 'fg', 'st', 'sp', 't', 'tm', 'wn'];

// logout.php and confirm.php can be refered to as 'handlers' //
$CURRENT_PAGES = array("index", "home", "about", "terms", "release", "password-recovery", "banned");
$PORTAL_PAGES = array("index", "about", "terms", "release", "password-recovery");
?>