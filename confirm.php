<?php
//--------------------------------------------------

/* This code is used to confirm profiles when the user */
/* Visits the link sent to his/her email (using get method) */

//--------------------------------------------------

require_once 'resources/php/helpers/database.class.php';
require_once 'resources/php/helpers/misc.class.php';
require_once 'resources/php/config.php';

//--------------------------------------------------

if($_SERVER['REQUEST_METHOD'] == "GET"){
	
	if(array_key_exists("id", $_GET) && array_key_exists("actkey", $_GET)){
		
		/* -----------------------------------------------------------
		 * First the id of the profile is checked to see if it exists 
		 * as well as the key, then the temporary information is taken 
		 * from the temp table and inserted into the respective tables
		 * in the database
		 * ---------------------------------------------------------- */
		
		$database = new Database;
		
		if($database->check("temp_user_data", "id", $_GET['id'], 'i')){
			
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			$results = array('Id' => null, 'Username' => null, 'Name' => null, 
			'Email' => null, 'School' => null, 'Password' => null, 'Reference' => null, 
			'ActKey' => null, 'Date' => null, 'Time' => null);
			$todayD = date("Y-m-d");
			$todayT = date("H:i:s");
			
			// Selects the information from the temporary table //
			$stmt = $conn->prepare("SELECT * FROM temp_user_data WHERE id = ?");
			$stmt->bind_param("i", $_GET['id']);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($results['Id'], $results['Username'], $results['Name'], $results['Email'], 
			$results['School'], $results['Password'], $results['Salt'], $results['Reference'], $results['ActKey'], $results['Date'], 
			$results['Time']);
			
			$stmt->fetch();
			$stmt->close();
			
			// Inserts the collected data into the real databases //
			if($results['ActKey'] == $_GET['actkey']){
				
				// Convert the First name to have a capital first and the rest lowercase //
				$results['Name'] = strtolower($results['Name']);
				$results['Name'] = ucfirst($results['Name']);
				
				// Inserting User data //
				$stmt = $conn->prepare("INSERT INTO user_data (username, name, date, time) VALUES (?, ?, ?, ?)");
				$stmt->bind_param("ssss", $results['Username'], $results['Name'], $todayD, $todayT);
				
				$stmt->execute();
				$userId = $stmt->insert_id;
				$stmt->close();
				
				// Inserting Password //
				$stmt = $conn->prepare("INSERT INTO pass_data (userid, password, salt) VALUES (?, ?, ?)");
				$stmt->bind_param("iss", $userId, $results['Password'], $results['Salt']);

				$stmt->execute();
				$stmt->close();
				
				// Inserting Email and School //
				$stmt = $conn->prepare("INSERT INTO user_data_extra (userid, email, bemail, school) VALUES (?, ?, ?, ?)");
				$stmt->bind_param("issi", $userId, $results['Email'], $results['Email'], $results['School']);

				$stmt->execute();
				$stmt->close();
				
				// Generating user statistics row //
				$stmt = $conn->prepare("INSERT INTO user_statistics (userid) VALUES (?)");
				$stmt->bind_param("i", $userId);

				$stmt->execute();
				$stmt->close();
				
				$database->remove_row_if_expired("temp_user_data", "id", $results['Id'], 'i', 0);
				$database->insert("activity_data", array('cuserid' => $userId, 'huserid' => $userId, 'action' => 7, 'date' => date("Y-m-d"), 'time' => date("H:i:s")), "iiiss");
				
				if($results['Reference'] != null) $database->influence_control($results['Reference'], REFERENCE_XP, true);
			}
			
			$conn->close();
			
			// Redirect to welcome page //
			$misc = new Misc;
			$misc->log_in_user($userId);
			
			header("Location: " . SERVER_URL . "home/");
			
		}else{
			echo "This registry has expired. To sign up again click <a href='" . SERVER_URL . "'>here</a>";
		}
		
	}
}
?>