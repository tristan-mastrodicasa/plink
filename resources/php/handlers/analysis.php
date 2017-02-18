<?php
//--------------------------------------------------

/* This code deals with returning the statistics for
 * the profile analysis section in the profile */

//--------------------------------------------------

require_once "../helpers/postcompiler.function.php";
require_once "../helpers/output.class.php";
require_once "../helpers/verification.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();

if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
	
	$response = array();
	
	// Collecting the number of profile views //
	$result = $conn->query("SELECT COUNT(*) FROM profile_loaded WHERE huserid = " . $_SESSION['uid']);
	$row = $result->fetch_assoc();
	
	$response['profileSeen'] =  number_format($row['COUNT(*)']);
	
	// Collecting the number of post views //
	$result = $conn->query("SELECT COUNT(*) FROM post_loaded WHERE huserid = " . $_SESSION['uid']);
	$row = $result->fetch_assoc();
	
	$response['postSeen'] = number_format($row['COUNT(*)']);
	
	// Collecting various statistics //
	$result = $conn->query("SELECT subscribers, admirations, endorsements, reposts, comments, posts FROM user_statistics WHERE userid = " . $_SESSION['uid']);
	$row = $result->fetch_assoc();
	
	foreach(array_keys($row) as $key) {
		$response[$key] = number_format($row[$key]);
	}
	
	$output = new Output;
	echo $output->json_response($response);
}
?>