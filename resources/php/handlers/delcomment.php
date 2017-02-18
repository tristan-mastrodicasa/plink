<?php
//--------------------------------------------------

/* This php code is responsable for deleting comments */

//--------------------------------------------------

require_once "../helpers/verification.class.php";
require_once "../helpers/database.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();
 
if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("cid", $_POST)){
		
		$database = new Database;
		
		if($database->check("post_comments", "id", $_POST['cid'], 'i', 14)) {
			
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			
			$result = $conn->query("SELECT userid, postid FROM post_comments WHERE id = " . $_POST['cid']);
			$row = $result->fetch_assoc();
			
			$comUserId = $row['userid'];
			$postUserId = $database->collect_userid_from_postid($row['postid']);
			
			if($_SESSION['uid'] == $comUserId || $_SESSION['uid'] == $postUserId) {
				$conn->query("DELETE FROM post_comments WHERE id = " . $_POST['cid']);
				$database->post_statistics($row['postid'], "comments", false);
				$database->user_statistics($postUserId, "comments", false);
			}
			
			$conn->close();
		}
		
	}
	
}