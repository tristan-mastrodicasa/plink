<?php
//--------------------------------------------------

/* This php code holds useable functions throughout the website (mostly for input validation) */
/* First systemwide is imported (holds systemwide variables) and timezone is set */

//--------------------------------------------------

require_once "../helpers/postcompiler.function.php";
require_once "../helpers/database.class.php";
require_once "../helpers/opengraph.class.php";
require_once "../helpers/output.class.php";
require_once "../helpers/image.class.php";
require_once "../helpers/misc.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();

if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	// Checks for the required POST variables //
	if(array_key_exists("type", $_POST) && array_key_exists("written", $_POST) && array_key_exists("topic", $_POST)){
		
		// Setting Variables //
		$type = $_POST['type'];
		$hashtags = Null;
		$link = Null;
		$output = new Output;
		
		// Checks for weird input (usually hackers) //
		$output->publish_output_error($verify->post(0, array($_POST['type'], $_POST['topic'])));
		
		// Check if user has posted to much stuff (spam protection) //
		if (PRODUCTION) {
			if($verify->post_spam_protection($_SESSION['uid'])) $output->publish_output_error(9);
		}
		
		// Verify Input //
		switch ($type) {
			
			// Blurb //
			case 4 : 
			// Article //
			case 1 :
				$input = $_POST['written'];
				break;
				
			// Image //
			case 2 :
				if(!array_key_exists("imageSrc", $_FILES) && !array_key_exists("imageUrl", $_POST)) $output->publish_output_error(7);
				
				if(strlen($_POST['imageUrl']) > 4) $input = $_POST['imageUrl'];
				else $input = $_FILES['imageSrc'];
				break;
				
			// Youtube Video //
			case 3 :
				if(!array_key_exists("videoUrl", $_POST)) $output->publish_output_error(7);
				else $input = array($_POST['written'], $_POST['videoUrl']);
				break;
			
			default : $output->publish_output_error(7);
		}
		
		if ($type != 2) $output->publish_output_error($verify->post($type, $input));
		else $output->publish_output_error($verify->post($type, $input), true);
		
		// Collect URL //
		if ($type == 1) preg_match_all(URL_REGEX, $_POST['written'], $link);
		else if ($type == 3) preg_match_all(URL_REGEX, $_POST['videoUrl'], $link);
		
		// Find hashtags //
		preg_match_all("/(#\w+)/", $_POST['written'], $hashtags);
		
		// Keep users from indefinitley writing new line characters //
		$_POST['written'] = preg_replace('"(\r?\n){2,}"', "\n\n", $_POST['written']);
		
		// Collects the opengraph information //
		$openGraph = new OpenGraph;
		$database = new Database;
		$misc = new Misc;
		
		switch($type){ // Need to check for the OG before setting the url prev as true
			
			// Article //
			case 1:
			
			// Video Link //
			case 3:
				if($link[0]) $URL = True;
				else $URL = False;
				
				// Will set date to 15 days ago to hide post from view //
				$postId = $database->insert("post_data", array('userid' => $_SESSION['uid'], 'type' => $type,
				'written' => $_POST['written'], 'url' => $URL, 'topic' => $_POST['topic'], 'date' => date('Y-m-d', strtotime('-15 days')), 'time' => date("H:i:s")), "iisssss");
				
				// OpenGraph class will auto delete post if no video is found from YouTube //
				if($URL) $output->publish_output_error($openGraph->collect_and_enter($link[0][0], $postId, $type));
				$misc->reset_post_date($postId);
				
				break;
			
			// Photo //
			case 2:
				// Download image into local storage //
				if(!is_array($input)) {
					$uniqueFilename = USER_CONTENT . "images/downloaded/" . uniqid() . ".jpg";
					$img_file = file_get_contents($input);
					file_put_contents($uniqueFilename, $img_file);
					$input = $uniqueFilename;
				}
				
				// Entering the Post Data //
				// Will set date to 15 days ago to hide post from view //
				$postId = $database->insert("post_data", array('userid' => $_SESSION['uid'], 'type' => $type, 'written' => $_POST['written'], 'topic' => $_POST['topic'], 
				'date' => date('Y-m-d', strtotime('-15 days')), 'time' => date("H:i:s")), "iissss");
				
				$image = new Image;
				$image->enter_image($input, $postId);
				
				$misc->reset_post_date($postId);
				
				break;
				
			// Blurb //
			case 4:
				$postId = $database->insert("post_data", array('userid' => $_SESSION['uid'], 'type' => $type, 
				'written' => $_POST['written'], 'topic' => 's', 'date' => date("Y-m-d"), 'time' => date("H:i:s")), "iissss");
				break;
		}
		
		// Enter hastags into database //
		$enteredHashtags = array();
		$i = 0;
		
		foreach($hashtags[0] as $hash){
			
			// Max of 10 hashtags //
			if($i == 10) break;
			else $i++;
			
			// So users don't enter 2 hashtags of the same name //
			if(!in_array($hash, $enteredHashtags)) array_push($enteredHashtags, strtolower($hash));
			else continue;
			
			$database->insert("hashtag_data", array('postid' => $postId, 'hashtag' => $hash), 'is');
		}
		
		// Update users post statistics //
		$database->user_statistics($_SESSION['uid'], "posts", true);
		
		// Add new post stat row in the database //
		$database->insert("post_statistics", array('postid' => $postId), "i");
		
		// Update user post majority //
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		$result = $conn->query("SELECT topic FROM post_data WHERE userid = " . $_SESSION['uid'] . " AND topic != 's' ORDER BY postid DESC LIMIT 10");
		if($result->num_rows == 0) $mainTopic = 0;
		else{
			$topics = array();
			
			while($row = $result->fetch_assoc()){
				array_push($topics, $row['topic']);
			}
			
			$c = array_count_values($topics);
			$mainTopic = array_search(max($c), $c);
		}
		
		$conn->query("UPDATE user_data SET mtopic = '$mainTopic' WHERE userid = " . $_SESSION['uid']);
		$conn->close();
		
		// Add activity row (This is used to stop users posting more than 8 posts a day even if they delete them) //
		$database->insert("activity_data", array('cuserid' => $_SESSION['uid'], 'huserid' => $_SESSION['uid'], 'action' => 8, 'date' => date("Y-m-d"), 'time' => date("H:i:s")), "iiiss");
		
		if ($type != 2) $output->publish_output_post($postId);
		else $output->publish_output_post($postId, true);
	}
}
?>