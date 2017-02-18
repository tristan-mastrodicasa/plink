<?php
//--------------------------------------------------

/* This code deals with checking any URL that might not be */
/* correctly returning the associated media */

//--------------------------------------------------

require_once "../helpers/verification.class.php";
require_once "../helpers/database.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();

if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	$database = new Database;
	
	// For opengraph images //
	if(array_key_exists("url", $_POST)) {
		
		if(!$verify->is_url_image($_POST['url']) || $verify->check_timeout($_POST['url'], 2000)) {
			
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			
			// Remove the image from open graph //
			$stmt = $conn->prepare("UPDATE open_graph SET media = NULL, mediafit = NULL WHERE media = ?");
			$stmt->bind_param('s', $_POST['url']);
			$stmt->execute();
			$stmt->close();
			
			$conn->close();
			
		}
	}
}

?>