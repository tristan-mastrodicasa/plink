<?php
//--------------------------------------------------

/* This php code is responsable for returning comments */
/* whenever a user clicks the 'comment' button on posts or the */
/* see more comments button */

//--------------------------------------------------

require_once "../helpers/verification.class.php";
require_once "../helpers/output.class.php";
require_once "../helpers/database.class.php";
require_once "../helpers/misc.class.php";
require_once "../helpers/commentcompiler.function.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();
 
if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("postid", $_POST) && array_key_exists("last", $_POST)){
		
		$database = new Database;
		
		if($database->check("post_data", "postid", $_POST['postid'], 'i', 14)) {
			
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			$response = array("pid" => $_POST['postid'], "fraction" => '', "allComments" => false, "ownPost" => false);
			
			$postid = $database->retrieve_original_postid($_POST['postid']);
			
			if($database->collect_userid_from_postid($postid) == $_SESSION['uid']) $response['ownPost'] = true;
			
			$response['comments'] = compile_comments($postid, $_POST['last']);
			
			if(count($response['comments']) < 3) {
				$response['allComments'] = true;
				$response['fraction'] = 0;
			} else {
				
				if($_POST['last'] && is_numeric($_POST['last'])) {
					
					if($_POST['last'] != 0) {
						$restrict = "AND id < " . $_POST['last'];
					} else $restrict = '';
					
				} else $restrict = '';
				
				$result = $conn->query("SELECT id FROM post_comments WHERE postid = " . $postid . " $restrict ORDER BY id");
				$response['fraction'] = ($result->num_rows - 3);
			}
			
			$conn->close();
			
			if($_POST['last'] == 0) $response['buttonClicked'] = true;
			else $response['buttonClicked'] = false;
			
			$output = new Output;
			echo $output->json_response($response);
		}
	}
	
}