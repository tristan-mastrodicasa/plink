<?php
//--------------------------------------------------

/* This php code is responsable for recovering lost passwords */

//--------------------------------------------------

require_once "../helpers/database.class.php";
require_once "../helpers/misc.class.php";
require_once "../helpers/output.class.php";
require_once "../helpers/verification.class.php";
require_once "../helpers/html.supplement.php";
require_once "../helpers/php-mailer/PHPMailerAutoload.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------
 
if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("step", $_POST)) {
			
		if($_POST['step'] == "eu") {
			
			if(array_key_exists("username", $_POST)) {
				$database = new Database;
				
				if($database->check("user_data", "username", $_POST['username'], 's')) {
					
					$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
					
					// Retrieve the userid //
					$result = $conn->query("SELECT userid FROM user_data WHERE username = '" . $_POST['username'] . "'");
					$row = $result->fetch_assoc();
					$userid = $row['userid'];
					
					// Check how many password recovery emails have already ben sent within the day //
					$misc = new Misc;
					$nothingBefore = $misc->date_calculator("P1D");
					
					$result = $conn->query("SELECT id FROM password_recovery WHERE userid = $userid AND TIMESTAMP(date, time) > '$nothingBefore'");
					
					// if less than 3 have been sent, send another one //
					if($result->num_rows < 3) { 
						$results = $conn->query("SELECT bemail FROM user_data_extra WHERE userid = $userid");
						$row = $results->fetch_assoc();
						
						$bytes = openssl_random_pseudo_bytes(50);
						$hex = bin2hex($bytes);
						
						$database->insert("password_recovery", array("userid" => $userid, "code" => $hex, 'date' => date("Y-m-d"), 'time' => date("H:i:s")), "isss");
						
						// Send the email //
						$url = SERVER_URL . "password-recovery/" . $userid . "/" . $hex;
						$message = $HTML_PASS_RESET[0] . $url . $HTML_PASS_RESET[1] . $url . $HTML_PASS_RESET[2];
						
						$mail = new PHPMailer;
						
						$misc->mailer_config($mail);
						
						$mail->addAddress($row['bemail']);
						
						$mail->isHTML(true);
						
						$mail->Subject = 'Reset Password';
						$mail->Body = $message;
						$mail->AltBody = "Visit $url to reset your password";
						
						$mail->send();
					}
				}
			}
		} else if ($_POST['step'] == "np") {
			
			if(array_key_exists("p", $_POST) && array_key_exists("p2", $_POST) && array_key_exists("userid", $_POST) && array_key_exists("code", $_POST)) {
				
				$verify = new Verify;
				$response = array();
				
				if($verify->password_recovery_code($_POST['userid'], $_POST['code'])) {
					
					$passStatus = $verify->user_credentials('p', $_POST['p']);
					
					if($passStatus == 0) {
						
						if($_POST['p'] == $_POST['p2']) {
							$response['p'] = $passStatus;
							
							// Enter password //
							$misc = new Misc;
							$passData = $misc->generate_hashed_pass_and_salt($_POST['p']);
							
							$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
							
							$conn->query("UPDATE pass_data SET password = '" . $passData['hashedPass'] . "' WHERE userid = " . $_POST['userid']);
							$conn->query("UPDATE pass_data SET salt = '" . $passData['salt'] . "' WHERE userid = " . $_POST['userid']);
							
							// Delete from password_recovery //
							$conn->query("DELETE FROM password_recovery WHERE userid = " . $_POST['userid']);
							
							// Log in //
							$misc->log_in_user($_POST['userid']);
							
						} else {
							$response['p'] = "Passwords do not match";
						}
						
					} else $response['p'] = $passStatus;
					
					$output = new Output;
					echo $output->json_response($response);
				}
			}
		}
	}
}