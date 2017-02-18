<?php
//--------------------------------------------------

/* This php code handles the signup proccesses of the website */

//--------------------------------------------------

require_once '../helpers/verification.class.php';
require_once '../helpers/database.class.php';
require_once '../helpers/output.class.php';
require_once '../helpers/misc.class.php';
require_once "../helpers/html.supplement.php";
require_once "../helpers/php-mailer/PHPMailerAutoload.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	// Checking that all correct input has been submitted. //
	// Hinders hackers trying to modify stuff (yes that means you cheeky script kiddy) //
	
	if(array_key_exists("f", $_POST) && array_key_exists("u", $_POST) && 
	array_key_exists("se", $_POST) && array_key_exists("p", $_POST) && 
	array_key_exists("reference", $_POST) && array_key_exists("step", $_POST) && 
	array_key_exists("captcha", $_POST)){
		
		$verify = new Verify;
		$output = new Output;
		$database = new Database;
		$response = array("step" => 0, "inputMethod" => 1);
		
		// Validation //
		
		// First Name //
		$response['form']['f'] = $verify->user_credentials('f', $_POST['f']);
		
		// Username //
		$response['form']['u'] = $verify->user_credentials('u', $_POST['u']);
		
		// School Email //
		$response['form']['se'] = $verify->user_credentials('se', $_POST['se']);
		
		// Password //
		$response['form']['p'] = $verify->user_credentials('p', $_POST['p']);
		
		// If there are no errors than enter into database //
		if(!$response['form']['f'] && !$response['form']['u'] && !$response['form']['se'] && !$response['form']['p'] && $_POST['step'] == 1){
			
			// Check if the captcha was completed successfully (if yes) //
			if($verify->captcha($_POST['captcha'])) {
				$response['step'] = 2;
				
				$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
				
				$date = date("Y-m-d");
				$time = date("H:i:s");
				
				$unique = 0;
				
				// Loop until a unique id is retrieved //
				while($unique == 0) {
					$bytes = openssl_random_pseudo_bytes(15);
					$hex = bin2hex($bytes);
					
					$stmt = $conn->prepare("SELECT activatekey FROM temp_user_data WHERE activatekey = ?");
					$stmt->bind_param("s", $hex);
					$stmt->execute();
					$stmt->store_result();
					$exists = $stmt->num_rows;
					
					if($exists == 0) $unique = 1;
					
					$stmt->close();
				}
				
				// Checks the school //
				$explodedEmail = explode('@', $_POST['se']);
				$domain = array_pop($explodedEmail);
				
				// Sets school //
				if($ACCEPTED_DOMAINS[0] || $ACCEPTED_DOMAINS[1]) $school = 1;
				else $school = 2;
				
				// Password Hashing using the pk .. whatever thingy //
				$misc = new Misc;
				$passData = $misc->generate_hashed_pass_and_salt($_POST['p']);
				
				// Enter the data into a temporary table //
				$stmt = $conn->prepare("INSERT INTO temp_user_data (username, name, email, school, password, salt, activatekey, date, time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
				$stmt->bind_param("sssisssss", $_POST['u'], $_POST['f'], $_POST['se'], $school, $passData['hashedPass'], $passData['salt'], $hex, $date, $time);
				
				$stmt->execute();
				$userId = $stmt->insert_id;
				$stmt->close();
				
				if($verify->user($_POST['reference'])){
					
					// Checks database for matches //
					$user = $database->id_collect("user_data", "username", $_POST['reference'], 's');
					$stmt = $conn->prepare("UPDATE temp_user_data SET reference = ? WHERE id = ?");
					$stmt->bind_param("ii", $user, $userId);
					
					$stmt->execute();
					$stmt->close();
				}
				
				$conn->close();
				
				 // Buffer all upcoming output...
				ob_start();
				
				// Send your response.
				echo $output->json_response($response);
				
				// Get the size of the output.
				$size = ob_get_length();
				
				// Disable compression (in case content length is compressed).
				header("Content-Encoding: none");
				
				// Set the content length of the response.
				header("Content-Length: {$size}");
				
				// Close the connection.
				header("Connection: close");
				
				// Flush all output.
				ob_end_flush();
				ob_flush();
				flush();
				
				// Send the email //
				$url = SERVER_URL . "confirm.php?id=" . $userId . "&actkey=" . $hex;
				$message = $HTML_SCHOOL_VERIFY[0] . $url . $HTML_SCHOOL_VERIFY[1] . $url . $HTML_SCHOOL_VERIFY[2];
				
				$mail = new PHPMailer;
				
				$misc->mailer_config($mail);
				
				$mail->addAddress($_POST['se']);
				
				$mail->isHTML(true);
				
				$mail->Subject = 'Validate Email';
				$mail->Body = $message;
				$mail->AltBody = "Visit $url to validate your email";
				
				$mail->send();
			} else {
				// Repeat Captcha //
				$response['step'] = 1;
			}
			
		}else if(!$response['form']['f'] && !$response['form']['u'] && !$response['form']['se'] && !$response['form']['p']) {
			// Ask for the user to complete the captcha //
			$response['step'] = 1;
		}
		
		echo $output->json_response($response);
	}
}
?>