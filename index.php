<?php
	
	//--------------------------------------------------
	
	/* This php code is the main page constructor using the
	 * scissor framework created by Tristan Mastrodicasa */
	
	//--------------------------------------------------
	
	require_once("resources/php/helpers/scissors/class.scissors.php");
	require_once("resources/php/helpers/misc.class.php");
	require_once("resources/php/helpers/verification.class.php");
	require_once("resources/php/config.php");
	
	// Both scripts below are imported when the main app (home) is constructed //
	// require_once("resources/php/helpers/misc.class.php");
	// require_once("resources/php/helpers/database.class.php");
	
	//--------------------------------------------------
	
	// Start the session //
	session_start();
	
	$misc = new Misc;
	
	// Change to https protocol //
	$misc->establish_secure();
	
	if ($_SERVER['REQUEST_METHOD'] == "GET") {
		
		$scissors = new Scissors("resources/html/");
		$scissors->set_canvas("main-template.html");
		
		// Assess which page is being requested //
		if (array_key_exists("page", $_GET) && in_array($_GET['page'], $CURRENT_PAGES)) $page = $_GET['page'];
		else $page = "index";
		
		// Checks if the site has launched //
		if ($page != "about" && $page != "home" && $page != "banned") $misc->has_launched();
		
		// BODY constructor //
		if ($page == "index" || $page == "password-recovery") {
			$scissors->paste("portal-middle.html", "body");
		} else if (in_array($page, $PORTAL_PAGES)) {
			$scissors->paste("portal-upper-middle.html", "body");
		} else {
			$scissors->paste("webapp-nav-content.html", "body");
		}
		
		// HEAD constructor //
		if ($page != "index" && $page != "about" && $page != "home") {
			$scissors->paste(array("meta/main-css.html", "meta/seo-lite.html"), "head");
		} else if ($page != "home") {
			
			if ($page == "index" && PRODUCTION) $scissors->paste(array("meta/main-css.html", "meta/seo-lite.html", "meta/seo.html", "<script src='https://www.google.com/recaptcha/api.js'></script>"), "head");
			else $scissors->paste(array("meta/main-css.html", "meta/seo-lite.html", "meta/seo.html"), "head");
			
		} else {
			
			// The main app's scripts are in it's <head> (therefore in the <head> constructor) //
			$scissors->paste(array(
				"meta/main-css.html", 
				"meta/seo-lite.html", 
				"scripts/home-script-one.html",
				"scripts/javascript-main.html",
				"scripts/javascript-utils.html",
				"scripts/home-script-two.html"), "head");
		}
		
		// FOOTER constructor //
		if ($page == "index") {
			$scissors->paste(array("portal-footers/general.html", "portal-footers/forgot-pass.html"), "footer");
			$scissors->paste("<span id='access'><a id='ti'>Login</a></span>", "signup");
		} else if (in_array($page, $PORTAL_PAGES)) {
			$scissors->paste("portal-footers/general.html", "footer");
			$scissors->paste("<span><a href='{{{ a:home }}}'>Sign Up</a></span>", "signup");
		}
		
		// CLOCK and SCRIPTS constructor for certain portal pages //
		if ($page == "about") {
			$scissors->paste("portal/about-clock.html", "clock");
			$scissors->paste(array("scripts/javascript-main.html", "scripts/about.html"), "scripts");
		} else if ($page == "terms" || $page == "release") {
			$scissors->paste("", "clock");
			$scissors->paste("", "scripts");
		}
		
		// HIDDEN constructor, removes it for anything not consisting of the main app //
		if(!in_array($page, $PORTAL_PAGES) && $page != "home") $scissors->paste('', "hidden");
		
		if ($page == "index") {
			
			if(isset($_SESSION['uid'])) header("Location: " . SERVER_URL . "home/");
			else {
				
				$verify = new Verify;
				$ref = '';
				
				if(array_key_exists("var_one", $_GET)) {
					
					// Assigning the Referenced User to the hidden input method //
					if($verify->user($_GET['var_one'])) $ref = $_GET['var_one'];
					
				}
				
				// Enter ref into the HTML //
				$scissors->paste(array("scripts/javascript-main.html", "scripts/signup.html"), "scripts");
				$scissors->paste("portal/signup.html", "card-content");
				
				if (PRODUCTION) $scissors->paste('<div class="g-recaptcha mb-10" data-sitekey="6LdePRUUAAAAANFgkSbkWkvmGvrEbT7IdzYhk4SM"></div>', "captcha");
				else $scissors->paste('<textarea id="g-recaptcha-response" name="g-recaptcha-response" class="g-recaptcha-response">asaskjdgasjkdagskjdga</textarea>', "captcha");
				
				$scissors->paste($ref, "ref");
				
				$scissors->update_urls("main.json", "signup");
			}
			
		} else if ($page == "password-recovery") {
	
			// Set the hidden values //
			$step = 'eu';
			$userid = $code = '';
			
			// var_one = userid, var_two = code //
			if(array_key_exists("var_one", $_GET) && array_key_exists("var_two", $_GET)) {
				
				$verify = new Verify;
				
				if($verify->password_recovery_code($_GET['var_one'], $_GET['var_two'])) {
					$step = "np"; // New password
					$userid = $_GET['var_one'];
					$code = $_GET['var_two'];
				}
			}
		
			$scissors->paste("portal/forgot-pass.html", "card-content");
			$scissors->paste(array("scripts/javascript-main.html", "scripts/passrecov.html"), "scripts");
			
			$scissors->paste((($step == "np") ? '' : "no-display"), "new-pass-hide");
			$scissors->paste((($step == "np") ? "no-display" : ''), "user-hide");
			$scissors->paste((($step == "np") ? '' : "no-display"), "retype-pass-hide");
			
			$scissors->paste($step, "step");
			$scissors->paste($userid, "userid");
			$scissors->paste($code, "code");
			
		} else if ($page == "about") {
			
			$scissors->paste("portal/about.html", "card-content");
			$scissors->update_urls("main.json", "about");
			
		} else if ($page == "terms") {
			
			$scissors->paste("portal/terms.html", "card-content");
			
		} else if ($page == "release") {
			
			$scissors->paste("portal/release.html", "card-content");
			
		} else if ($page == "banned") {
			
			// Logout //
			session_unset();
			session_destroy();
			
			$title = "Hmm ... Your not supposed to be here ...";
			$description = "";
			
			// var_one = userid //
			if(array_key_exists("var_one", $_GET)) {
				
				$verify = new Verify;
				
				if($verify->is_banned($_GET['var_one'])) {
					
					$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
					
					$result = $conn->query("SELECT * FROM ban_list WHERE userid = " . $_GET['var_one']);
					$row = $result->fetch_assoc();
					
					if($row['type'] == 'b') {
						$title = "Your account has been permanently suspended";
						$description = "Due to recent violations of our rules your account has been removed";
					} else {
						$title = "Your account has been suspended";
						$description = "Due to recent violations of our rules your account has been suspended until " . $row['date'];
					}
					
					$conn->close();
				}
			}
			
			$scissors->paste("top-nav/informational.html", "top-nav");
			$scissors->paste("webapp/banned.html", "content");
			
			$scissors->paste($title, "title");
			$scissors->paste($description, "description");
			$scissors->update_urls("main.json", "banned");
			
		} else if ($page == "home") {
			
			require_once("resources/php/helpers/misc.class.php");
			require_once("resources/php/helpers/database.class.php");
			
			$database = new Database;
			$verify = new Verify;
			$verify->logged_in();
			
			// Collect User Profile Picture //
			$proPic = $database->collect_user_pic($_SESSION['uid'], "tiny");
			
			$userSettings = $database->collect_user_settings($_SESSION['uid']);
			
			$scissors->paste("webapp/home-hidden.html", "hidden");
			$scissors->paste("top-nav/webapp-standard-ui.html", "top-nav");
			$scissors->paste("webapp/home.html", "content");
			
			$scissors->paste($_SESSION['uid'], "uid");
			$scissors->paste($proPic, "propic");
			
			$scissors->paste(htmlspecialchars($userSettings['username'][0], ENT_QUOTES, "UTF-8"), "username");
			$scissors->paste(htmlspecialchars($userSettings['name'][0], ENT_QUOTES, "UTF-8"), "fname");
			$scissors->paste(htmlspecialchars($userSettings['bemail'][0], ENT_QUOTES, "UTF-8"), "bemail");
			
		}
		
		// Update URL's //
		if ($page != "home" && $page != "banned") $scissors->update_urls("main.json", "portal");
		else $scissors->update_urls("main.json", "webapp");
			
		$scissors->update_urls("main.json");
		
		echo $scissors->html;
	}
?>