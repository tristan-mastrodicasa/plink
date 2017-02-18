<?php
//--------------------------------------------------

/* This php code provides a class which deals with 
 * image manipulation and storage. It does not verify
 * images however which is the job of verify->image() */

//--------------------------------------------------

require_once "database.class.php";
require_once "misc.class.php";
require_once __DIR__ . "/../config.php";

//--------------------------------------------------

class Image {
	
	private function append_zero ($str, $num) {
		$imgId = strval($str);
		$loop = $num - strlen($imgId);
		
		while($loop != 0){
			$imgId = '0' . $imgId;
			$loop--;
		}
		
		return $imgId;
	}

	private function new_dir_entry ($dir) {
		mkdir(USER_CONTENT . "images/large/" . $dir);
		mkdir(USER_CONTENT . "images/medium/" . $dir);
		mkdir(USER_CONTENT . "images/small/" . $dir);
		mkdir(USER_CONTENT . "images/tiny/" . $dir);
	}
	
	public function find_path ($id) {
		
		/* -----------------------------------------------------------
		 * Converts the id of an image in the database to an image path
		 * which can be used to pass to HTML src tags
		 * ---------------------------------------------------------- */
		
		if($id % 1000 == 0){
			$pathNum = $id / 1000;
			$dirPath = $this->append_zero($pathNum, 9);
			
			if(!is_dir(USER_CONTENT . "images/medium/" . substr($dirPath, 0, 3))) {
				$this->new_dir_entry(substr($dirPath, 0, 3));
			}
			if(!is_dir(USER_CONTENT . "images/medium/" . substr($dirPath, 0, 3) . '/' . substr($dirPath, 3, 3))) {
				$this->new_dir_entry(substr($dirPath, 0, 3) . '/' . substr($dirPath, 3, 3));
			}
			if(!is_dir(USER_CONTENT . "images/medium/" . substr($dirPath, 0, 3) . '/' . substr($dirPath, 3, 3) . '/' . substr($dirPath, 6, 3))) {
				$this->new_dir_entry(substr($dirPath, 0, 3) . '/' . substr($dirPath, 3, 3) . '/' . substr($dirPath, 6, 3));
			}
		}
		
		$dirPath = $this->append_zero(floor($id / 1000), 9);
		return substr($dirPath, 0, 3) . '/' . substr($dirPath, 3, 3) . '/' . substr($dirPath, 6, 3) . '/' . $this->append_zero($id, 12);
	}

	private function convert ($imageSrc, $maxDem) {
		
		/* -----------------------------------------------------------
		 * Convert image to appropiate dementions and possibly crop for
		 * profile picturesz
		 * ---------------------------------------------------------- */
		
		if(is_array($imageSrc)) $fn = $imageSrc['tmp_name'];
		else $fn = $imageSrc;
		
		$imgSize = getimagesize($fn);
		
		if($imgSize[0] > $maxDem || $imgSize[1] > $maxDem){
			$ratio = $imgSize[0] / $imgSize[1];
			
			if($ratio > 1) {
				$width = $maxDem;
				$height = $maxDem / $ratio;
			} else {
				$width = $maxDem * $ratio;
				$height = $maxDem;
			}
		}else{
			$width = $imgSize[0];
			$height = $imgSize[1];
		}
		$src = imagecreatefromstring(file_get_contents($fn));
		
		if($src === False) return False;
		
		// Orientate in case image from mobile //
		if($this->orient_image($fn, $src, $width, $height)) {
			$misc = new Misc;
			$misc->swap_values($imgSize[0], $imgSize[1]);
		}
			
		$newImage = imagecreatetruecolor($width, $height);
		imagecopyresampled($newImage, $src, 0, 0, 0, 0, $width, $height, $imgSize[0], $imgSize[1]);
		imagedestroy($src);
		
		return $newImage;
	}
	
