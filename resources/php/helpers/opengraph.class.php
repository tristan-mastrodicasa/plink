<?php
//--------------------------------------------------

/* Copyright 2010 Scott MacVicar
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.

 * Original can be found at https://github.com/scottmac/opengraph/blob/master/OpenGraph.php */

//--------------------------------------------------

require_once "misc.class.php";
require_once "verification.class.php";
require_once "database.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

class OpenGraph implements Iterator {
	
	/* -----------------------------------------------------------
	 * There are base schema's based on type, this is just
	* a map so that the schema can be obtained
	 * ---------------------------------------------------------- */
	 
	public static $TYPES = array(
		'activity' => array('activity', 'sport'),
		'business' => array('bar', 'company', 'cafe', 'hotel', 'restaurant'),
		'group' => array('cause', 'sports_league', 'sports_team'),
		'organization' => array('band', 'government', 'non_profit', 'school', 'university'),
		'person' => array('actor', 'athlete', 'author', 'director', 'musician', 'politician', 'public_figure'),
		'place' => array('city', 'country', 'landmark', 'state_province'),
		'product' => array('album', 'book', 'drink', 'food', 'game', 'movie', 'product', 'song', 'tv_show'),
		'website' => array('blog', 'website'),
	);
	
	// Holds all the Open Graph values we've parsed from a page //
	private $_values = array();
	
	public function fetch($URI) {
		$curl = curl_init($URI);
		curl_setopt($curl, CURLOPT_FAILONERROR, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 6);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36");
		$response = curl_exec($curl);
		curl_close($curl);
		if (!empty($response)) {
			return self::_parse($response);
		} else {
			return false;
		}
	}
	
	// Parses HTML and extracts Open Graph data, this assumes the document is at least well formed //
	static private function _parse($HTML) {
		$old_libxml_error = libxml_use_internal_errors(true);
		$doc = new DOMDocument();
		$doc->loadHTML($HTML);
		
		libxml_use_internal_errors($old_libxml_error);
		$tags = $doc->getElementsByTagName('meta');
		if (!$tags || $tags->length === 0) {
			return false;
		}
		$page = new self();
		$nonOgDescription = null;
		
		foreach ($tags AS $tag) {
			if ($tag->hasAttribute('property') &&
				strpos($tag->getAttribute('property'), 'og:') === 0) {
				$key = strtr(substr($tag->getAttribute('property'), 3), '-', '_');
				if(!array_key_exists($key, $page->_values)){
					$page->_values[$key] = $tag->getAttribute('content');
				}
			}
			
			// Added this if loop to retrieve description values from sites like the New York Times who have malformed it //
			if ($tag ->hasAttribute('value') && $tag->hasAttribute('property') &&
			    strpos($tag->getAttribute('property'), 'og:') === 0) {
				$key = strtr(substr($tag->getAttribute('property'), 3), '-', '_');
				$page->_values[$key] = $tag->getAttribute('value');
			}
			// Based on modifications at https://github.com/bashofmann/opengraph/blob/master/src/OpenGraph/OpenGraph.php //
			if ($tag->hasAttribute('name') && $tag->getAttribute('name') === 'description') {
				$nonOgDescription = $tag->getAttribute('content');
			}
			
		}
		
		// Based on modifications at https://github.com/bashofmann/opengraph/blob/master/src/OpenGraph/OpenGraph.php //
		if (!isset($page->_values['title'])) {
			$titles = $doc->getElementsByTagName('title');
			if ($titles->length > 0) {
				$page->_values['title'] = $titles->item(0)->textContent;
			}
		}
		if (!isset($page->_values['description']) && $nonOgDescription) {
			$page->_values['description'] = $nonOgDescription;
		}
		// Fallback to use image_src if ogp::image isn't set //
		if (!isset($page->values['image'])) {
			$domxpath = new DOMXPath($doc);
			$elements = $domxpath->query("//link[@rel='image_src']");
			if ($elements->length > 0) {
				$domattr = $elements->item(0)->attributes->getNamedItem('href');
				if ($domattr) {
					$page->_values['image'] = $domattr->value;
					$page->_values['image_src'] = $domattr->value;
				}
			}
		}
		if (empty($page->_values)) { return false; }
		
		return $page;
	}
	
