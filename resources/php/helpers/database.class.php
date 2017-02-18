<?php
//--------------------------------------------------

/* This php code provides a class which deals with common
 * database functions */

//--------------------------------------------------

require_once "misc.class.php";
require_once "image.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

class Database {
	
	public function insert ($table, $data, $expect){
		
		/* -----------------------------------------------------------
		 * This code makes inserting data into php easy using prepared
		 * statements. It will first take the first column defined in
		 * $data and the first character in $expect to create an empty
		 * row in the table.
		 * 
		 * Then I will find the id of this row and update it as nessesary
		 * to find this row and it's id and subsequently add another entry
		 * column by column through UPDATE
		 * 
		 * Only compatable with integers and text
		 * ---------------------------------------------------------- */
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		// Collect increment column name //
		$result = $conn->query("SHOW INDEX FROM " . $table . " WHERE Key_name = 'PRIMARY'");
		$row = $result->fetch_assoc();
		
		// Collect first key-value in $data //
		$mal = $data;
		reset($mal);
		$refColumn = key($mal);
		
		// Collect first character in $expect //
		$refExpect = $expect[0];
		
		// Create random value
		if($refExpect == 'i') $refValue = 0;
		else if($refExpect == 's') $refValue = 'x'; // x is random
		
		// Collect id of row //
		$stmt = $conn->prepare("INSERT INTO " . $table . " (" . $refColumn . ") " . "VALUES (?)");
		$stmt->bind_param($refExpect, $refValue);
		$stmt->execute();
		$rowId = $stmt->insert_id;
		$stmt->close();
		
		$expectChar = 0;
		
		// Fill row with values from $data //
		foreach(array_keys($data) as $column){
			$expectedInput = $expect[$expectChar];
			$expectedInput = $expectedInput . 'i';
			
			$stmt = $conn->prepare("UPDATE " . $table . " SET " . $column . " = ? WHERE " . $row['Column_name'] . " = ?");
			
			$stmt->bind_param($expectedInput, $data[$column], $rowId);
			$stmt->execute();
			$stmt->close();
			
			$expectChar++;
		}
		
		$conn->close();
		
		return $rowId;
	}
	
	public function check ($table, $column, $value, $expect, $expiry = False) {
		
		/* -----------------------------------------------------------
		 * This code is an easy way to check for values in the database
		 * in order to identify wether a value is present or not
		 * 
		 * I have also added $expiry where you can set to exclude values
		 * that surpass a certain date (in days)
		 * ---------------------------------------------------------- */
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		// Modify Sql to collect Date and Time for expiry check //
		if($expiry) $sql = "SELECT " . $column . ", date, time FROM " . $table . " WHERE " . $column . " = ?";
		else $sql ="SELECT " . $column . " FROM " . $table . " WHERE " . $column . " = ?";
		
		$stmt = $conn->prepare($sql); // Check to see if vars can be replaced with '?'
		$stmt->bind_param($expect, $value);
		$stmt->execute();
		
		if($expiry){
			$res = $stmt->get_result();
			$row = $res->fetch_assoc();
			$misc = new Misc;
			
			if($row != NULL){
				if($misc->expiry_checker($row['date'], $row['time'], $expiry)) $exists = 0;
				else $exists = 1;
			}else $exists = 0;
		}else{
			$stmt->store_result();
			$exists = $stmt->num_rows;
		}
		
		$stmt->close();
		$conn->close();
		
		if($exists > 0) return True;
		else return False;
	}
	
	public function remove_row_if_expired ($table, $column, $value, $expect, $days) {
		
		/* -----------------------------------------------------------
		 * This code is mostly used to remove old temporary profiles
		 * when a new profile is created with the same username. It can
		 * however be used as mateniance or whatever
		 * ---------------------------------------------------------- */
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		if($days) $stmt = $conn->prepare("SELECT $column, date, time FROM $table WHERE $column = ?");
		else $stmt = $conn->prepare("SELECT $column FROM $table WHERE $column = ?");
		
		$stmt->bind_param($expect, $value);
		$stmt->execute();
		$stmt->store_result();
		
		if($days) $stmt->bind_result($result, $date, $time);
		else $stmt->bind_result($result);
		
		$stmt->fetch();
		$numRows = $stmt->num_rows;
		$stmt->close();
		
		if($numRows > 0){
			
			$misc = new Misc;
			
			// If the registration was made more than a day ago than delete row and use the username/email //
			if($days) $expired = $misc->expiry_checker($date, $time, $days);
			else $expired = true;
			
			if($expired){
				$stmt = $conn->prepare("DELETE FROM $table WHERE $column = ?");
				$stmt->bind_param($expect, $value);
				$stmt->execute();
				$stmt->close();
				$conn->close();
				return true;
			} else return false;
		}
		
		$conn->close();
		
		return false;
	}
	
