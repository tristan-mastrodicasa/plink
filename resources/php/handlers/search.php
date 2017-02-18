<?php
//--------------------------------------------------

/* This php code is the main search handler which deals with */
/* searching in the database based on certain inputs */

//--------------------------------------------------

require_once "../helpers/postcompiler.function.php";
require_once "../helpers/verification.class.php";
require_once "../helpers/database.class.php";
require_once "../helpers/output.class.php";
require_once "../helpers/misc.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();

if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("query", $_POST) && array_key_exists("last", $_POST)){
		
		if(!$verify->comment($_POST['query'])) {
			
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			$response = array("posts" => array(), "meta" => array("layer" => 's'));
			
			// Clean the query into a single sentence //
			$query = array_filter(explode(' ', $_POST['query']), 'strlen');
			$query = reset($query);
			
			$misc = new Misc;
			$database = new Database;
			$output = new Output;
			$qType = $misc->search_type($query);
			
			if($qType == 'un') $query = substr($query, 1);
			
			if($qType == 'h'){
				$response['meta']['type'] = 'h';
				$offset = 3;
			} else {
				$response['meta']['type'] = 'u';
				$offset = 10;
			}
			
			if($_POST['last'] && is_numeric($_POST['last'])) {
				$last = " LIMIT " . $_POST['last'] . ", $offset ";
				// Append Results //
				$response['meta']['method'] = 2;
			} else {
				$last = " LIMIT $offset";
				
				// Refresh Results //
				$response['meta']['method'] = 1;
			}
			
			if($qType == 'h') {
				
				$keepOutDuplicates = '';
				
				if(array_key_exists("hashtagFirstId", $_POST)) {
					if(is_numeric($_POST['hashtagFirstId'])) {
						if($database->check("hashtag_data", "id", $_POST['hashtagFirstId'], 'i')) {
							$keepOutDuplicates = "AND id <= " . $_POST['hashtagFirstId'];
						}
					}
				}
				
				// All hashtags are stored as lowercase //
				$query = strtolower($query);
				
				$stmt = $conn->prepare("SELECT id, postid FROM hashtag_data WHERE hashtag = ? $keepOutDuplicates ORDER BY id DESC $last");
				$stmt->bind_param('s', $query);
				$stmt->execute();
				$res = $stmt->get_result();
				$stmt->close();
				
				$i = 0;
				while ($row = $res->fetch_assoc()) {
					// Find the first hashtag id returned //
					if($i == 0) $firstId = $row['id'];
					$i++;
					
					array_push($response['posts'], $output->post_meta_apply(compile_post($row['postid']), 2, 's'));
				}
				
				if($keepOutDuplicates == '' && count($response['posts']) > 0) {
					$response['meta']['hashtagFirstId'] = $firstId;
				} else if ($keepOutDuplicates != '') {
					$response['meta']['hashtagFirstId'] = $_POST['hashtagFirstId'];
				}
				
			} else if ($qType == 'un' || $qType == 'f') {
				
				if(!$verify->user_credentials($qType, $query)) {
					$query = "%$query%";
					$column = ($qType == 'un') ? 'username' : 'name';
					
					$stmt = $conn->prepare("SELECT userid, username, name FROM user_data WHERE $column LIKE ? $last");
					$stmt->bind_param('s', $query);
					$stmt->execute();
					$res = $stmt->get_result();
					$stmt->close();
					
					while ($row = $res->fetch_assoc()) {
						$row['profilePicture'] = $database->collect_user_pic($row['userid'], "small");
						array_push($response['posts'], $row);
					}
				}
			}
			
			$conn->close();
			echo $output->json_response($response);
		}
	}
	
}