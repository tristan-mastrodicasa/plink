<?php
//--------------------------------------------------

/* This php code provides a class which standardises
 * the output or response of the different handler files */

//--------------------------------------------------

require_once "postcompiler.function.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

class Output {
	
	public function json_through_iframe ($json, $handler) {
		echo "<script>window.onload = window.top.window." . $handler . "(" . $this->json_response($json) . ")</script>";
	}
	
	// Publishing Posts UI NOTE: Images need to output errors through their frames //
	public function publish_output_error ($cd, $image = false) {
		
		/* -----------------------------------------------------------
		 * This method is responsible for outputing any errors to the main
		 * application when a post is submitted for publishing
		 * ---------------------------------------------------------- */
		 
		if($cd) {
			if (!$image) die($this->json_response(array("errorMsg" => $cd)));
			
			// Outputs the response in the frame (when uploading images) //
			else die("<script>window.onload = window.top.window.animation.publish.responseHandler(" . $this->json_response(array("errorMsg" => $cd)) . ")</script>");
		}
	}
	
	public function publish_output_post ($postId, $image = false) {
		
		/* -----------------------------------------------------------
		 * This method is responsible for outputing the finished post 
		 * JSON to the website when no error is found (code : 0)
		 * ---------------------------------------------------------- */
		
		$compiledPost = $this->post_meta_apply(compile_post($postId), 1, 'm', true);
		
		if (!$image) echo $this->json_response(array("errorMsg" => 0, "postJson" => $compiledPost));
		
		// Outputs the response in the frame (when uploading images) //
		else echo "<script>window.onload = window.top.window.animation.publish.responseHandler(" . $this->json_response(array("errorMsg" => 0, "postJson" => $compiledPost)) . ")</script>";
	}
	
	public function post_meta_apply ($postJson, $method, $layer, $posted = false) {
		
		/* -----------------------------------------------------------
		 * This method is responsible for outputing the finished post 
		 * JSON after applying the appropiate layer/method/posted params
		 * ---------------------------------------------------------- */
		 
		$postJson['postMeta']['method'] = $method;
		$postJson['postMeta']['posted'] = $posted;
		$postJson['postMeta']['layer'] = $layer;
		
		return $postJson;
	}
	
	public function post_analysis_apply ($postJson) {
		
		/* -----------------------------------------------------------
		 * This method is responsible for outputing the finished post 
		 * JSON after applying the analysis specific JSON
		 * ---------------------------------------------------------- */
		
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		// Collecting the number of post views //
		$result = $conn->query("SELECT COUNT(*) FROM post_loaded WHERE postid = " . $postJson['postMeta']['postId']);
		$row = $result->fetch_assoc();
		
		$postJson['postActions']['seenNum'] = $row['COUNT(*)'];
	}
	
	// JSON response standardisation //
	public function json_response ($data) {
		return json_encode($data);
	}
}

?>