	// Helper method to access attributes directly Example: $graph->title //
	public function __get($key) {
		if (array_key_exists($key, $this->_values)) {
			return $this->_values[$key];
		}
		
		if ($key === 'schema') {
			foreach (self::$TYPES AS $schema => $types) {
				if (array_search($this->_values['type'], $types)) {
					return $schema;
				}
			}
		}
	}
	
	// Return all the keys found on the page //
	public function keys() {
		return array_keys($this->_values);
	}
	
	//Helper method to check an attribute exists //
	public function __isset($key) {
		return array_key_exists($key, $this->_values);
	}
	
	// Will return true if the page has location data embedded //
	public function hasLocation() {
		if (array_key_exists('latitude', $this->_values) && array_key_exists('longitude', $this->_values)) {
			return true;
		}
		
		$address_keys = array('street_address', 'locality', 'region', 'postal_code', 'country_name');
		$valid_address = true;
		foreach ($address_keys AS $key) {
			$valid_address = ($valid_address && array_key_exists($key, $this->_values));
		}
		return $valid_address;
	}
	
	// Iterator code //
	private $_position = 0;
	public function rewind() { reset($this->_values); $this->_position = 0; }
	public function current() { return current($this->_values); }
	public function key() { return key($this->_values); }
	public function next() { next($this->_values); ++$this->_position; }
	public function valid() { return $this->_position < sizeof($this->_values); }
	
	/* -----------------------------------------------------------
	 * Tristan Mastrodicasa Modifications, following function provides
	 * a quick means for collecting appropiate open graph information
	 * and enter it into the database
	 * ---------------------------------------------------------- */
	
	public function collect_and_enter ($url, $postId, $postType) {
		
		$misc = new Misc;
		$verify = new Verify;
		$database = new Database;
		
		$ographr = $this->fetch($url);
		$graph = array();
		
		if(!$ographr) $ogPresent = False;
		else{
			$ogPresent = True;
			foreach ($ographr as $key => $value){
				$graph[$key] = $value;
			}
		}
		
		if($ogPresent && array_key_exists('title', $graph)){
			$inputArray = array('postid' => $postId, 'url' => $url);
			
			// Trim input //
			if(strlen($graph['title']) > 75) $inputArray['title'] = $misc->new_line_killer(substr($graph['title'], 0, 72)) . " ...";
			else $inputArray['title'] = $misc->new_line_killer($graph['title']);
			
			if(!array_key_exists('description', $graph)) $inputArray['description'] = Null;
			else if(strlen($graph['description']) > 120) $inputArray['description'] = $misc->new_line_killer(substr($graph['description'], 0, 117)) . " ...";
			else $inputArray['description'] = $misc->new_line_killer($graph['description']);
			
			if($postType == 3){
				if(!array_key_exists('video:url', $graph) || strlen($graph['video:url']) > 500) return 8;
				else {
					$inputArray['media'] = $graph['video:url'];
					$inputArray['mediafit'] = 1;
				}
			}else{
				if(!array_key_exists('image', $graph) || strlen($graph['image']) > 500) $inputArray['media'] = Null;
				else if($verify->is_url_image($graph['image'], true)){
					$inputArray['media'] = $graph['image'];
					
					list($width, $height) = getimagesize($inputArray['media']);
					
					if($width > $height && $width > 500) $inputArray['mediafit'] = 1;
					else $inputArray['mediafit'] = 0;
				}else $inputArray['media'] = Null;
			}
			
			if($inputArray['media'] == Null) $inputArray['mediafit'] = Null;
			
			// Currently my database only accepts ASCII characters //
			$inputArray['description'] = iconv(mb_detect_encoding($inputArray['description']), "ASCII//IGNORE", $inputArray['description']);
			$inputArray['title'] = iconv(mb_detect_encoding($inputArray['title']), "ASCII//IGNORE", $inputArray['title']);
			
			$database->insert("open_graph", $inputArray, "issssi");
		}else{
			// If no open graph is found then the url is removed //
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			
			$result = $conn->query("SELECT type FROM post_data WHERE postid = $postId");
			$row = $result->fetch_assoc();
			
			// If the post is supposed to be a video delete it //
			if($row['type'] == 3) {
				$database->delete_post($postId);
				return 13;
			} else $conn->query("UPDATE post_data SET url = 0 WHERE postid = " . $postId);
			
			$conn->close();
		}
		return 0;
	}
}