	public function id_collect ($table, $column, $value, $expect) {
		
		/* -----------------------------------------------------------
		 * Checks if data exists and associated id is returned, otherwise
		 * false is returned, value must be string. Useful for returning
		 * the rest of the data in a row with the specified ID.
		 * ---------------------------------------------------------- */
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		if($this->check($table, $column, $value, $expect)){
			$result = $conn->query("SHOW INDEX FROM " . $table . " WHERE Key_name = 'PRIMARY'");
			$row = $result->fetch_assoc();
			
			$stmt = $conn->prepare("SELECT " . $row['Column_name'] . " FROM " . $table . " WHERE " . $column . " = ?");
			$stmt->bind_param($expect, $value);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($id);
			$stmt->fetch();
			
			$stmt->close();
			$conn->close();
			
			return $id;
		}else return False;
	}
	
	public function retrieve_by_id ($table, $column, $id) {
		
		/* -----------------------------------------------------------
		 * Generally used in conjunction with idCollect(), this function
		 * returns the contents of a row with a specified id and an array
		 * is returned (I think)
		 * ---------------------------------------------------------- */
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		if($this->check($table, $column, $id, "i")){
			$result = $conn->query("SHOW INDEX FROM " . $table . " WHERE Key_name = 'PRIMARY'");
			$row = $result->fetch_assoc();
			
			$stmt = $conn->prepare("SELECT " . $row['Column_name'] . " FROM " . $table . " WHERE " . $column . " = ?");
			$stmt->bind_param("i", $id);
			$stmt->execute();
			
			$stmt->store_result();
			$stmt->bind_result($value);
			$stmt->fetch();
			
			$stmt->close();
			$conn->close();
			
			return $value;
		}else return False;
	}
	
	public function collect_user_settings ($id) {
		
		/* -----------------------------------------------------------
		 * Collect User Setting Information
		 * ---------------------------------------------------------- */
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		$response = array("username" => array(), "name" => array(), "bemail" => array());
		
		$result = $conn->query("SELECT user_data.username, user_data.name, user_data_extra.bemail FROM user_data INNER JOIN user_data_extra ON user_data.userid = user_data_extra.userid WHERE user_data.userid = $id");
		$row = $result->fetch_assoc();
		
		foreach($row as $key => $value) {
			array_push($response[$key], $row[$key]);
		}
		
		$misc = new Misc;
		$nothingBefore = $misc->date_calculator("P3M");
		
		$result = $conn->query("SELECT setting FROM user_settings WHERE userid = " . $_SESSION['uid'] . " AND setting = 'u' AND TIMESTAMP(date, time) > '$nothingBefore' ORDER BY id DESC LIMIT 1");
		$row = $result->fetch_assoc();
		
		if($row != null) array_push($response['username'], true);
		else array_push($response['username'], false);
		
		$result = $conn->query("SELECT setting FROM user_settings WHERE userid = " . $_SESSION['uid'] . " AND setting = 'f' AND TIMESTAMP(date, time) > '$nothingBefore' ORDER BY id DESC LIMIT 1");
		$row = $result->fetch_assoc();
		
		if($row != null) array_push($response['name'], true);
		else array_push($response['name'], false);
		
		$conn->close();
		
		return $response;
	}
	
