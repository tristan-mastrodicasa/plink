<?php
//--------------------------------------------------

/* This php code holds a class which deals with POST var 
 * validation and user input validation. */

//--------------------------------------------------

require_once "misc.class.php";
require_once "database.class.php";
require_once "pbkdf2.function.php";
require_once __DIR__ . "/../config.php";

// Invalid SOS parameters for sequential JPEG ? //
ini_set ('gd.jpeg_ignore_warning', 1);

//--------------------------------------------------

class Verify {
	
	public $CREDENTIAL_ERROR_MESSAGES = [0,
		"Input must be less than 20 characters",
		"Input must be less than 254 characters",
		"Only alphabetical characters are allowed. No spaces",
		"Feild must not be left empty",
		"Username is already in use",
		"Must only contain lowercase letters",
		"Only letters, numbers and underscores are allowed",
		"That email is not valid",
		"Spaces are not allowed",
		"Plink has not opened to that school yet",
		"That email is already in use",
		"Input must not be less than 6 characters"
	];
	
	public $POST_ERROR_MESSAGES = [0,
		"Written input is too large, keep under 30,000 characters",
		"Written input is too large, keep under 150 characters",
		"Input must not be empty",
		"No file detected",
		"No topic selected, choose Other if unsure",
		"Error conecting to server, check internet connection",
		"Unexpected error occured. Try again later?",
		"Please enter a valid URL",
		"Sorry but you have reached our spam protection of 8 posts a day",
		"Only PNG, JPG and GIF is allowed.",
		"Image is too small, please keep above 350px for height and width",
		"Please keep image size under 10MB",
		"Some trouble has been had connecting to YouTube, try again later",
		"Image link is either invalid or the image is too small",
		"Image is unusually large, please keep under 6000px for height and width"
	];
	
	public function logged_in () {
		
		/* -----------------------------------------------------------
		 * Checks if the user is logged in
		 * ---------------------------------------------------------- */
		 
		// Kicks out those who aren't logged in //
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		
		if(!isset($_SESSION['uid'])) header("Location: " . SERVER_URL);
	}
	
	public function is_banned($userid) {
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		$database = new Database;
		$misc = new Misc;
		
		if($database->check("user_data", "userid", $userid, 'i')) {
			// Check if user id banned //
			$result = $conn->query("SELECT * FROM ban_list WHERE userid = $userid");
			
			if($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				
				if($row['type'] == 'b') return true;
				else if ($row['type'] == 'tb') {
					
					// If the temp ban is over //
					if($misc->diff_in_seconds_now($row['date'], $row['time']) > 0) {
						$conn->query("DELETE FROM ban_list WHERE userid = " . $_SESSION['uid']);
						return false;
					} else return true;
				}
			} else return false;
		} else return false;
	}
	
