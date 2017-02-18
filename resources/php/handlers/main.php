<?php
//--------------------------------------------------

/* This php code is the main feed's handler, this code returns */
/* the best posts for the specific user and their interests */

//--------------------------------------------------

require_once "../helpers/postcompiler.function.php";
require_once "../helpers/verification.class.php";
require_once "../helpers/misc.class.php";
require_once "../helpers/output.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

$verify = new Verify;
$verify->logged_in();

if($_SERVER['REQUEST_METHOD'] == "POST"){
	
	$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
	$response = array("posts" => array());
	$response['meta']['layer'] = 'm';
	
	// First we must collect the user's favourite topics (last 10) //
	$results = $conn->query("SELECT post_data.topic FROM 
	post_data INNER JOIN activity_data ON activity_data.postid = post_data.postid WHERE activity_data.action = 1 
	AND activity_data.cuserid = " . $_SESSION['uid'] . " AND activity_data.committed = 1 ORDER BY activity_data.id DESC LIMIT 10");
	
	$listOfLikedTopics = array();

	if($results->num_rows == 0) array_push($listOfLikedTopics, 'rn');
	else {
		while ($row = $results->fetch_assoc()) {
			array_push($listOfLikedTopics, $row['topic']);
		}
	}
	
	$conn->close();
	
	$topicList = array();
	$selectedTopics = array();
	$topicRange = array();
	$numOfTopics = 0;
	$rangeMin = 0;

	// Assess the topics liked an their number of occurences //
	foreach ($listOfLikedTopics as $topic) {
		if(array_key_exists($topic, $topicList)) {
			$topicList[$topic] += 1;
		} else {
			$topicList[$topic] = 1;
		}
		
		$numOfTopics++;
	}

	// Generates the ranges within 100 for the different topics //
	foreach ($topicList as $topic => $num) {
		
		$rangeMax = floor($rangeMin + (($num / $numOfTopics) * 80));
		$topicRange[$topic][0] = ($rangeMin + 1);
		$topicRange[$topic][1] = $rangeMax;
		
		$rangeMin = $rangeMax;
	}

	$topicRange['rn'][0] = ($rangeMin + 1);
	$topicRange['rn'][1] = 100;

	// Create an array of topics not in $listOfLikedTopics //
	$otherTopics = array();

	foreach ($TOPIC_INITIALS as $topic) {
		
		if(!in_array($topic, $listOfLikedTopics)) {
			array_push($otherTopics, $topic);
		}
	}

	// Select 3 unique topics //
	while (true) {
		
		$selection = rand(1, 100);
		
		foreach ($topicRange as $topic => $range) {
			
			if(($selection > $range[0] && $selection < $range[1]) || ($selection == $range[0] || $selection == $range[1])) {
				
				if ($topic == 'rn') {
					$i = count($otherTopics);
					$index = rand(0, ($i - 1));
					array_push($selectedTopics, $otherTopics[$index]);
					array_splice($otherTopics, $index, 1);
				} else if (!in_array($topic, $selectedTopics)) {
					array_push($selectedTopics, $topic);
				} else {
					break;
				}
				
			}
			
		}
		
		if(count($selectedTopics) == 3) break;
	}
	
	// Run though the 6 different MySql queries in order to find a post under the //
	// 3 specified topics //
	$misc = new Misc;
	
	$mysqlQueries = array(
		"SELECT post_data.postid FROM post_data INNER JOIN post_statistics ON post_data.postid = post_statistics.postid WHERE post_data.userid IN (SELECT * FROM (SELECT user_data.userid FROM post_data INNER JOIN activity_data ON activity_data.postid = post_data.postid INNER JOIN subscription_data ON subscription_data.huserid = activity_data.huserid INNER JOIN user_data ON user_data.userid = subscription_data.huserid WHERE subscription_data.cuserid = ? AND activity_data.action = 1 AND activity_data.committed = 1 AND post_data.topic = ? AND TIMESTAMP(activity_data.date, activity_data.time) > ? ORDER BY activity_data.id DESC LIMIT 5) AS t ) AND post_data.topic = ? AND post_data.postid NOT IN (SELECT postid FROM post_loaded WHERE cuserid = ?) AND TIMESTAMP(post_data.date, post_data.time) > ? ORDER BY post_statistics.endorsements DESC LIMIT 1",
		"SELECT post_data.postid FROM post_data INNER JOIN user_data ON user_data.userid = post_data.userid WHERE user_data.userid IN (SELECT huserid FROM subscription_data WHERE cuserid = ?) AND user_data.mtopic = ? AND TIMESTAMP(post_data.date, post_data.time) > ? AND post_data.topic = ? AND post_data.postid NOT IN (SELECT postid FROM post_loaded WHERE cuserid = ?) ORDER BY user_data.influence DESC, post_data.postid DESC LIMIT 1",
		"SELECT postid FROM post_data WHERE userid IN (SELECT * FROM (SELECT user_data.userid FROM user_data INNER JOIN subscription_data ON subscription_data.cuserid = user_data.userid WHERE subscription_data.huserid IN (SELECT * FROM (SELECT user_data.userid FROM user_data INNER JOIN subscription_data ON subscription_data.huserid = user_data.userid WHERE subscription_data.cuserid = ? AND user_data.mtopic = ? ORDER BY user_data.influence DESC LIMIT 1) AS t) AND subscription_data.cuserid != ? AND user_data.mtopic = ? ORDER BY user_data.influence DESC LIMIT 3) AS s) AND TIMESTAMP(date, time) > ? AND topic = ? AND postid NOT IN (SELECT postid FROM post_loaded WHERE cuserid = ?) ORDER BY postid DESC LIMIT 1",
		"SELECT post_data.postid FROM post_data INNER JOIN user_data ON post_data.userid = user_data.userid WHERE TIMESTAMP(post_data.date, post_data.time) > ? AND user_data.mtopic = ? AND post_data.topic = ? AND post_data.postid NOT IN (SELECT postid FROM post_loaded WHERE cuserid = ?) ORDER BY user_data.influence DESC, post_data.postid DESC LIMIT 1",
		"SELECT post_data.postid FROM post_data INNER JOIN post_statistics ON post_data.postid = post_statistics.postid WHERE TIMESTAMP(post_data.date, post_data.time) > ? AND post_data.topic = ? AND post_data.postid NOT IN (SELECT postid FROM post_loaded WHERE cuserid = ?) ORDER BY post_statistics.endorsements DESC, post_data.postid DESC LIMIT 1",
		"SELECT post_data.postid FROM post_data INNER JOIN post_statistics ON post_data.postid = post_statistics.postid WHERE TIMESTAMP(post_data.date, post_data.time) > ? AND post_data.postid NOT IN (SELECT postid FROM post_loaded WHERE cuserid = ?) ORDER BY post_statistics.endorsements DESC, post_data.postid DESC LIMIT 1"
	);
	
	$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
	$output = new Output;
	
	// Loop through each selected topic and run through query by query until a post is returned //
	foreach ($selectedTopics as $topic) {
		for($i = 0; $i < 6; $i++) {
			$results = $conn->query($misc->mysql_query_construct($i, $mysqlQueries[$i], $topic));
			
			if($results->num_rows > 0) {
				$row = $results->fetch_assoc();
				array_push($response['posts'], $output->post_meta_apply(compile_post($row['postid']), 2, 'm'));
				break;
			} // else move on to the next query
		}
	}
	
	$conn->close();
	
	echo $output->json_response($response);
}