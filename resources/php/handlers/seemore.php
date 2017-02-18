<?php
//--------------------------------------------------

/* This code deals with returning the rest of the string */
/* in a post as a 'see more' option */

//--------------------------------------------------

require_once "../helpers/verification.class.php";
require_once "../helpers/database.class.php";
require_once "../helpers/misc.class.php";
require_once "../helpers/output.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();

if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("postid", $_POST) && array_key_exists("increment", $_POST)){
		
		$database = new Database;
		$misc = new Misc;
		$output = new Output;
		
		if($database->check("post_data", "postid", $_POST['postid'], 'i')){
			
			$postid = $database->retrieve_original_postid($_POST['postid']);
			
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			$result = $conn->query("SELECT written FROM post_data WHERE postid = " . $postid);
			$row = $result->fetch_assoc();
			$conn->close();
			
			if($_POST['increment'] == 0) $returnedWritten = $row['written'];
			else $returnedWritten = $misc->incremental_text_displayer($_POST['increment'], $row['written']);
			
			if($returnedWritten == $row['written']) $writtenPrev = False;
			else $writtenPrev = True;
			
			if($_POST['increment'] != 0) {
				$returnedWritten = htmlspecialchars($returnedWritten);
				$returnedWritten = str_replace("\n", " <br/> ", $returnedWritten);
			}
			
			echo $output->json_response(array("written" => $returnedWritten, "more" => $writtenPrev, "postid" => $_POST['postid'], "increment" => $_POST['increment'] + 1));
		}
		
	}
}
?>