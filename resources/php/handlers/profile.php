<?php
//--------------------------------------------------

/* This php code is responsable for returning profile data */
/* when a user wants to view either his/her or a public profile */

//--------------------------------------------------

require_once "../helpers/verification.class.php";
require_once "../helpers/database.class.php";
require_once "../helpers/misc.class.php";
require_once "../helpers/image.class.php";
require_once "../helpers/output.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();
 
if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("id", $_POST)){
		
		$database = new Database;
		$misc = new Misc;
		$image = new Image;
		$output = new Output;
		
		// Check if user id exists //
		if($database->check("user_data", "userid", $_POST['id'], 'i')){
			
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			
			// Update the profile loaded statistics //
			$result = $conn->query("SELECT id FROM profile_loaded WHERE cuserid = " . $_SESSION['uid'] . " AND huserid = " . $_POST['id']);
			
			if(!$result->num_rows) $database->insert("profile_loaded", array('cuserid' => $_SESSION['uid'], 'huserid' => $_POST['id']), "ii");
			
			// Create Response array //
			$response = array();
			
			$response['profileId'] = $_POST['id'];
			
			// Tells the client which set of buttons to display //
			if($_POST['id'] == $_SESSION['uid']) $response['profileOwner'] = true;
			else $response['profileOwner'] = false;
			
			$result = $conn->query("SELECT username, name, influence, mtopic, date FROM user_data WHERE userid = " . $_POST['id']);
			$row = $result->fetch_assoc();
			
			// Profile Owner Name //
			$response['profileName'] = $row['name'] . ' @' . $row['username'];
			
			// Collect level and xp //
			$response['profileXp'] = number_format($row['influence']);
			$response['profileXpToNext'] = number_format($misc->next_level_calculator($row['influence']));
			$response['profileLevel'] = $misc->level_calculator($row['influence']);
			
			// Collect Post Majority //
			$response['topic'] = $row['mtopic'];
			
			// Collect when user had joined //
			$date = explode('-', $row['date']);
			$response['joined'] = $MONTH_DATES[intval($date[1])] . ' ' . $date[0];
			
			// Collect statistics //
			$result = $conn->query("SELECT subscribers, admirations, endorsements, posts FROM user_statistics WHERE userid = " . $_POST['id']);
			$row = $result->fetch_assoc();
			
			$response['subscribers'] = $row['subscribers'];
			$response['admirations'] = $row['admirations'];
			$response['endorsements'] = $row['endorsements'];
			$response['posts'] = $row['posts'];
			
			if(!$response['profileOwner']){
				$result = $conn->query("SELECT id FROM subscription_data WHERE cuserid = " . $_SESSION['uid'] . " AND huserid = " . $response['profileId']);
				
				if(!$result->num_rows) $response['isSubscribed'] = false;
				else $response['isSubscribed'] = true;
				
				$result = $conn->query("SELECT id FROM admiration_data WHERE cuserid = " . $_SESSION['uid'] . " AND huserid = " . $response['profileId']);
				
				if(!$result->num_rows) $response['isAdmired'] = false;
				else $response['isAdmired'] = true;
			}
			
			// Collect profile picture //
			$response['profilePicture'] = $database->collect_user_pic($_POST['id'], "medium");
			
			// Find when user last logged in //
			$result = $conn->query("SELECT date, time FROM activity_data WHERE cuserid = " . $_POST['id'] . " ORDER BY id DESC LIMIT 1");
			if($result->num_rows == 0) $response['lastActive'] = "Never?";
			else{
				$row = $result->fetch_assoc();
				
				$diffInSeconds = $misc->diff_in_seconds_now($row['date'], $row['time']);
				
				if($diffInSeconds > 60) $suffix = " ago";
				else $suffix = "";
				
				if($diffInSeconds > 120) $response['lastActive'] = $misc->seconds_to_string($diffInSeconds) . $suffix;
				else $response['lastActive'] = "Now";
			}
			
			$conn->close();
			
			echo $output->json_response($response);
		}
		
	}
	
}
?>