	public function post ($inputType, $input) {
		
		/* -----------------------------------------------------------
		 * Verify's the inputs of posts
		 * ---------------------------------------------------------- */
		
		global $TOPIC_INITIALS;
		
		switch ($inputType){
			
			// Topic and Type //
			case 0:
				// 0 : Type, 1 : Topic //
				if (!is_numeric($input[0]) || strlen($input[1]) > 2 || strlen($input[1]) == 0) return $this->POST_ERROR_MESSAGES[7];
				else if ($input[0] > 4 || $input[0] < 1) return $this->POST_ERROR_MESSAGES[7];
				else if ($input[0] != 4 && !in_array($input[1], $TOPIC_INITIALS)) return $this->POST_ERROR_MESSAGES[7];
				else if ($input[0] != 4 && $input[1] == 'n') return $this->POST_ERROR_MESSAGES[5];
				else return $this->POST_ERROR_MESSAGES[0];
				break;
			
			// Article //
			case 1:
				if (!$input || strlen($input) <= 0) return $this->POST_ERROR_MESSAGES[3];
				else if(strlen($input) > 30000) return $this->POST_ERROR_MESSAGES[1];
				else return 0;
				break;
			
			// Photo //
			case 2:
				if (isset($input) && $input) {
					
					// If the photo is being uploaded //
					if(is_array($input)) {
						$output = $this->image($input);
						if ($output != 0) return $this->POST_ERROR_MESSAGES[$output];
						else return $this->POST_ERROR_MESSAGES[0];
					} else {
						
						// If the photo is being linked //
						if(!$this->is_url_image($input)) return $this->POST_ERROR_MESSAGES[14];
						else return $this->POST_ERROR_MESSAGES[0];
					}
				} else return $this->POST_ERROR_MESSAGES[7];
				break;
				
			// Video Link //
			case 3:
				// 0 : Written, 1 : VideoUrl //
				if ((strlen($input[0]) + strlen($input[1])) > 30000) return $this->POST_ERROR_MESSAGES[1];
				
				preg_match_all(URL_REGEX, $input[1], $linksRaw);
				
				if (isset($linksRaw[0][0])) {
					if(strpos($linksRaw[0][0], "youtube.com") !== false || strpos($linksRaw[0][0], "youtu.be") !== false || strpos($linksRaw[0][0], "m.youtube.com") !== false) return $this->POST_ERROR_MESSAGES[0];
					else return 8;
				}else return 8;
				
				break;
			
			// Blurb //
			case 4:
				if(!$input || strlen($input) <= 0) return $this->POST_ERROR_MESSAGES[3];
				else if(strlen($input) > 150) return $this->POST_ERROR_MESSAGES[2];
				else return $this->POST_ERROR_MESSAGES[0];
				break;
			
			// In case user enters something weird //
			default: return $this->POST_ERROR_MESSAGES[7];
		}
	}
	
	public function post_spam_protection ($userid) {
			
			/* -----------------------------------------------------------
			 * Allows only 8 posts to be made a day
			 * ---------------------------------------------------------- */
			
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			
			$stmt = $conn->prepare("SELECT date, time FROM activity_data WHERE cuserid = ? AND action = 8 ORDER BY id DESC LIMIT 9"); // Check to see if vars can be replaced with '?'
			$stmt->bind_param("i", $userid);
			$stmt->execute();
			$stmt->bind_result($date, $time);
			$stmt->store_result();
			
			if($stmt->num_rows >= 8){
				while($stmt->fetch()) {
					
					$misc = new Misc;
					if(!$misc->expiry_checker($date, $time, 1)) {
						$stmt->close();
						$conn->close();
						return True;
					}
					break;
				}
			}
			
			$stmt->close();
			$conn->close();
		
	}
	
