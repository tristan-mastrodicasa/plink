<?php
//--------------------------------------------------

/* This code is can compile an array of comments and 
 * returns the array. You can also specify a specific
 * comment id (to return your own comment) */

//--------------------------------------------------

require_once "database.class.php";
require_once "misc.class.php";
require_once "image.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

function compile_comments($postid, $last, $commentId = false) {
	
	$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
	$database = new Database;
	$commentArray = array();
	
	if($commentId == false) {
		if($last && is_numeric($last)) {
			$last = "AND id < $last";
		} else $last = '';
		
		$results = $conn->query("SELECT id, userid, written, date, time FROM post_comments WHERE postid = " . $postid . " $last ORDER BY id DESC LIMIT 3");
	} else {
		$results = $conn->query("SELECT id, userid, written, date, time FROM post_comments WHERE id = $commentId");
	}
	
	while($row = $results->fetch_assoc()) {
		
		// There are quite a few similarities in profile.php //
		$comment = array();
		
		$result = $conn->query("SELECT username, name FROM user_data WHERE userid = " . $row['userid']);
		$row2 = $result->fetch_assoc();
	
		// Profile Owner Name //
		$comment['profileUserName'] = ' @' . $row2['username'];
		$comment['profileName'] = $row2['name']; 
		
		$comment['profilePic'] = $database->collect_user_pic($row['userid'], "tiny");
		$comment['userid'] = $row['userid'];
		$comment['comid'] = $row['id'];
		
		// Same in profile.php //
		$misc = new Misc;
		
		$diffInSeconds = $misc->diff_in_seconds_now($row['date'], $row['time']);
		
		if($diffInSeconds > 120) $comment['time'] = $misc->seconds_to_string($diffInSeconds);
		else $comment['time'] = "Now";
		
		$comment['written'] = $row['written'];
		
		$comment['written'] = htmlspecialchars($comment['written']);
		$comment['written'] = str_replace("\n", " ", $comment['written']);
		
		array_push($commentArray, $comment);
	}
	
	$conn->close();
	
	return $commentArray;
}