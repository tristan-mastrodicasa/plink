<?php
//--------------------------------------------------

/* This code deals with returning modifying the settings 
 * of a specific user if the input is valid */
 
 /* Programmer Note: This file is probably one of the most
  * shameful I have written (Tristan), it is the prime example of poor planning
  * implementation and the infamous code copy-paste example.
  * Please don't be too mad */

//--------------------------------------------------

require_once "../helpers/verification.class.php";
require_once "../helpers/database.class.php";
require_once "../helpers/output.class.php";
require_once "../helpers/misc.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();

if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("u", $_POST) && array_key_exists("f", $_POST) && array_key_exists("be", $_POST)
		&& array_key_exists("p", $_POST) && array_key_exists("p2", $_POST) && array_key_exists("po", $_POST)) {
		
		$output = new Output;
		$database = new Database;
		$userSettings = $database->collect_user_settings($_SESSION['uid']);
		$response = array();
		$change = array('u' => true, 'f' => true, 'p' => true, 'be' => true);
		
		if($userSettings['username'][0] == $_POST['u']){
			$response['u'] = 0;
			$change['u'] = false;
		}
		
		if($userSettings['name'][0] == $_POST['f']){
			$response['f'] = 0;
			$change['f'] = false;
		}
		
		// Changing user backup email //
		if($userSettings['bemail'][0] == $_POST['be']) {
			$response['be'] = 0;
			$change['be'] = false;
		} else if (!$verify->user_credentials('lg', array($_SESSION['uid'], $_POST['po']))) {
			$response['be'] = 0;
			$response['po'] = "Incorrect password";
			$change['be'] = false;
		} else {
			$response['be'] = $verify->user_credentials('be', $_POST['be']);
			
			if(!$response['be']) $change['be'] = true;
			else $change['be'] = false;
			
		}
		
		if(!array_key_exists('u', $response) && $userSettings['username'][1]){
			$response['u'] = "You have already changed your username within 3 months from now";
		} else if (!array_key_exists('u', $response)) {
			// For some reason the row isin't deleted if expired in the temp_user_data table //
			// When run in the loopyness //
			$response['u'] = $verify->user_credentials('u', $_POST['u']);
		}
		
		if(!array_key_exists('f', $response) && $userSettings['name'][1]){
			$response['f'] = "You have already changed your first name within 3 months from now";
		}
		
		// Password Validation //
		if(!$_POST['p'] && !$_POST['p2']){
			$response['p'] = 0;
			$response['p2'] = 0;
			if(!array_key_exists('po', $response)) $response['po'] = 0;
			$change['p'] = false;
		} else {
			if($_POST['p']) {
				$response['p'] = $verify->user_credentials('p', $_POST['p']);
				
				if($_POST['p'] != $_POST['p2']){
					$response['p2'] = "Passwords do not match";
				} else $response['p2'] = 0;
				
				if(!$verify->user_credentials('lg', array($_SESSION['uid'], $_POST['po']))) {
					$response['po'] = "Incorrect password";
				} else $response['po'] = 0;
			}
		}
		
		foreach ($_POST as $key => $value) {
			if(!array_key_exists($key, $response) || $response[$key] != 0) $response[$key] = $verify->user_credentials($key, $_POST[$key]);
		}
		
		echo $output->json_response($response);
		
		$inputValid = true;
		foreach ($response as $key => $value) {
			if(gettype($response[$key]) == "string") $inputValid = false;
		}
		
		if($inputValid == true) {
			
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			
			// Username and First Name //
			if($change['u']){
				$conn->query("UPDATE user_data SET username = '" . $_POST['u'] . "' WHERE userid = " . $_SESSION['uid']);
				$database->insert("user_settings", array('userid' => $_SESSION['uid'], 'setting' => 'u', 'date' => date("Y-m-d"), 'time' => date("H:i:s")), "isss");
			}
			
			if($change['f']){
				// Convert the First name to have a capital first and the rest lowercase //
				$name = strtolower($_POST['f']);
				$name = ucfirst($name);
				
				$conn->query("UPDATE user_data SET name = '" . $name . "' WHERE userid = " . $_SESSION['uid']);
				$database->insert("user_settings", array('userid' => $_SESSION['uid'], 'setting' => 'f', 'date' => date("Y-m-d"), 'time' => date("H:i:s")), "isss");
			}
			
			// Backup Email //
			if($change['be']) {
				$conn->query("UPDATE user_data_extra SET bemail = '" . $_POST['be'] . "' WHERE userid = " . $_SESSION['uid']);
			}
			
			// Password //
			if($change['p']) {
				$misc = new Misc;
				$passData = $misc->generate_hashed_pass_and_salt($_POST['p']);
				
				$conn->query("UPDATE pass_data SET password = '" . $passData['hashedPass'] . "' WHERE userid = " . $_SESSION['uid']);
				$conn->query("UPDATE pass_data SET salt = '" . $passData['salt'] . "' WHERE userid = " . $_SESSION['uid']);
			}
		}
	}
}
?>