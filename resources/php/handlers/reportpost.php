<?php
//--------------------------------------------------

/* This php code is used to report posts by taking the */
/* postid and session uid */

//--------------------------------------------------

require_once "../helpers/verification.class.php";
require_once "../helpers/database.class.php";
require_once "../helpers/misc.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();
 
if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("postid", $_POST)){
		
		$database = new Database;
		
		if($database->check("post_data", "postid", $_POST['postid'], 'i', 14)) {
			
			$misc = new Misc;
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			
			$postid = $database->retrieve_original_postid($_POST['postid']);
			
			// Check if the user has surpassed their 3 rep's a month //
			$nothingBefore = $misc->date_calculator("P4W");
			$result = $conn->query("SELECT id FROM post_reports WHERE userid = " . $_SESSION['uid'] . " AND TIMESTAMP(date, time) > '$nothingBefore' ORDER BY id DESC");
			
			if($result->num_rows != 3) {
				
				// Check if user has already sent a ticket //
				$result = $conn->query("SELECT id FROM post_reports WHERE userid = " . $_SESSION['uid'] . " AND postid = " . $postid);
				
				if($result->num_rows == 0) {
					// Insert ticket //
					$database->insert("post_reports", array("postid" => $postid, "userid" => $_SESSION['uid'], "date" => date("Y-m-d"), "time" => date("H:i:s")), "iiss");
				}
				
			
				// Find how long the post has been up //
				$after = $misc->date_calculator("PT10M");
				$result = $conn->query("SELECT * FROM post_data WHERE postid = " . $postid . " AND TIMESTAMP(date, time) < '$after'");
				
				if($result->num_rows > 0) {
					$postData = $result->fetch_assoc();
					
					// Collecting the number of post views //
					$result = $conn->query("SELECT COUNT(*) FROM post_loaded WHERE postid = " . $postData['postid']);
					$row = $result->fetch_assoc();
					
					$views = $row['COUNT(*)'];
					
					// Collect Number of tickets //
					$result = $conn->query("SELECT id FROM post_reports WHERE resolved = 0 AND postid = " . $postData['postid']);
					$tickets = $result->num_rows;
					
					// If more than 18% of people have reported suspend post //
					if((($tickets / $views) * 100) > 18) {
						
						$database->insert("post_suspended", $postData, "iiisisiiss");
						$database->insert("system_messages", array("userid" => $postData['userid'], "subject" => 'w'), "is");
						$conn->query("DELETE FROM post_data WHERE postid = " . $postData['postid'] . " OR originalpostid = " . $postData['postid']);
						$conn->query("DELETE FROM hashtag_data WHERE postid = " . $postData['postid']);
					}
					
				}
				
			}
			
		}
		
	}
	
}