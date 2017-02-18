<?php
//--------------------------------------------------

/* This code deals with confirming if the user wants
 * to upload their new profile photo to the database */

//--------------------------------------------------

require_once "../helpers/verification.class.php";
require_once "../helpers/database.class.php";
require_once "../helpers/output.class.php";
require_once "../helpers/image.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();

if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("choice", $_POST)) {
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		if($_POST['choice'] == 'true') {
			$result = $conn->query("SELECT * FROM temp_photo_data WHERE userid = " . $_SESSION['uid']);
			$row = $result->fetch_assoc();
			
			$imageId = $row['id'];
			$imagePathM = USER_CONTENT . "images/temp/medium/$imageId" . ".jpg";
			$imagePathL = USER_CONTENT . "images/temp/large/$imageId" . ".jpg";
			
			$database = new Database;
			// Generate 'user changed picture' post //
			$postId = $database->insert("post_data", array('userid' => $_SESSION['uid'], 'type' => 2, 'written' => Null, 'topic' => 's', 
			'date' => date("Y-m-d"), 'time' => date("H:i:s")), "iissss");
			
			// Update users post statistics //
			$database->user_statistics($_SESSION['uid'], "posts", true);
			
			// Add new post stat row in the database //
			$database->insert("post_statistics", array('postid' => $postId), "i");
			
			$image = new Image;
			$image->enter_image($imagePathL, $postId);
			
			// Enter image into database as profile picture //
			$image->enter_image($imagePathM, 0, true);
			
			$output = new Output;
			
			// Return the Path to the tiny picture so that the profile icon can change //
			echo $output->json_response(array("tinyPic" => $database->collect_user_pic($_SESSION['uid'], "tiny")));
		} else $conn->query("DELETE FROM temp_photo_data WHERE userid = " . $_SESSION['uid']);
	}
}