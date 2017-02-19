<?php
//--------------------------------------------------

/* This php code provides a class which deals with 
 * unique and mainly general functions */

//--------------------------------------------------

require_once 'pbkdf2.function.php';
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

class Misc {
	
	public function expiry_checker($date, $time, $days) {
		
		/* -----------------------------------------------------------
		 * This handy function checks to see if a date and time has passed
		 * a certain amount of time in days. the fomula is $days * 24 so 
		 * passing something like 0.5 for $days will result in 12 hours expiry
		 * ---------------------------------------------------------- */
		
		// If days = 0 than return true for expiry //
		if(!$days) return true;
		
		$today = new DateTime(date("Y-m-d") . 'T' . date("H:i:s"));
		$entered = new DateTime($date . 'T' . $time);
		$diff = $today->diff($entered);
		$hours = $diff->h;
		$hours = $hours + ($diff->days * 24);
		
		if($hours >= (24 * $days)) return True;
		else return False;
	}
	
	public function date_calculator($subTime) {
		
		/* -----------------------------------------------------------
		 * This handy function helps return a timestamp from before a
		 * specified amount of time (today - 2 weeks) by inputting the
		 * time to subtract in terms of DateInterval () language
		 * ---------------------------------------------------------- */
		
		$date = new DateTime(date("Y-m-d") . 'T' . date("H:i:s"));
		$date->sub(new DateInterval($subTime));
		return $date->format('Y-m-d H:i:s');
	}
	
	public function diff_in_seconds_now ($date, $time) {
		$lastTime = strtotime($date . ' ' . $time);
		$timeNow = strtotime(date('Y-m-d H:i:s'));
		return $timeNow - $lastTime;
	}
	
	public function seconds_to_string ($seconds) {
		
		/* -----------------------------------------------------------
		 * Displays human readable time from seconds
		 * ---------------------------------------------------------- */
		
		$plural = '';
		
		if($seconds < 60) return "Just Now";
		
		else if($seconds > 60 && $seconds < 3600){
			$minutes = round($seconds / 60);
			return "$minutes min";
		}else if($seconds > 3600 && $seconds < 86400){
			$hours = round(($seconds / 60) / 60);
			if($hours > 1) $plural = 's';
			return "$hours hr" . $plural;
		}else if($seconds > 86400){
			$days = round((($seconds / 60) / 60) / 24);
			if($days > 1) $plural = 's';
			return "$days day" . $plural;
		}else return False;
	}
	
	public function level_calculator ($xp) {
		
		/* -----------------------------------------------------------
		 * Retrieves the xp and returns user level
		 * ---------------------------------------------------------- */
		
		if($xp < 10) return 1; 
		$level = 1;
		
		while (true) {
			$xpn = 10 * ($level*$level) + 10;
			$level++;
			if ($xpn > $xp) return $level;
			else if($xpn == $xp) return ($level + 1);
		}
	}
	
	public function next_level_calculator ($xp) {
		
		/* -----------------------------------------------------------
		 * Returns the xp to the next level
		 * ---------------------------------------------------------- */
		
		if($xp < 10){
			return 10 - $xp; 
		}
		$level = 1;
		
		while (true) {
			$xpn = 10 * ($level*$level) + 10;
			$level++;
			if ($xpn > $xp) return $xpn - $xp;
			else if($xpn == $xp){
				return (10 * ($level*$level) + 10) - $xp;
			}
		}
	}
	