	public function delete_post ($postId) {
		
		/* -----------------------------------------------------------
		 * Completely remove a post from the database
		 * This function assumes the postid exists and is valid
		 * ---------------------------------------------------------- */
		 
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			
		$postid = $this->retrieve_original_postid($postId);
		
		$result = $conn->query("SELECT userid FROM post_data WHERE postid = $postid");
		if($result->num_rows != 0) {
			
			$results = $conn->query("SELECT postid FROM post_data WHERE postid = $postid OR originalpostid = $postid");
			
			while($row = $results->fetch_assoc()) {
				// Photo data wil not be erased //
				$conn->query("DELETE FROM hashtag_data WHERE postid = " . $row['postid']);
				$conn->query("DELETE FROM post_statistics WHERE postid = " . $row['postid']);
				$conn->query("DELETE FROM post_comments WHERE postid = " . $row['postid']);
				$conn->query("DELETE FROM open_graph WHERE postid = " . $row['postid']);
			}
			
			// Might need to delete all other associated tables //
			$conn->query("DELETE FROM post_data WHERE postid = $postid OR originalpostid = $postid");
			$conn->query("DELETE FROM post_suspended WHERE postid = $postid");
			
			// Resolve all involved tickets //
			$conn->query("UPDATE post_reports SET resolved = 1 WHERE postid = $postid");
		}
		
		$conn->close();
			
	}
	
	// Common Database Calls //
	public function post_statistics ($postid, $column, $update) {
		
		/* -----------------------------------------------------------
		 * This code is an easy way to add or subtract post statistics
		 * for post actions. Inputs must be verified. $Update means whether
		 * you want to add (true) or subtract (false) 1
		 * ---------------------------------------------------------- */
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		$add_sub = ($update ? "+ 1" : "- 1");
		$conn->query("UPDATE post_statistics SET $column = $column $add_sub WHERE postid = $postid");
		
		$conn->close();
	}
	
	public function influence_control ($userid, $influence, $update) {
		
		/* -----------------------------------------------------------
		 * This code is an easy way to add or subtract influence from a
		 * users profile
		 * ---------------------------------------------------------- */
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		$add_sub = ($update ? "+ $influence" : "- $influence");
		$conn->query("UPDATE user_data SET influence = influence $add_sub WHERE userid = $userid");
		
		$conn->close();
	}
	
	public function user_statistics ($userid, $column, $update) {
		
		/* -----------------------------------------------------------
		 * This code is an easy way to add or subtract statistics points
		 * from the user_statistics table
		 * ---------------------------------------------------------- */
		 
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		$add_sub = ($update ? "+ 1" : "- 1");
		$conn->query("UPDATE user_statistics SET $column = $column $add_sub WHERE userid = $userid");
		
		$conn->close();
	}
	
	public function collect_user_pic ($userid, $size) {
		
		/* -----------------------------------------------------------
		 * Collect user profile picture
		 * ---------------------------------------------------------- */
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		$result = $conn->query("SELECT id FROM photo_data WHERE userid = " . $userid . " AND propic = 1");
		if($result->num_rows){
			$row = $result->fetch_assoc();
			$image = new Image;
			return SERVER_URL . "user/images/" . $size . "/" . $image->find_path($row['id']) . ".jpg";
		}else{
			return SERVER_URL . "resources/images/placeholder/default-" . $size . ".png";
		}
		
		$conn->close();
	}
	
	public function collect_userid_from_postid ($postid) {
		
		/* -----------------------------------------------------------
		 * Collect the userid of the post
		 * ---------------------------------------------------------- */
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		$stmt = $conn->prepare("SELECT userid FROM post_data WHERE postid = ?");
		$stmt->bind_param('i', $postid);
		$stmt->execute();
		
		$res = $stmt->get_result();
		$row = $res->fetch_assoc();
		
		$stmt->close();
		$conn->close();
		
		return $row['userid'];
	}
	
	public function retrieve_original_postid ($pid) {
		
		/* -----------------------------------------------------------
		 * Collect the postid of the post and check to see if there is an 
		 * original postid. This function assumes you checked the postid.
		 * ---------------------------------------------------------- */
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		$result = $conn->query("SELECT originalpostid FROM post_data WHERE postid = " . $pid);
		$row = $result->fetch_assoc();
		
		if($row['originalpostid'] == NULL) $postid = $pid;
		else $postid = $row['originalpostid'];
		
		return $postid;
	}
}
?>