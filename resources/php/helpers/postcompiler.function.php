<?php
//--------------------------------------------------

/* This code is responsible for constructing post data 
 * into a json object which can be decoded by the client
 * to create the posts */

//--------------------------------------------------

require_once "database.class.php";
require_once "misc.class.php";
require_once "image.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

function compile_post($postid){
	
	/* -----------------------------------------------------------
	 * This script takes the id of a post and spits out all relevant
	 * information on it in a JSON object to be passed to the client
	 * and deciphered, the structure is like this:
	 * $data : 
	 * 		postMeta
	 * 			postId
	 * 			userId
	 * 			insertId (client generated)
	 *		postHead
	 * 			userPicture
	 * 			userName
	 * 			userUsername
	 * 			postType (could be a repost)
	 * 			postTopic
	 * 			timePosted
	 * 			reposterName (if reposted)
	 * 			reposterId (if reposted)
	 * 		postContent
	 * 			written
	 * 			writtenPrev (True if the written is a preview) (For large posts)
	 * 			imageUrl
	 * 		postActions
	 * 			endNum
	 * 			repNum
	 * 			comNum
	 * 			userEnd (If the user had commited an action)
	 * 			userRep
	 * 			userCom
	 * 		openGraph
	 * 			url
	 * 			title
	 * 			description
	 * 			media
	 * 			mediaSize
	 * 		
	 * When a post is loaded then the database will be told that
	 * it had been uploaded, both the reposted version and the original
	 * ---------------------------------------------------------- */
	 
	$database = new Database;
	$misc = new Misc;
	$image = new Image;
	
	if($database->check("post_data", "postid", $postid, 'i')){
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		// Create the JSON object (array) //
		$data = array();
		$openGraph = false;
		
		// Collect data from post_data //
		$result = $conn->query("SELECT userid, type, written, url, topic, original, originalpostid, date, time FROM post_data WHERE postid = " . $postid);
		$row = $result->fetch_assoc();
		
		$data['postHead']['postType'] = $row['type'];
		$data['postHead']['postTopic'] = $row['topic'];
		$data['postMeta']['postId'] = $postid;
		
		// If the post is not original (reposted) //
		// then collect the rest of the information from //
		// the original poster //
		if(!$row['original']){
			$result = $conn->query("SELECT userid, name FROM user_data WHERE userid = " . $row['userid']);
			$reposterName = $result->fetch_assoc();
			
			$data['postHead']['reposterName'] = $reposterName['name'];
			$data['postHead']['reposterId'] = $reposterName['userid'];
			$data['postMeta']['postRef'] = $row['originalpostid'];
			
			$result = $conn->query("SELECT postid, userid, written, url, date, time FROM post_data WHERE postid = " . $row['originalpostid']);
			$row = $result->fetch_assoc();
			
			// Same input but row is different than below at *** //
			$data['postMeta']['userId'] = $row['userid'];
			$written = $misc->incremental_text_displayer(0, $row['written']);
			
			$userid = $row['userid'];
			
			$date = $row['date'];
			$time = $row['time'];
			
			if($row['url']) $openGraph = True;
			
			$postid = $row['postid'];
		}else{
			
			// *** //
			$data['postHead']['reposterName'] = null;
			
			$data['postMeta']['userId'] = $row['userid'];
			$written = $misc->incremental_text_displayer(0, $row['written']);
			
			$userid = $row['userid'];
			
			$date = $row['date'];
			$time = $row['time'];
			
			if($row['url']) $openGraph = True;
		}
		
		// Sort out the written input //
		if($written != $row['written']) $data['postContent']['writtenPrev'] = True;
		else $data['postContent']['writtenPrev'] = False;
		$written = htmlspecialchars($written);
		$written = str_replace("\n", " <br/> ", $written);
		$data['postContent']['written'] = $written;
		
		// Find open graph info //
		if($openGraph){
			$result = $conn->query("SELECT url, title, description, media, mediafit FROM open_graph WHERE postid = " . $postid);
			$row = $result->fetch_assoc();
			
			$data['openGraph']['url'] = $row['url'];
			$data['openGraph']['title'] = $row['title'];
			$data['openGraph']['description'] = $row['description'];
			$data['openGraph']['media'] = $row['media'];
			$data['openGraph']['mediaSize'] = $row['mediafit'];
		}
		
		// Collect image URL if it's an image //
		if($data['postHead']['postType'] == 2) $data['postContent']['imageUrl'] = $image->retrieve_image($postid);
		
		// Calculate the time from when it was posted //
		$postedAt = strtotime($date . ' ' . $time);
		$timeNow = strtotime(date('Y-m-d H:i:s'));
		$diffInSeconds = $timeNow - $postedAt;
		
		$data['postHead']['timePosted'] = $misc->seconds_to_string($diffInSeconds);
		
		// Now collect info on user //
		$result = $conn->query("SELECT username, name FROM user_data WHERE userid = " . $userid);
		$row = $result->fetch_assoc();
		
		$data['postHead']['userUsername'] = $row['username'];
		$data['postHead']['userName'] = $row['name'];
		
		// Collect user profile picture //
		$data['postHead']['userPicture'] = $database->collect_user_pic($userid, "small");
		
		// Collect the post statistics //
		$result = $conn->query("SELECT endorsements, reposts, comments FROM post_statistics WHERE postid = " . $postid);
		$row = $result->fetch_assoc();
		
		$data['postActions']['endNum'] = $row['endorsements'];
		$data['postActions']['repNum'] = $row['reposts'];
		$data['postActions']['comNum'] = $row['comments'];
		
		// Collect the user's actions //
		$result = $conn->query("SELECT action FROM activity_data WHERE postid = " . $postid . " AND cuserid = " . $_SESSION['uid'] . " AND committed = 1");
		if($result->num_rows){
			while($row = $result->fetch_assoc()){
				if($row['action'] == 1) $data['postActions']['userEnd'] = True;
				if($row['action'] == 2) $data['postActions']['userRep'] = True;
				if($row['action'] == 3) $data['postActions']['userCom'] = True;
			}
		}
		
		// Update database with loaded information //
		if($data['postMeta']['postId'] != $postid) $postid = $data['postMeta']['postId'];
		
		$result = $conn->query("SELECT id FROM post_loaded WHERE cuserid = " . $_SESSION['uid']. " AND postid = " . $postid);
		
		if(!$result->num_rows) $database->insert("post_loaded", array('cuserid' => $_SESSION['uid'], 'huserid' => $userid, 'postid' => $postid), 'iii');
		
		return $data;
	}else return False;
}
?>