	public function generate_profile_image ($imageSrc, $size) {
		
			/* -----------------------------------------------------------
			 * Crop-to-fit PHP-GD
			 * http://salman-w.blogspot.com/2009/04/crop-to-fit-image-using-aspphp.html
			 *
			 * Resize and center crop an arbitrary size image to fixed width and height
			 * e.g. convert a large portrait/landscape image to a small square thumbnail
			 * ---------------------------------------------------------- */
			
			switch($size) {
				case "large" : 
					$width = 450;
					$height = 450;
					break;
				case "medium" :
					$width = 150;
					$height = 150;
					break;
				case "small" : 
					$width = 50;
					$height = 50;
					break;
				case "tiny" : 
					$width = 40;
					$height = 40;
					break;
			}
			
			$DESIRED_IMAGE_WIDTH = $width;
			$DESIRED_IMAGE_HEIGHT = $height;
			
			if(is_array($imageSrc)) $source_path = $imageSrc['tmp_name'];
			else $source_path = $imageSrc;
			
			
			list($source_width, $source_height, $source_type) = getimagesize($source_path);
			
			switch ($source_type) {
				case IMAGETYPE_GIF:
					$source_gdim = imagecreatefromgif($source_path);
					break;
				case IMAGETYPE_JPEG:
					$source_gdim = imagecreatefromjpeg($source_path);
					break;
				case IMAGETYPE_PNG:
					$source_gdim = imagecreatefrompng($source_path);
					break;
			}
			
			// Orientate in case image from mobile //
			$this->orient_image($source_path, $source_gdim, $source_width, $source_height);
			
			$source_aspect_ratio = $source_width / $source_height;
			$desired_aspect_ratio = $DESIRED_IMAGE_WIDTH / $DESIRED_IMAGE_HEIGHT;
			
			if ($source_aspect_ratio > $desired_aspect_ratio) {
				
				// Triggered when source image is wider //
				$temp_height = $DESIRED_IMAGE_HEIGHT;
				$temp_width = ( int ) ($DESIRED_IMAGE_HEIGHT * $source_aspect_ratio);
			} else {
				
				// Triggered otherwise (i.e. source image is similar or taller) //
				$temp_width = $DESIRED_IMAGE_WIDTH;
				$temp_height = ( int ) ($DESIRED_IMAGE_WIDTH / $source_aspect_ratio);
			}
			
			// Resize the image into a temporary GD image //
			$temp_gdim = imagecreatetruecolor($temp_width, $temp_height);
			imagecopyresampled(
				$temp_gdim,
				$source_gdim,
				0, 0,
				0, 0,
				$temp_width, $temp_height,
				$source_width, $source_height
			);
			
			// Copy cropped region from temporary image into the desired GD image //
			
			$x0 = ($temp_width - $DESIRED_IMAGE_WIDTH) / 2;
			$y0 = ($temp_height - $DESIRED_IMAGE_HEIGHT) / 2;
			$desired_gdim = imagecreatetruecolor($DESIRED_IMAGE_WIDTH, $DESIRED_IMAGE_HEIGHT);
			imagecopy(
				$desired_gdim,
				$temp_gdim,
				0, 0,
				$x0, $y0,
				$DESIRED_IMAGE_WIDTH, $DESIRED_IMAGE_HEIGHT
			);
			
			// Render the image, Alternatively, you can save the image in file-system or database //
			imagedestroy($temp_gdim);
			imagedestroy($source_gdim);
			return $desired_gdim; // Make JPEG FROM IT
	}
	