	public function user_credentials ($inputType, $input) {
		
		/* -----------------------------------------------------------
		 * This script takes the input type and value and determines wether
		 * the input is vaild or not by sending and error code (0 means no
		 * error)
		 * ---------------------------------------------------------- */
		
		if (!$input) return $this->CREDENTIAL_ERROR_MESSAGES[4];
		
		$database = new Database;
		
		switch ($inputType) {
			
			// First Name //
			case 'f' :
				if (strlen($input) > 20) return $this->CREDENTIAL_ERROR_MESSAGES[1];
				else if (!preg_match("/^[a-zA-Z]+$/", $input)) return $this->CREDENTIAL_ERROR_MESSAGES[3];
				else return 0;
				break;
			
			// Username or Reference //
			case 'un' :
			case 'u' :
			case 'ur' :
				if (strlen($input) > 20) return $this->CREDENTIAL_ERROR_MESSAGES[1];
				else if (preg_match("/\s/", $input)) return $this->CREDENTIAL_ERROR_MESSAGES[9];
				else if (preg_match("/[A-Z]/", $input)) return $this->CREDENTIAL_ERROR_MESSAGES[6];
				else if (preg_match("/[^a-z0-9_]/", $input)) return $this->CREDENTIAL_ERROR_MESSAGES[7];
				else if ($inputType == 'ur' && $database->check("user_data", "username", $input, 's')) return 0;
				else if ($inputType == 'u') {
					if ($database->check("user_data", "username", $input, 's') || $database->check("temp_user_data", "username", $input, 's')) {
						if($database->remove_row_if_expired("temp_user_data", "username", $input, 's', 1)) return 0;
						else return $this->CREDENTIAL_ERROR_MESSAGES[5];
					} else return 0;
				} else return 0;
				break;
			
			// School or Back up Email //
			case 'se' :
			case 'be' :
				if(strlen($input) > 254) return $this->CREDENTIAL_ERROR_MESSAGES[2];
				else if(!filter_var($input, FILTER_VALIDATE_EMAIL)) return $this->CREDENTIAL_ERROR_MESSAGES[8];
				else {
					
					// Backup Email in settings doesn't need to be school based //
					if ($inputType == 'se') {
						
						// Checks if email matches accepted schools //
						$explodedEmail = explode('@', $input);
						$domain = array_pop($explodedEmail);
						global $ACCEPTED_DOMAINS;
						
						if (!in_array($domain, $ACCEPTED_DOMAINS)) return $this->CREDENTIAL_ERROR_MESSAGES[10];
						else if ($database->check("user_data_extra", "email", $input, 's') || $database->check("temp_user_data", "email", $input, 's')) {
							if ($database->remove_row_if_expired("temp_user_data", "email", $input, 's', 1)) return 0;
							else return $this->CREDENTIAL_ERROR_MESSAGES[11];
						} else return 0;
					} else return 0;
				}
				break;
			
			// Password //
			case 'p' :
				if (strlen($input) < 6) return $this->CREDENTIAL_ERROR_MESSAGES[12];
				else if (strlen($input) > 254) return $this->CREDENTIAL_ERROR_MESSAGES[2];
				else return 0;
				break;
			
			// Password Username //
			case 'lg' :
				if(!$database->check("user_data", "userid", $input[0], 'i')) return false;
				else {
					// Checks Password if username exists //
					$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
					
					$stmt = $conn->prepare("SELECT password, salt FROM pass_data WHERE userid = ?");
					$stmt->bind_param("i", $input[0]);
					$stmt->execute();
					$stmt->store_result();
					$stmt->bind_result($password, $salt);
					$stmt->fetch();
					
					$stmt->close();
					$conn->close();
					
					// Hash inputted Password //
					$hashedPass = pbkdf2("SHA256", $input[1], $salt, 1500, 64);
					
					if($hashedPass == $password) return true;
					else return false;
				}
			
			default : return "Unexpected input";
		}
	}
	
	public function user ($value) {
		
		/* -----------------------------------------------------------
		 * When a user is referenced (passed to show he/she shared a link)
		 * this function will make sure it is valid before passing into the
		 * database (takes in the written username)
		 * ---------------------------------------------------------- */
		
		$unError = $this->user_credentials('u', $value);
		
		if($unError == 0){
			
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			
			$stmt = $conn->prepare("SELECT username FROM user_data WHERE username = ?");
			$stmt->bind_param("s", $value);
			$stmt->execute();
			$stmt->store_result();
			$exists = $stmt->num_rows;
			
			$stmt->close();
			$conn->close();
			
			if($exists > 0){
				return True;
			}
		}
		return False;
	}
	
	public function is_url_image ($url, $opengraph = false) {
		
		/* -----------------------------------------------------------
		 * This code was created by danio on stackoverflow at the following
		 * location -> http://stackoverflow.com/questions/676949/best-way-to-determine-if-a-url-is-an-image-in-php
		 * ---------------------------------------------------------- */
		 
		$params = array('http' => array(
			'method' => 'HEAD'
		));
		
		$ctx = stream_context_create($params);
		$fp = @fopen($url, 'rb', false, $ctx);
		if(!$fp) 
			return false;  // Problem with url

		$meta = stream_get_meta_data($fp);
		if($meta === false){
			fclose($fp);
			return false;  // Problem reading data from url
		}

		$wrapper_data = $meta["wrapper_data"];
		if(is_array($wrapper_data)){
			foreach(array_keys($wrapper_data) as $hh){
				if (substr($wrapper_data[$hh], 0, 19) == "Content-Type: image"){  // strlen("Content-Type: image") == 19 
					fclose($fp);
					
					list($width, $height) = getimagesize($url);
					
					if(!$opengraph) {
						if ($width < 350 || $height < 350) return false;
						else if ($width > 6000 || $height > 6000) return false;
						else return true;
					} else {
						if ($width > 6000 || $height > 6000) return false;
						else return true;
					}
				}
			}
		}

		fclose($fp);
		return false;
	}
	
