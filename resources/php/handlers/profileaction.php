<?php
//--------------------------------------------------

/* This php code is responsable for returning dealing 
 * with subscribing and removing subscriptions as well
 * as assigning the appropiate statistics and influence */

//--------------------------------------------------

require_once "../helpers/verification.class.php";
require_once "../helpers/database.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();

if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("huserid", $_POST) && array_key_exists("action", $_POST)) {
		
		$database = new Database;
		
		if($database->check("user_data", "userid", $_POST['huserid'], 'i') && $_POST['huserid'] != $_SESSION['uid']) {
			
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			
			switch($_POST['action']) {
				
				// Subscribe //
				case 1:
					$result = $conn->query("SELECT id FROM subscription_data WHERE cuserid = " . $_SESSION['uid'] . " AND huserid = " . $_POST['huserid']);
					
					if(!$result->num_rows){
						$database->insert("subscription_data", array('cuserid' => $_SESSION['uid'], 'huserid' => $_POST['huserid']), "ii");
						$database->user_statistics($_POST['huserid'], "subscribers", true);
						$database->influence_control($_POST['huserid'], SUBSCRIBE_XP, true);
					} else {
						$row = $result->fetch_assoc();
						$database->remove_row_if_expired("subscription_data", "id", $row['id'], "i", 0);
						$database->user_statistics($_POST['huserid'], "subscribers", false);
						$database->influence_control($_POST['huserid'], SUBSCRIBE_XP, false);
					}
					
					break;
				
				// Admire //
				case 2 :
					$result = $conn->query("SELECT id FROM admiration_data WHERE cuserid = " . $_SESSION['uid'] . " AND huserid = " . $_POST['huserid']);
					
					if(!$result->num_rows) {
						$result = $conn->query("SELECT huserid FROM admiration_data WHERE cuserid = " . $_SESSION['uid']);
						
						if(!$result->num_rows) {
							$database->insert("admiration_data", array('cuserid' => $_SESSION['uid'], 'huserid' => $_POST['huserid']), "ii");
							$database->user_statistics($_POST['huserid'], "admirations", true);
							$database->influence_control($_POST['huserid'], ADMIRATION_XP, true);
						} else {
							
							// If another user is admired remove their statistics //
							$row = $result->fetch_assoc();
							$database->remove_row_if_expired("admiration_data", "cuserid", $_SESSION['uid'], 'i', 0);
							$database->user_statistics($row['huserid'], "admirations", false);
							$database->influence_control($row['huserid'], ADMIRATION_XP, false);
							
							$database->insert("admiration_data", array('cuserid' => $_SESSION['uid'], 'huserid' => $_POST['huserid']), "ii");
							$database->user_statistics($_POST['huserid'], "admirations", true);
							$database->influence_control($_POST['huserid'], ADMIRATION_XP, true);
						}
					} else {
						$row = $result->fetch_assoc();
						$database->remove_row_if_expired("admiration_data", "id", $row['id'], 'i', 0);
						$database->user_statistics($_POST['huserid'], "admirations", false);
						$database->influence_control($_POST['huserid'], ADMIRATION_XP, false);
					}
					
					break;
			}
		}
	}
}
?>