	public function incremental_text_displayer ($increment, $string) {
		
		/* -----------------------------------------------------------
		 * This function will retireve more text from a posts written
		 * value for super long posts. It will also make sure to show
		 * only a certain amount of lines. $Increment is how deep the
		 * user is into the text. Returns false if there is no more text
		 * after sending the last bit.
		 * ---------------------------------------------------------- */
		
		$stringLength = strlen($string);
		$nlCount = substr_count($string, "\n");
		$nlCount = $nlCount * 44;
		
		if(($stringLength + $nlCount) > 250){
			if($increment != 0) $increment *= 800;
			else $increment = 250;
			
			$charCount = 0;
			$cleanString = '';
			$i = -1;
			$urlPositions = array();
			
			// Collects the urls in the string to make sure they aren't cut off //
			preg_match_all(URL_REGEX, $string, $urlList);
			
			foreach($urlList[0] as $link){
				if($i > -1) $urlPos = strpos($string, $link, $urlPositions[$i][1]);
				else $urlPos = strpos($string, $link);
				array_push($urlPositions, array($urlPos, ($urlPos + strlen($link))));
				$i++;
			}
			
			for($i = 0; $i < $stringLength; $i++){
				$cleanString .= $string[$i];
				if($string[$i] == "\n") $charCount += 45;
				else $charCount++;
				
				if($charCount > $increment){
					
					// Check if the string has stopped within a link //
					// If it has then back track or include link string //
					foreach($urlPositions as $redZone){
						if($i >= $redZone[0] && $i <= $redZone[1]){
							if(($redZone[1] - $i) < 200){
								while($i != ($redZone[1] - 1)){
									$i++;
									$cleanString .= $string[$i];
								}
							}else if(($redZone[1] - $i) > 200){
								$cleanString = substr($string, 0, ($redZone[0] - 1));
							}
						}
					}
					$string = $cleanString;
					break;
				}
			}
		}
		
		return $string;
		
	}
	
	public function search_type($query) {
		
		/* -----------------------------------------------------------
		 * This function checks whether a query is checking for a user or
		 * hash related post. This function assumes you have removes spaces
		 * ---------------------------------------------------------- */
		
		$char0 = substr($query, 0, 1);
		
		if($char0 == '#') return 'h';
		else if($char0 == '@') return 'un';
		else return 'f';
		
	}
	
	public function new_line_killer ($string) {
		
		/* -----------------------------------------------------------
		 * Removes new lines
		 * ---------------------------------------------------------- */
		
		$string = str_replace("\n", "", $string);
		$string = str_replace("\r", "", $string);
		
		return $string;
	}
	
	public function mysql_query_construct ($stageNum, $query, $topic) {
		
		/* -----------------------------------------------------------
		 * To save processing power in main.php this function will construct
		 * (add the parameters for) the mysql query in a specfic stage  
		 * ---------------------------------------------------------- */
		
		$topic = "'" . $topic . "'";
		
		switch ($stageNum) {
			
			case 0:
				$query = preg_replace('/\?/', $_SESSION['uid'], $query, 1);
				$query = preg_replace('/\?/', $topic, $query, 1);
				$query = preg_replace('/\?/', "'" . $this->date_calculator("P2W") . "'", $query, 1);
				$query = preg_replace('/\?/', $topic, $query, 1);
				$query = preg_replace('/\?/', $_SESSION['uid'], $query, 1);
				$query = preg_replace('/\?/', "'" . $this->date_calculator("P2D") . "'", $query, 1);
				break;
				
			case 1:
				$query = preg_replace('/\?/', $_SESSION['uid'], $query, 1);
				$query = preg_replace('/\?/', $topic, $query, 1);
				$query = preg_replace('/\?/', "'" . $this->date_calculator("P1D") . "'", $query, 1);
				$query = preg_replace('/\?/', $topic, $query, 1);
				$query = preg_replace('/\?/', $_SESSION['uid'], $query, 1);
				break;
				
			case 2:
				$query = preg_replace('/\?/', $_SESSION['uid'], $query, 1);
				$query = preg_replace('/\?/', $topic, $query, 1);
				$query = preg_replace('/\?/', $_SESSION['uid'], $query, 1);
				$query = preg_replace('/\?/', $topic, $query, 1);
				$query = preg_replace('/\?/', "'" . $this->date_calculator("P2D") . "'", $query, 1);
				$query = preg_replace('/\?/', $topic, $query, 1);
				$query = preg_replace('/\?/', $_SESSION['uid'], $query, 1);
				break;
				
			case 3:
				$query = preg_replace('/\?/', "'" . $this->date_calculator("P1D") . "'", $query, 1);
				$query = preg_replace('/\?/', $topic, $query, 1);
				$query = preg_replace('/\?/', $topic, $query, 1);
				$query = preg_replace('/\?/', $_SESSION['uid'], $query, 1);
				break;
				
			case 4:
				$query = preg_replace('/\?/', "'" . $this->date_calculator("P2W") . "'", $query, 1);
				$query = preg_replace('/\?/', $topic, $query, 1);
				$query = preg_replace('/\?/', $_SESSION['uid'], $query, 1);
				break;
				
			case 5:
				$query = preg_replace('/\?/', "'" . $this->date_calculator("P2W") . "'", $query, 1);
				$query = preg_replace('/\?/', $_SESSION['uid'], $query, 1);
				break;
		}
		
		return $query;
	}
	
