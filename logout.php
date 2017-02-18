<?php
//--------------------------------------------------

/* This php code simply loads the userVerify method to check */
/* if a valid user is logged in then destroys the session variables */
/* if any user exists */

//--------------------------------------------------

require "resources/php/config.php";

//--------------------------------------------------

session_start();
session_unset();
session_destroy();

header("Location: " . SERVER_URL);

//--------------------------------------------------
?>