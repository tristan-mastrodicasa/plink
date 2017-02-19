<?php
//--------------------------------------------------

/* This code deals with changing the users profile picture */

//--------------------------------------------------

require_once "../helpers/verification.class.php";
require_once "../helpers/output.class.php";
require_once "../helpers/image.class.php";
require_once "../helpers/database.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();

if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("imageSrc", $_FILES)) {
		$response = array("error" => 0);
		$response['error'] = $verify->image($_FILES['imageSrc']);
		
		if($response['error'] == 0) {
			
			$image = new Image;
			$newImageM = $image->generate_profile_image($_FILES['imageSrc'], "medium");
			$newImageL = $image->generate_profile_image($_FILES['imageSrc'], "large");
			
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			$result = $conn->query("SELECT * FROM temp_photo_data WHERE userid = " . $_SESSION['uid']);
			
			if($result->num_rows > 0) {
				$conn->query("DELETE FROM temp_photo_data WHERE userid = " . $_SESSION['uid']);
			}
			
			$conn->close();
			
			$database = new Database;
			$id = $database->insert("temp_photo_data", array("userid" => $_SESSION['uid']), 'i');
			
			$imagePath = USER_CONTENT . "images/temp/medium/$id" . ".jpg";
			imagejpeg($newImageM, $imagePath, 100);
			
			$imagePath = USER_CONTENT . "images/temp/large/$id" . ".jpg";
			imagejpeg($newImageL, $imagePath, 100);
			
			$response['tempImagePath'] = SERVER_URL . "user/images/temp/medium/$id" . ".jpg";
			
			// Watch Out For Possible Memory Leaks //
			imagedestroy($newImageL);
			imagedestroy($newImageM);
		}
		
		$output = new Output;
		$output->json_through_iframe($response, "animation.profile.settings.profilePicture");
	}
	
}
?>