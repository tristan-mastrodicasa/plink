//--------------------------------------------------

/* Following JS code provides all of the background 
 * processes for validating, transporting and recieving data */

//--------------------------------------------------

////////////////////////////
// Main Data Handler Object
var dataHandler = { 
	mobileDetect : function() {
		var check = false;
		(function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
		return check;
	},
	ajaxHelper : function (location, data, dataHandler) {
		
		/* -----------------------------------------------------------
		 * This function makes sending and retrieving JSON data easy
		 * ---------------------------------------------------------- */
		
		var timeout = 9000;
		
		if(dataHandler) $.ajax({type : "POST", url : DOMAIN + location, data : data, dataType : 'json', success : function(response){ dataHandler(response); }, error : function(){ dataHandler(false); }, timeout: timeout });
		else $.ajax({type : "POST", url : DOMAIN + location, data : data, dataType : 'json', timeout: timeout });
	},
	assignEventListener : function (action, elemClass, callback) {
		
		/* -----------------------------------------------------------
		 * When called adds events listeners to elements according to class
		 * ---------------------------------------------------------- */
		
		var elements = document.getElementsByClassName(elemClass);
		for(var i = 0, len = elements.length; i < len; i++){
			// Off prevents doubling up //
			$(elements[i]).off(action);
			$(elements[i]).on(action, callback);
		}
	},
	escapeRegExp : function (str) {
		
		/* -----------------------------------------------------------
		 * Escapes special characters in a string for regex
		 * ---------------------------------------------------------- */
		 
		// http://stackoverflow.com/questions/1144783/replacing-all-occurrences-of-a-string-in-javascript @Cory Gross //
		return str.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"); // $& means the whole matched string
	},
	htmlspecialchars : function (str) {
		
		/* -----------------------------------------------------------
		 * Converts user entered strings to safe html appendable strings
		 * ---------------------------------------------------------- */
		 
		str = str.replace(/&/g, "&amp;");
		str = str.replace(/>/g, "&gt;");
		str = str.replace(/</g, "&lt;");
		str = str.replace(/"/g, "&quot;");
		str = str.replace(/'/g, "&#039;");
		return str;
	},
	clearNodes : function (parentDOM) {
		
		/* -----------------------------------------------------------
		 * Uses JQuery to remove all child nodes in a DOM
		 * ---------------------------------------------------------- */
		
		$(parentDOM).empty();
		
	},
	verify : {
		postContent : function (publishType, input) { 
			
			/* -----------------------------------------------------------
			 * This code verifies on the Front-End the input for posts
			 * ---------------------------------------------------------- */
			
			switch(publishType){
				
				// Article //
				case 1:
					if (!input.written) return 3;
					else if (input.written.length > 30000) return 1;
					else if (input.topic == 'n') return 5;
					else return 0;
					break;
					
				// Photo //
				case 2:
					if (input.written.length > 30000) return 1;
					else if (!input.imageSrc && !input.imageUrl) return 4;
					else if (input.topic == 'n') return 5;
					else return 0;
					break;
					
				// Video Link //
				case 3:
					if(!input.videoUrl) return 3;
					else if(input.written.length + input.videoUrl > 30000) return 1;
					else if(input.topic == 'n') return 5;
					else return 0;
					break;
					
				// Blurb //
				case 4:
					if (!input.written) return 3;
					else if(input.written.length > 150) return 2;
					else return 0;
					break;
			}
		},
		imageUrl : function (url, callback, timeout){
			
			/* -----------------------------------------------------------
			 * Tests if a url is a valid image
			 * ---------------------------------------------------------- */
			
			timeout = timeout || 5000;
			var timedOut = false, timer;
			var img = new Image();
			img.onerror = img.onabort = function() {
				if (!timedOut){
					clearTimeout(timer);
					callback(url, false);
				}
			};
			img.onload = function() {
				if (!timedOut) {
					clearTimeout(timer);
					callback(url, true);
				}
			};
			img.src = url;
			timer = setTimeout(function() {
				timedOut = true;
				
				// reset .src to invalid URL so it stops previous //
				// loading, but doesn't trigger new load //
				img.src = "//!!!!/test.jpg";
				callback(url, false);
			}, timeout); 
		}
	},
	profile : { 
		retrieve : function (DOM) {
			
			/* -----------------------------------------------------------
			 * Requests profile information from the servers and sends response
			 * to the handler to update the profile information
			 * ---------------------------------------------------------- */
			
			var profileId;
			
			if(!$(DOM).attr("data-param")) profileId = sysTrack.profileUserId;
			else profileId = parseInt($(DOM).attr("data-param"));
			
			// Hide profile card and show loading sign //
			$("#profile-card").hide();
			$(animation.globals.loaders.pc).show();
			
			dataHandler.ajaxHelper("resources/php/handlers/profile.php", {id : profileId}, animation.profile.update);
		}
	},
	post : {
		seeMoreText : function () {
			
			/* -----------------------------------------------------------
			 * Retrieves the increments of text to be displayed to the user
			 * ---------------------------------------------------------- */
			 
			var pid = $(this).attr("data-pid");
			var icr = $(this).attr("data-increment");
			
			dataHandler.ajaxHelper("resources/php/handlers/seemore.php", {postid : pid, increment : icr}, animation.post.updateText);
		}
	},
	scrollEngine : function (layer, refresh = false) {
		
		/* -----------------------------------------------------------
		 * This function is run everytime the user scrolls and new posts
		 *  / results need to be loaded
		 * ---------------------------------------------------------- */
		
		var headers = {};
		var handler = "resources/php/handlers/populate.php";
		
		// Changing the populator may cause problems //
		// Notably not running dataHandler.populateTillScroll //
		var populator = animation.post.populate;
		
		headers.layer = layer;
		
		switch (layer) {
			case 'p' : 
				headers.userid = sysTrack.currentLoadedUser;
				break;
			case 'pa' : 
				headers.userid = sysTrack.currentLoadedUser;
				break;
			case 'l' : 
				headers.sort = $("#liked-sort").val();
				headers.topic = $("#liked-topic").val();
				
				break;
			case 'm' : 
				handler = "resources/php/handlers/main.php";
				break;
			case 's' : 
				handler = "resources/php/handlers/search.php";
				headers.query = sysTrack.activeSearchQ;
				headers.hashtagFirstId = sysTrack.firstHashId;
				break;
			
			case 'sb' : 
				headers.sort = $("#sub-sort").val();
				headers.topic = $("#sub-topic").val();
				headers.query = $("#sub-query").val();
				break;
		}
		
		if(layer != 'm') {
			
			if(layer == 'p' || layer == 'pa') {
				if($(animation.globals.insertionContainer[layer] + " > div").length != 0) headers.last = $(animation.globals.insertionContainer[layer] + " > div").last().attr("data-post-id");
				else headers.last = 0;
			} else {
				
				if(!refresh) {
					// Collect the last node id //
					headers.last = $(animation.globals.insertionContainer[layer]).children().length;
				} else headers.last = 0;
				
			}
			
		}
		
		dataHandler.ajaxHelper(handler, headers, populator);
	},
	populateTillScroll : function () {
		if (($(document).height() - 43) > $(window).height()) {
			return;
		} else {
			$(window).scroll();
		}
	},
	qualityControl : function (data, state) {
		
		/* -----------------------------------------------------------
		 * Checks if the URL succeeded in loading the associated media
		 * (currently only images) and if anything did not load then
		 * the server will validate.
		 * ---------------------------------------------------------- */
		 
		if(state == false) {
			dataHandler.ajaxHelper("resources/php/handlers/checkmedia.php", {url : data});
		}
	}
};