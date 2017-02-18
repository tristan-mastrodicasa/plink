<?php
	
	//--------------------------------------------------
	
	 /* 
	  * Created by Tristan Mastrodicasa
	  * Scissors is a HTML management system to maintain better
	  * organisation between UI and serverside code. It also 
	  * serves as an easy way to scale and modify HTML across 
	  * large enterprise level websites
	  */
	
	//--------------------------------------------------
	
	class Scissors {
		
		// The main HTML directory and HTML output //
		private $html_dir = "/";
		public $html = '';
		
		public function __construct ($html_dir) {
			
			/* -----------------------------------------------------------
			 * This is the constructor function which is responsible for 
			 * configuring the scissors class (like the html directory)
			 * ---------------------------------------------------------- */
			
			// Set the directory containing all of the HTML files //
			if (isset($html_dir) && is_string($html_dir) && is_dir($html_dir)) {
				
				// Append a slash if the directory definition doen't have one //
				if (substr($html_dir, -1) != '/') $html_dir .= '/';
				$this->html_dir = $html_dir;
				
			} else throw new Exception("Main html file directory is not defined");
			
		}
		
		public function set_canvas ($html) {
			
			/* -----------------------------------------------------------
			 * This function takes a string or file path and set's it as the
			 * main canvas which all other html code will be cut and pasted to
			 * ---------------------------------------------------------- */
			
			// Check if the canvas has already been set //
			if (strlen($this->html) > 0) throw new Exception("Canvas has already been set");
			
			// Take the canvas HTML from a file if path exists //
			if (is_file($this->html_dir . $html)) $this->html .= file_get_contents($this->html_dir . $html);
			
			// Otherwise the passed param is treated as a string //
			else $this->html .= $html;
		}
		
		public function paste ($html, $identifier, $is_text = false) {
			
			/* -----------------------------------------------------------
			 * This function takes a string or file path or an array of both 
			 * ($html) and pastes it to (replaces) the identifier string 
			 * ($identifier) where ever found within the canvas. Pasted HTML 
			 * can also contain identifiers which can have HTML pasted to them 
			 * as well. If $is_text is set to true than any entered string ($html) 
			 * will pass though htmlspecialchars
			 * ---------------------------------------------------------- */
			
			// Plugs any html sources into an array //
			if (!is_array($html)) $html = array($html);
			
			// Append all html from all sources into a single string //
			$html_final = '';
			
			foreach($html as $source) {
				
				// Compile the final HTML output (to be pasted) //
				if (is_file($this->html_dir . $source)) $html_final .= file_get_contents($this->html_dir . $source);
				else $html_final .= $source;
				
			}
			
			// Clean text if set //
			if($is_text) $html_final = htmlspecialchars($html_final, ENT_SUBSTITUTE);
			
			// Escape all occurences of '-' //
			$identifier = str_replace("-", '\-', $identifier);
			
			// Replace the html //
			$this->html = preg_replace("/{{{\ $identifier\ }}}/", $html_final, $this->html);
		}
		
		public function update_urls ($json_path, $json_key = false) {
			
			/* -----------------------------------------------------------
			 * This function replaces all of the identifiers with the prefix
			 * 'a:' (ex. {{{ a:home }}} ) with url's defined inside a JSON file
			 * within the main HTML directory
			 * 
			 * You can define a key for $json_key to restrict which variables
			 * to loop through. If $json_key is false then only keys in the 
			 * 'globals' object will be looped
			 * ---------------------------------------------------------- */
			
			// Check if the JSON file exists //
			if (is_file($this->html_dir . $json_path)) $json_raw = file_get_contents($this->html_dir . $json_path);
			else throw new Exception("JSON file not found");
			
			// Parse JSON data from JSON file //
			$json = json_decode($json_raw, true);
			if ($json == null) throw new Exception("Error parsing JSON file");
			
			// Choose a set of key => values to loop through //
			if(!$json_key) $parent_key = 'globals';
			else $parent_key = $json_key;
			
			foreach(array_keys($json[$parent_key]) as $key) {
				
				// Check if the URL is complete //
				if (!filter_var($json[$parent_key][$key], FILTER_VALIDATE_URL)) { 
					
					// If the URL is incomplete than append the SERVER URL //
					$json[$parent_key][$key] =  ((array_key_exists("HTTPS", $_SERVER) && $_SERVER['HTTPS'] == "on") ? "https://" : "http://") . $_SERVER['SERVER_NAME'] . '/' . $json[$parent_key][$key];
				}
				
				// Escape all occurences of '-' //
				$identifier = str_replace("-", '\-', $key);
				
				// Replace the links //
				$this->html = preg_replace("/{{{\ a:$identifier\ }}}/", $json[$parent_key][$key], $this->html);
			}
			
		}
		
	}
	
?>