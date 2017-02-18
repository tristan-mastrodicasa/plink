<?php
//--------------------------------------------------

/* This php code is responsable for updating the post */
/* text of a specific postid specified by the client */

//--------------------------------------------------

require_once "../helpers/verification.class.php";
require_once "../helpers/database.class.php";
require_once "../helpers/output.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();
 
if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("pid", $_POST) && array_key_exists("text", $_POST)){
		
		$database = new Database;
		
		if($database->check("post_data", "postid", $_POST['pid'], 'i', 14)) {
			
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			
			$postid = $database->retrieve_original_postid($_POST['pid']);
			
			$result = $conn->query("SELECT type FROM post_data WHERE postid = " . $postid);
			$row = $result->fetch_assoc();
			
			$error = 0;
			$written = null;
			
			if($_POST['text'] == '' && ($row['type'] == 2 || $row['type'] == 3)) $error = 0;
			else {
				if($row['type'] == 4) $type = 4;
				else $type = 1;
				
				$error = $verify->post($type, $_POST['text']);
				if($error == 0) $written = $_POST['text'];
			}
			
			if(!is_string($error)) {
				$stmt = $conn->prepare("UPDATE post_data SET written = ? WHERE postid = " . $postid);
				$stmt->bind_param('s', $written);
				$stmt->execute();
				$stmt->close();
			}
			
			$conn->close();
			
			if($error == 0 && $written != null) {
				$written = htmlspecialchars($written);
				$written = str_replace("\n", " <br/> ", $written);
			}
			
			$output = new Output;
			echo $output->json_response((array("errorCode" => $error, "text" => $written, "pid" => $_POST['pid'])));
		}
	}
}