	public function enter_image ($imageSrc, $postId, $propic = false) {
		
		/* -----------------------------------------------------------
		 * Takes the image source and post id to anchor and upload the image
		 * to the post and possibly the user (image can be a url)
		 * ---------------------------------------------------------- */
		
		$database = new Database;
		
		// Convert images to a small and large version //
		$imgCopyLarge = $this->convert($imageSrc, 1920);
		$imgCopyMedium = $this->convert($imageSrc, 500);
		
		if(is_array($imageSrc)) {
			if(strtolower(pathinfo(basename($imageSrc['name']), PATHINFO_EXTENSION)[0]) == 'g') $imgType = 'g';
			else if(strtolower(pathinfo(basename($imageSrc['name']), PATHINFO_EXTENSION)[0]) == 'j') $imgType = 'j';
			else $imgType = 'p';
		} else {
			// If the Image src is a string then we know it was taken from the temporary //
			// profile picture code which are all jpg's //
			$imgType = 'j';
		}
		
		if($propic) {
			$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
			$result = $conn->query("SELECT * FROM photo_data WHERE propic = 1 AND userid = " . $_SESSION['uid']);
			
			if($result->num_rows > 0) {
				$conn->query("DELETE FROM photo_data WHERE propic = 1 AND userid = " . $_SESSION['uid']);
			}
		}
		
		// Entering the Photo data //
		$insertId = $database->insert("photo_data", array('userid' => $_SESSION['uid'], 'postid' => $postId, 'type' => $imgType,
		'propic' => (($propic) ? 1 : 0), 'date' => date("Y-m-d"), 'time' => date("H:i:s")), "iisiss");
		
		// Calculate Img Path //
		$path = $this->find_path($insertId);
		
		if($imgType == 'g'){
			imagegif($imgCopyLarge, USER_CONTENT . "images/large/" . $path . ".gif");
			imagegif($imgCopyMedium, USER_CONTENT . "images/medium/" . $path . ".gif");
		} else if ($imgType == 'j'){
			if($propic) {
				$imM = $this->generate_profile_image($imageSrc, "medium");
				$imS = $this->generate_profile_image($imageSrc, "small");
				$imT = $this->generate_profile_image($imageSrc, "tiny");
				
				imagejpeg($imM, USER_CONTENT . "images/medium/" . $path . ".jpg", 100);
				imagejpeg($imS, USER_CONTENT . "images/small/" . $path . ".jpg", 100);
				imagejpeg($imT, USER_CONTENT . "images/tiny/" . $path . ".jpg", 100);
				
				imagedestroy($imM);
				imagedestroy($imS);
				imagedestroy($imT);
			} else {
				imagejpeg($imgCopyLarge, USER_CONTENT . "images/large/" . $path . ".jpg", 100);
				imagejpeg($imgCopyMedium, USER_CONTENT . "images/medium/" . $path . ".jpg", 100);
			}
		} else {
			imagepng($imgCopyLarge, USER_CONTENT . "images/large/" . $path . ".png", 2);
			imagepng($imgCopyMedium, USER_CONTENT . "images/medium/" . $path . ".png", 2);
		}
		
		// Watch Out For Possible Memory Leaks //
		imagedestroy($imgCopyLarge);
		imagedestroy($imgCopyMedium);
	}
	
	private function orient_image ($fn, &$image, &$width, &$height) {
		
		/* -----------------------------------------------------------
		 * This function helps orientate images that are sideways if
		 * taken on phones. I have taken this function from 
		 * http://php.net/manual/en/function.exif-read-data.php
		 * ---------------------------------------------------------- */
		
		$exif = @exif_read_data($fn);
		
		if(!empty($exif['Orientation'])) {
			switch($exif['Orientation']) {
				case 8:
					$image= imagerotate($image, 90, 0);
					
					$misc = new Misc;
					$misc->swap_values($width, $height);
					
					return true;
					break;
					
				case 3:
					$image = imagerotate($image, 180, 0);
					break;
					
				case 6:
					$image= imagerotate($image, 270, 0);
					
					$misc = new Misc;
					$misc->swap_values($width, $height);
					
					return true;
					break;
			}
		}
		
		return false;
		
	}
	
	public function retrieve_image ($postid) {
		
		/* -----------------------------------------------------------
		 * Retrieves the url of the associated image to a postid
		 * ---------------------------------------------------------- */
		 
		$conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
		
		$result = $conn->query("SELECT id, type FROM photo_data WHERE postid = " . $postid);
		$row = $result->fetch_assoc();
		
		if ($row['type'] == 'p') $extension = ".png";
		else if ($row['type'] == 'j') $extension = ".jpg";
		else $extension = ".gif";
		
		// This is not the full url, client side will need to append the website's URL //
		return $this->find_path($row['id']) . $extension;
		
		$conn->close();
	}
}

?>