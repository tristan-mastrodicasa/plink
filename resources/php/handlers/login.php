<?php
//--------------------------------------------------

/* This php code handles the login proccesses of the website */

//--------------------------------------------------

require_once '../helpers/verification.class.php';
require_once '../helpers/database.class.php';
require_once '../helpers/output.class.php';
require_once '../helpers/pbkdf2.function.php';
require_once '../helpers/misc.class.php';
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("u", $_POST) && array_key_exists("p", $_POST)){
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		$database = new Database;
		$verify = new Verify;
		$output = new Output;
		$response = array('p' => 0);
		
		// Quick Error Processing //
		function loginError () {
			global $response;
			$response['p'] = "Incorrect Username/Password";
		}
		
		// Validation //
		// Username / Password //
		if($verify->user_credentials('u', $_POST['u'] || $verify->user_credentials('p', $_POST['p']))) loginError();
		
		if(!$response['p']){
			
			$user = $database->id_collect("user_data", "username", $_POST['u'], 's');
			
			if(!$user) loginError();
			else {
				
				// Checks Password if username exists //
				$stmt = $conn->prepare("SELECT password, salt FROM pass_data WHERE userid = ?");
				$stmt->bind_param("i", $user);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($password, $salt);
				$stmt->fetch();
				
				$stmt->close();
				$conn->close();
				
				// Hash inputted Password //
				$hashedPass = pbkdf2("SHA256", $_POST['p'], $salt, 1500, 64);
				
				if($hashedPass == $password) {
					
					// Password Success //
					$misc = new Misc;
					$misc->log_in_user($user);
					
					if($verify->is_banned($user)) {
						$response['p'] = 'b';
						$response['uid'] = $user;
					} else $database->insert("activity_data", array('cuserid' => $user, 'huserid' => $user, 'action' => 7, 'date' => date("Y-m-d"), 'time' => date("H:i:s")), "iiiss");
					
				} else loginError();
			}
		}
		
		echo $output->json_response($response);
	}
}
?>