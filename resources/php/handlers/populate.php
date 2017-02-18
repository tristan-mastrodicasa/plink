<?php
//--------------------------------------------------

/* This php code will be the scroll engine's main population handler, */
/* it manages what the users sees */
/* as they scroll down a certain layer */

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
	
	if(array_key_exists("layer", $_POST) && array_key_exists("last", $_POST)){
		
		/* -----------------------------------------------------------
		 * The input this handler takes is:
		 * Method : Which layer the posts will be populating
		 * Last: How many posts were sent
		 * ---------------------------------------------------------- */
		 
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		// Helper objects //
		$database = new Database;
		$misc = new Misc;
		$output = new Output;
		
		// Response Array //
		// The Response will send two arrays, 1 to hold //
		// meta data and the other to contain the compiled posts //
		$postData = array();
		$postData['posts'] = array();
		$postData['meta']['layer'] = $_POST['layer'];
		
		if($_POST['last'] && is_numeric($_POST['last'])) {
			
			if($_POST['last'] != 0) {
				$restrict = "AND postid < " . $_POST['last'];
			} else $restrict = '';
					
			// Append Results //
			$postData['meta']['method'] = 2;
		} else {
			$restrict = '';
			
			// Refresh Results //
			$postData['meta']['method'] = 1;
		}

		switch($_POST['layer']){
			case 'sb' : 
				if(array_key_exists("topic", $_POST) && array_key_exists("sort", $_POST) && array_key_exists("query", $_POST)) {
					
					$topic = $last = $param = '';
					
					if($_POST['topic'] && !$verify->post(0, array(1, $_POST['topic']))) { 
						$topic = "AND user_data.mtopic = '" . $_POST['topic'] . "'";
					}
					
					if($_POST['last'] && is_numeric($_POST['last'])) {
						$last = " LIMIT " . $_POST['last'] . ", 10 ";
						
						// Append Results //
						$postData['meta']['method'] = 2;
					} else {
						$last = " LIMIT 10";
						
						// Refresh Results //
						$postData['meta']['method'] = 1;
					}
					
					if($_POST['query']){
						$_POST['query'] = "%" . $_POST['query'] . "%";
						$param = "AND (user_data.username LIKE ? OR user_data.name LIKE ?)";
					}
					
					switch($_POST['sort']) {
					
						// Sort alphabetically //
						case 1:
							$query = "SELECT user_data.userid, user_data.name, user_data.username FROM user_data 
							INNER JOIN subscription_data ON user_data.userid=subscription_data.huserid WHERE subscription_data.cuserid = " 
							. $_SESSION['uid'] . " $topic $param ORDER BY user_data.name $last";
							
							break;
							
						// Sort by newest posts //
						case 2:
							
							$query = "SELECT user_data.userid, user_data.name, user_data.username FROM user_data
							INNER JOIN subscription_data ON user_data.userid=subscription_data.huserid INNER JOIN post_data ON
							subscription_data.huserid = post_data.userid WHERE subscription_data.cuserid = "
							. $_SESSION['uid'] . " $topic $param GROUP BY post_data.userid ORDER BY MAX(post_data.postid) DESC $last";
							
							break;
							
						// Sort by most popular users //
						case 3:
							
							$query = "SELECT user_data.userid, user_data.name, user_data.username FROM user_data
							INNER JOIN subscription_data ON user_data.userid=subscription_data.huserid WHERE subscription_data.cuserid = "
							. $_SESSION['uid'] . " $topic $param ORDER BY user_data.influence DESC $last";
							
							break;
						
						default: die();
					}
					
					if(!$_POST['query']) $result = $conn->query($query);
					else {
						
						$stmt = $conn->prepare($query);
						$stmt->bind_param('ss', $_POST['query'], $_POST['query']);
						
						$stmt->execute();
						$result = $stmt->get_result();
					}
					
					while ($row = $result->fetch_assoc()) {
						$row['profilePicture'] = $database->collect_user_pic($row['userid'], "small");
						array_push($postData['posts'], $row);
					}
				}
				break;
			
			case 'pa':
			case 'p':
				if(array_key_exists("userid", $_POST)) {
					
					if(($_POST['layer'] == 'p' && $database->check("user_data", "userid", $_POST['userid'], 'i')) || ($_POST['layer'] == 'pa' && $_POST['userid'] == $_SESSION['uid'])){
						
						$nothingBefore = $misc->date_calculator("P2W");
						
						if($_POST['layer'] == 'pa') $result = $conn->query("SELECT postid FROM post_data WHERE userid = " . $_POST['userid'] . " AND original = 1 AND TIMESTAMP(date, time) > '$nothingBefore' $restrict ORDER BY postid DESC LIMIT 3");
						else $result = $conn->query("SELECT postid FROM post_data WHERE userid = " . $_POST['userid'] . " AND TIMESTAMP(date, time) > '$nothingBefore' $restrict ORDER BY postid DESC LIMIT 3");
						
						if(!$result->num_rows) $postData['posts'] = null;
						else {
							while($row = $result->fetch_assoc()) {
								if($_POST['layer'] == 'pa') {
									
									// Collecting the number of post views //
									$result2 = $conn->query("SELECT COUNT(*) FROM post_loaded WHERE postid = " . $row['postid']);
									$seen = $result2->fetch_assoc();
									
									$postJson = compile_post($row['postid']);
									$postJson['postActions']['seenNum'] = $seen['COUNT(*)'];
									
									$postJson = $output->post_meta_apply($postJson, 2, 'pa');
									
									array_push($postData['posts'], $postJson);
								} else {
									array_push($postData['posts'], $output->post_meta_apply(compile_post($row['postid']), 2, 'p'));
								}
							}
						}
					}
				}
				
				break;
			
			case 'l':
				if(array_key_exists("topic", $_POST) && array_key_exists("sort", $_POST)) {
					
					if($_POST['last'] && is_numeric($_POST['last'])) {
						$last = " LIMIT " . $_POST['last'] . ", 3 ";
						// Append Results //
						$postData['meta']['method'] = 2;
					} else {
						$last = " LIMIT 3";
						// Refresh Results //
						$postData['meta']['method'] = 1;
					}
				
					$topic = '';
					
					if($_POST['topic'] && !$verify->post(0, array(1, $_POST['topic']))) { 
						$topic = "AND post_data.topic = '" . $_POST['topic'] . "'";
					}
					
					$nothingBefore = $misc->date_calculator("P2W");
					
					switch($_POST['sort']) {
						
						// Newest to Oldest //
						case 1:
							$query = "SELECT activity_data.postid FROM activity_data INNER JOIN post_data ON post_data.postid = activity_data.postid
							WHERE activity_data.cuserid = " . $_SESSION['uid'] . " AND activity_data.action = 1 AND activity_data.committed = 1 $topic AND TIMESTAMP(post_data.date, post_data.time) > '$nothingBefore' 
							ORDER BY post_data.postid DESC $last";
							break;
						
						// Oldest to Newest //
						case 2:
							$query = "SELECT activity_data.postid FROM activity_data INNER JOIN post_data ON post_data.postid = activity_data.postid
							WHERE activity_data.cuserid = " . $_SESSION['uid'] . " AND activity_data.action = 1 AND activity_data.committed = 1 $topic AND TIMESTAMP(post_data.date, post_data.time) > '$nothingBefore' 
							ORDER BY post_data.postid ASC $last";
							break;
						
						// Most Popular //
						case 3:
							$query = "SELECT activity_data.postid FROM activity_data INNER JOIN post_data ON post_data.postid = activity_data.postid INNER JOIN post_statistics ON post_statistics.postid = activity_data.postid 
							WHERE activity_data.cuserid = " . $_SESSION['uid'] . " AND activity_data.action = 1 AND activity_data.committed = 1 $topic AND TIMESTAMP(post_data.date, post_data.time) > '$nothingBefore' 
							ORDER BY post_statistics.endorsements DESC $last";
							break;
					}
					
					$result = $conn->query($query);
					$output = new Output;
					
					while ($row = $result->fetch_assoc()) array_push($postData['posts'], $output->post_meta_apply(compile_post($row['postid']), 2, 'l'));
				}
				break;
		}
		
		$conn->close();
		
		$output = new Output;
		
		// Returning the json object //
		echo $output->json_response($postData);
	}
}
?>