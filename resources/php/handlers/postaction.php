<?php
//--------------------------------------------------

/* This code deals with returning the rest of the string */
/* in a post as a 'see more' option */

//--------------------------------------------------

require_once "../helpers/database.class.php";
require_once "../helpers/verification.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();

if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	if(array_key_exists("action", $_POST) && array_key_exists("postid", $_POST)) {
		
		$database = new Database;
		
		if($database->check("post_data", "postid", $_POST['postid'], 'i', 14)) {
			
			if($_POST['action'] == 1){
				
				$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
				
				$postidList = array();
				$endorse = true;
				
				// Check if the post has an orginal ID //
				$result = $conn->query("SELECT originalpostid FROM post_data WHERE postid = " . $_POST['postid']); // Don't freak out it was checked by database->check()
				$row = $result->fetch_assoc();
				
				// Prepare the list of postid's to be actioned upon //
				if(array_key_exists("originalpostid", $row) && $row['originalpostid']) {
					
					// Users cannot like a post they have reposted //
					$result = $conn->query("SELECT id FROM activity_data WHERE action = 2 AND committed = 1 AND cuserid = " . $_SESSION['uid'] . " AND postid = " . $row['originalpostid']);
					if($result->num_rows > 0) $endorse = false;
					else {
						
						// If the user already liked the original post they cannot like it again through a repost //
						$result = $conn->query("SELECT id FROM activity_data WHERE action = 1 AND committed = 1 AND cuserid = " . $_SESSION['uid'] . " AND postid = " . $row['originalpostid']);
						
						if($result->num_rows == 0) {
							// If no rows exist than add the original and repost postid to endorse //
							array_push($postidList, $row['originalpostid']);
							
							// If the user liked the reposted post but unliked the original than liking the repost will not endorse the reposted post //
							$result = $conn->query("SELECT id FROM activity_data WHERE action = 1 AND committed = 1 AND cuserid = " . $_SESSION['uid'] . " AND postid = " . $_POST['postid']);
							
							if($result->num_rows == 0) array_push($postidList, $_POST['postid']);
						} else {
							
							// If the post was already liked from the original than the original post will be 'unliked' //
							array_push($postidList, $row['originalpostid']);
						}
					}
				} else array_push($postidList, $_POST['postid']);
				
				if($endorse) {
					foreach($postidList as $postid){
						
						$sqlFinder = "WHERE action = 1 AND postid = $postid AND cuserid = " . $_SESSION['uid'];
						
						// Check for an existing activity row in activity_data // // ******** //
						$result = $conn->query("SELECT huserid, committed FROM activity_data $sqlFinder");
						
						if($result->num_rows){
							$row = $result->fetch_assoc();
							$com = ($row['committed'] ? 0 : 1);
							$conn->query("UPDATE activity_data SET committed = $com $sqlFinder");
							$huserid = $row['huserid'];
						}else{
							$com = 1;
							
							// Collect the userid of the post //
							$result = $conn->query("SELECT userid FROM post_data WHERE postid = " . $postid); // Don't freak out it was checked by database->check()
							$row = $result->fetch_assoc();
							
							$database->insert("activity_data", array('cuserid' => $_SESSION['uid'], 'huserid' => $row['userid'], 
							'postid' => $postid, 'action' => 1, 'date' => date("Y-m-d"), 'time' => date("H:i:s")), "iiiiss");
							$huserid = $row['userid'];
						}
						
						// Update statistics //
						
						// Post Statistics //
						$database->post_statistics($postid, "endorsements", $com);
						
						// User Statistics //
						$database->user_statistics($huserid, "endorsements", $com);
						
						// Influence //
						$database->influence_control($huserid, ENDORSE_XP, $com);
						
					}
				}
			} else if($_POST['action'] == 2) {
				
				$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
				
				// Check if this is the original post, otherwise return original //
				$result = $conn->query("SELECT userid, originalpostid FROM post_data WHERE postid = " . $_POST['postid']);
				$row = $result->fetch_assoc();
				
				// Assinging the postid and host's userid //
				if($row['originalpostid']){
					$postid = $row['originalpostid'];
					$result = $conn->query("SELECT userid FROM post_data WHERE postid = " . $postid);
					$row = $result->fetch_assoc();
					$userid = $row['userid'];
				}
				else if (!$row['originalpostid'] && ($row['userid'] != $_SESSION['uid'])){
					$postid = $_POST['postid'];
					$userid = $row['userid'];
				}
				else die();
				
				// Check if user has already reposted this post //
				$result = $conn->query("SELECT committed FROM activity_data WHERE postid = $postid AND action = 2 AND cuserid = " . $_SESSION['uid']);
				$reposting = true;
				
				// If not than repost //
				if(!$result->num_rows) {
					
					// Collect post info for repost //
					$result = $conn->query("SELECT userid, type, topic, date, time FROM post_data WHERE postid = $postid");
					$row = $result->fetch_assoc();
					
					// Enter reference post //
					$postId = $database->insert("post_data", array('userid' => $_SESSION['uid'], 'type' => $row['type'], 
					'topic' => $row['topic'], 'original' => 0, 'originalpostid' => $postid, 'date' => $row['date'], 'time' => $row['time']), "iisiiss");
					
					// Activity Data //
					$database->insert("activity_data", array('cuserid' => $_SESSION['uid'], 'huserid' => $userid, 'postid' => $postid, 'action' => 2,
					'committed' => 1, 'date' => date("Y-m-d"), 'time' => date("H:i:s")), "iiiiiss");
					
				// If so than remove the reposted post //
				} else {
					
					$reposting = false;
					
					// Delete rows //
					$result = $conn->query("SELECT postid FROM post_data WHERE originalpostid = $postid AND userid = " . $_SESSION['uid']);
					$row = $result->fetch_assoc();
					
					$result = $conn->query("SELECT id FROM activity_data WHERE action = 2 AND postid = $postid AND cuserid = " . $_SESSION['uid']);
					$rowId = $result->fetch_assoc();
					
					$database->remove_row_if_expired("post_data", "postid", $row['postid'], 'i', 0);
					$database->remove_row_if_expired("activity_data", "id", $rowId['id'], 'i', 0);
				}
				
				// Post Statistics //
				$database->post_statistics($postid, "reposts", $reposting);
				
				// User Statistics //
				$database->user_statistics($_SESSION['uid'], "posts", $reposting);
				$database->user_statistics($userid, "reposts", $reposting);
				
				// Influence //
				$database->influence_control($userid, REPOST_XP, $reposting);
				
				$conn->close();
			}
		}
	}
}
?>