	public function generate_hashed_pass_and_salt ($password) {
		
		/* -----------------------------------------------------------
		 * This function takes in the password and returns the hashed version
		 * along with the generated salt within an array
		 * ---------------------------------------------------------- */
		
		$result = array("hashedPass" => '', "salt" => '');
		
		$saltLen = 64;
		$saltStrong = True;
		$saltRaw = openssl_random_pseudo_bytes($saltLen, $saltStrong);
		$result["salt"] = bin2hex($saltRaw);
		$result["hashedPass"] = pbkdf2("SHA256", $password, $result['salt'], 1500, 64);
		
		return $result;
	}
	
	public function log_in_user ($userid, $cookies = false) {
		
		/* -----------------------------------------------------------
		 * This function takes in the userid of the user that will be
		 * logged in. This function assumes $userid is verified
		 * ---------------------------------------------------------- */
		 
		session_start();
		session_unset();
		session_destroy();
		
		session_start();
		$_SESSION['uid'] = $userid;
		// Set cookies etc
	}
	
	public function swap_values (&$a, &$b) {
		
		$t = $a;
		$a = $b;
		$b = $t;
		
	}
	
	public function has_launched () {
		
		/* -----------------------------------------------------------
		 * This function checks to see if the launch date has been surpassed,
		 * if it hasen't than it will redirect the user to the about page
		 * ---------------------------------------------------------- */
		
		if (PRODUCTION) {
			
			// Demo Code //
			if(array_key_exists('var_one', $_GET) && $_GET['var_one'] == "aaabbbccc123") {}
			else {
				if($this->diff_in_seconds_now("2017-02-20", "17:29:42") < 0) {
					header("Location: " . SERVER_URL . "about/");
				}
			}
			
		}
	}
	
	public function mailer_config (&$mail) {
		
		/* -----------------------------------------------------------
		 * This function takes the reference to a PHPMailer object and
		 * assigns the standard headers and configurations
		 * ---------------------------------------------------------- */
		
		$mail->isSMTP();
		$mail->Host = 'server.plink-net.com';
		$mail->SMTPAuth = true;
		$mail->Username = 'noreply@plink-net.com';
		$mail->Password = 'mIlLer#4900';
		$mail->Port = 26;
		
		$mail->setFrom('noreply@plink-net.com');
	}
	
	public function reset_post_date ($postid) {
		
		/* -----------------------------------------------------------
		 * Used to set a post date to the current one to make a certain
		 * post 'active' and visibile to post engines. Postid is assumed
		 * to be clean
		 * ---------------------------------------------------------- */
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		$conn->query("UPDATE post_data SET date = '" . date("Y-m-d") . "' WHERE postid = $postid");
		
		$conn->close();
	}
	
	public function establish_secure () {
			
		/* -----------------------------------------------------------
		 * This function will automatically set the protocol to https
		 * ---------------------------------------------------------- */
		
		if (PRODUCTION) {
			
			if ($_SERVER['HTTPS'] != "on") {
				$url = "https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
				header("Location: $url");
				exit;
			}
			
		}
	}
}


?>