	public function image ($imageSrc) {
		
		/* -----------------------------------------------------------
		 * This function helps determine the authenticity of the image
		 * The Image must currently
		 * 
		 * 1. Be an image (check by converting in gd) and using w3 school method
		 * 2. Be under 10mb
		 * 3. Must be larger than 500 * 500
		 * 4. Only accept .png .jpg and .gif
		 * ---------------------------------------------------------- */
		
		$src = imagecreatefromstring(file_get_contents($imageSrc['tmp_name']));
		if($src === False) return 7;
		else imagedestroy($src);
		
		$check = getimagesize($imageSrc['tmp_name']);
		
		// Check image dimensions //
		if($check !== False){
			// These demensions are also checked in $this->is_url_image() //
			if ($check[0] < 350 || $check[1] < 350) return 11;
			else if ($check[0] > 6000 || $check[1] > 6000) return 15;
		}else return 10;
		
		// Check image size //
		if($imageSrc['size'] > 10000000) return 12;
		
		$imageType = pathinfo(basename($imageSrc['name']), PATHINFO_EXTENSION);
		
		// Check image type //
		if(strtolower($imageType) != "jpg" && strtolower($imageType) != "png" && strtolower($imageType) != "jpeg" && strtolower($imageType) != "gif") return 10;
		
		// In Depth Image Checking //
		$allowedTypes = array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF);
		$detectedType = exif_imagetype($imageSrc['tmp_name']);
		
		if(!in_array($detectedType, $allowedTypes)) return 10;
		
		return 0;
	}
	
	public function comment ($written) {
		
		/* -----------------------------------------------------------
		 * This function helps determine the validity of a comment
		 * ---------------------------------------------------------- */
		 
		if(is_string($written) && strlen($written) <= 180 && strlen($written) > 0) return 0;
		else return 1;
	}
	
	public function password_recovery_code ($userid, $code) {
		
		/* -----------------------------------------------------------
		 * This function validates the userid / verification code for 
		 * recovering a password
		 * ---------------------------------------------------------- */
		
		$database = new Database;
		
		if($database->check("password_recovery", "userid", $userid, 'i')) {
			
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			$misc = new Misc;
			$nothingBefore = $misc->date_calculator("P2D");
			
			$stmt = $conn->prepare("SELECT id FROM password_recovery WHERE userid = ? AND code = ? AND TIMESTAMP(date, time) > '$nothingBefore'");
			$stmt->bind_param('is', $userid, $code);
			$stmt->execute();
			$stmt->store_result();
			$numRows = $stmt->num_rows;
			$stmt->close();
			$conn->close();
			
			if ($numRows > 0) return true;
			else return false;
		} else return false;
	}
	
	public function url_timeout ($url, $timeout) {
		
		/* -----------------------------------------------------------
		 * This function checks if a url takes more than the specified
		 * time to load
		 * ---------------------------------------------------------- */
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
		
		curl_exec($ch);
		$result = curl_errno($ch);
		curl_close($ch);
		return ($result != 0) ? true : false;
	}
	
	public function captcha ($value) {
		
		/* -----------------------------------------------------------
		 * This function verifies if the user is not a robot
		 * ---------------------------------------------------------- */
		
		$url = "https://www.google.com/recaptcha/api/siteverify";
		$data = array('secret' => '6LdePRUUAAAAADO8XWhLIalwjC0ci5bFKYhvoYLX', 'response' => $value);

		// use key 'http' even if you send the request to https://...
		$options = array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query($data)
			)
		);

		$context  = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		if ($result === FALSE) { return false; }

		$json = json_decode($result, true);
		return $json["success"];
	}
}

?>