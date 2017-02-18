<?php
//--------------------------------------------------

/* This php code is responsable for returning comments */
/* whenever a user clicks the 'comment' button on posts or the */
/* see more comments button */

//--------------------------------------------------

require_once "../helpers/verification.class.php";
require_once "../helpers/output.class.php";
require_once "../helpers/database.class.php";
require_once "../helpers/commentcompiler.function.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();
 
if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("postid", $_POST) && array_key_exists("written", $_POST)){
		
		$database = new Database;
		
		if($database->check("post_data", "postid", $_POST['postid'], 'i', 14)) {
			
			if(!$verify->comment($_POST['written'])){
				
				$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
				
				$postid = $database->retrieve_original_postid($_POST['postid']);
				
				// Prevent comment spam (allow only 10 per post) //
				$result = $conn->query("SELECT COUNT(*) FROM post_comments WHERE postid = $postid AND userid = " . $_SESSION['uid']);
				$row = $result->fetch_assoc();
				
				if($row['COUNT(*)'] <= 10) {
				
					$commentId = $database->insert("post_comments", array("postid" => $postid, "userid" => $_SESSION['uid'], "written" => $_POST['written'], "date" => date("Y-m-d"), 'time' => date("H:i:s")), "iisss");
					$database->post_statistics($postid, "comments", true);
					$database->user_statistics($database->collect_userid_from_postid($postid), "comments", true);
					
					$response = array();
					$response['comments'] = compile_comments($postid, 0, $commentId);
					$response['pid'] = $_POST['postid'];
					
					$output = new Output;
					echo $output->json_response($response);
					
				}
				
				$conn->close();
			}
		}
		
	}
	
}