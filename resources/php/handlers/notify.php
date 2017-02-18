<?php
//--------------------------------------------------

/* This file is simply called using post and takes the */
/* session 'uid' variable to check outstanding notifications */
/* for the user in an order of importance */

//--------------------------------------------------

require_once '../helpers/verification.class.php';
require_once '../helpers/misc.class.php';
require_once '../helpers/output.class.php';
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();
 
if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
	$response = array("message" => null);
	$misc = new Misc;
	
	if($verify->is_banned($_SESSION['uid'])) $response['message'] = 'b';
	
	// If no Ban //
	if($response['message'] == null) {
		
		$result = $conn->query("SELECT * FROM system_messages WHERE userid = " . $_SESSION['uid']);
		
		// Check if any messages are available //
		if($result->num_rows > 0){
			
			$messages = array();
			while($row = $result->fetch_assoc()){
				array_push($messages, $row['subject']);
			}
			
			// Add message in order of importance //
			if (in_array('w', $messages)) $response['message'] = 'w';
			
			// Remove the messages //
			$conn->query("DELETE FROM system_messages WHERE userid = " . $_SESSION['uid']);
			
		} else {
			// Check for level increase //
			$result = $conn->query("SELECT username, level, influence FROM user_data WHERE userid = " . $_SESSION['uid']);
			$row = $result->fetch_assoc();
			
			$newUserLevel = $misc->level_calculator($row['influence']);
			
			if($newUserLevel > $row['level']) {
				$conn->query("UPDATE user_data SET level = $newUserLevel WHERE userid = " . $_SESSION['uid']);
				$response['message'] = 'l';
				$response['level'] = $newUserLevel;
				$response['username'] = $row['username'];
			}
		}
		
	}
	
	$output = new Output;
	echo $output->json_response($response);
}

?>