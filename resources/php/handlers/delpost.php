<?php
//--------------------------------------------------

/* This php code is responsable for deleting a post if */
/* a postid is returned and then deleting it if the post has */
/* the same userid as the logged in user */

//--------------------------------------------------

require_once "../helpers/verification.class.php";
require_once "../helpers/database.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();
 
if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("pid", $_POST)){
		$database = new Database;
		if($database->check("post_data", "postid", $_POST['pid'], 'i')) $database->delete_post($_POST['pid']);
	}
	
}