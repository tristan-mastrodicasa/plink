//--------------------------------------------------

/* Following JS code provides all of the full-stack
 * processes for specific events on the webpage which uses The
 * utilities provided by data-handler.js and animation.js */

//--------------------------------------------------

////////////////////////////
// Main Applet Object
var applet = { 
	linkEngine : {
		hash : function () {
			
			/* -----------------------------------------------------------
			 * This function is attached to all anchors, instead of changing
			 * the page to a link defined in the 'href' attribute it instead
			 * looks at the 'data-loc' attribute to change 'focus-layers'
			 * which is a unique way of navigating the website or web app
			 * ---------------------------------------------------------- */
			
			sysTrack.scrollLoad = false;
			sysTrack.pidToBeDel = 0;
			sysTrack.pidToBeRep = 0;
			sysTrack.pidToBeEdit = 0;
			sysTrack.pidEditActive = 0;
			
			// Changes Layer //
			var fullLocation, focusLayer, subLayer;
			fullLocation = location.hash;
			focusLayer = fullLocation.match(/\#(.*):/);
			subLayer = fullLocation.match(/:(.*)/);
			
			// Remember the scroll postion to be restored later when navigating back to the main feed //
			if((sysTrack.currentLayer + ':' + sysTrack.currentSubLayer) == "1:0"){
				sysTrack.mainScrollPosition = $(document).scrollTop();
			}
			
			// Hide / Show change propic overlay //
			if((sysTrack.currentLayer + ':' + sysTrack.currentSubLayer) == "2:5"){
				$("#propic-overlay").hide();
				$("#propic-confirm").hide();
				$("#propic-src").val('');
				dataHandler.ajaxHelper("resources/php/handlers/profile.php", {id : sysTrack.profileUserId}, animation.profile.update);
			}
			if((focusLayer[1] + ':' + subLayer[1]) == "2:5"){
				$("#propic-overlay").show();
				$("#profpic-select").show();
			}
			
			// Load the user's profile if they try to access their sublayers //
			if((sysTrack.currentLoadedUser == 0 || sysTrack.currentLoadedUser != sysTrack.profileUserId) && (focusLayer[1] == 2 && subLayer[1] != 1)) {
				dataHandler.profile.retrieve(null);
			}
			
			// Animate for page change, otherwise don't for sublayer change //
			if(sysTrack.currentLayer == focusLayer && sysTrack.currentSubLayer != subLayer){
				animation.pageNav.changeSublayer(focusLayer[1], subLayer[1]);
				
				// Update Global Variables with new Focus Layers //
				sysTrack.currentLayer = focusLayer[1];
				sysTrack.currentSubLayer = subLayer[1];
			}else{
				animation.pageNav.changeLayer(focusLayer[1], subLayer[1]);
			}
			
			if(sysTrack.currentLayer == '1' || (sysTrack.currentLayer == '2' && sysTrack.currentSubLayer == '1')) {
				dataHandler.ajaxHelper("resources/php/handlers/notify.php", {}, animation.popup.notification);
			}
			
			sysTrack.scrollLoad = true;
			
			// run scroll event to run the populate for the main //
			if((sysTrack.currentLayer + ':' + sysTrack.currentSubLayer) == "1:0") $(window).scroll();
		},
		callback : function () {
			
			// Runs Callbacks //
			switch($(this).attr("data-callback")) { 
				case "profile" : dataHandler.profile.retrieve(this); break;
				case "profileAnalyse" : dataHandler.ajaxHelper("resources/php/handlers/analysis.php", {}, animation.profile.analysis); break;
				case "subscriptions" :
					$("#sub-topic").prop("selectedIndex", 'n');
					$("#sub-sort").prop("selectedIndex", 0);
					$("#sub-query").val('');
					dataHandler.scrollEngine('sb', true); 
					
					break;
				case "likedPosts" :
					$("#liked-topic").prop("selectedIndex", 'n');
					$("#liked-sort").prop("selectedIndex", 0);
					dataHandler.scrollEngine('l', true); 
					
					break;
				
				case "search":
					$('#top-nav-search').val($(this).attr("data-param"));
					animation.search.query($(this).attr("data-param"));
					break;
			}
		}
	},
	portal : {
		submit : function () {
			
			if(inputMethod == 1) {
				var input = {
					f : $("#f").val(),
					u : $("#u").val(),
					se : $("#se").val(),
					p : $("#p").val(),
					captcha : $("#g-recaptcha-response").val(),
					reference : $("input[name='reference']").val(),
					step : $("input[name='step']").val()
				};
				var location = "resources/php/handlers/signup.php";
			} else {
				var input = {
					u : $("#u").val(),
					p : $("#p").val()
				};
				var location = "resources/php/handlers/login.php";
			}
			
			dataHandler.ajaxHelper(location, input, animation.portal.responseHandler);
		},
		passRecovery : {
			submit : function () {
				var step = $("#step").val();
				
				var headers = {step : step};
				
				if(step == 'eu') {
					headers.username = $("#un").val();
					dataHandler.ajaxHelper("resources/php/handlers/passrecov.php", headers);
					animation.portal.passRecovery.message();
				} else {
					headers.p = $("#p").val();
					headers.p2 = $("#p2").val();
					
					headers.userid = $("#userid").val();
					headers.code = $("#verify").val();
					dataHandler.ajaxHelper("resources/php/handlers/passrecov.php", headers, animation.portal.passRecovery.responseHandler);
				}
			}
		}
		
	},
	publishUI : { 
		phpLoc : "resources/php/handlers/publish.php",
		submit : function (formDOM) { 
			
			/* -----------------------------------------------------------
			 * This code is the one to run once the submit button is pressed 
			 * for the main publish UI
			 * ---------------------------------------------------------- */
			
			// Hides existing error //
			animation.publish.hideError();
			
			// Collect the value of all inputs //
			var input = { 
				written : $("#textbox").val(),
				topic : $("#publish-topic-chooser").val(),
				imageSrc : $("#image-src").val(),
				imageUrl : $("#image-url-input").val(),
				videoUrl : $("#video-input").val(),
				
				// Find out what type of post //
				type : parseInt($(formDOM).find("#upload-type").val())
			};
			
			// Checks input for errors //
			var code = dataHandler.verify.postContent(input.type, input);
			
			// Display Load Bar //
			$("#publish-loading-sign").show();
			
			if(code != 0) { 
				$("#publish-loading-sign").hide();
				animation.publish.showError(code);
			} else if (input.type != 2) dataHandler.ajaxHelper(this.phpLoc, input, animation.publish.responseHandler);
			else { 
				$("#img-form").attr("action", DOMAIN + this.phpLoc);
				$("#img-desc").attr("value", input.written);
				$("#img-top").attr("value", input.topic);
				$("#img-type").attr("value", input.type);
				$("#img-link").attr("value", input.imageUrl);
				document.getElementById("img-form").submit();
				$("#publish-loading-sign").show();
			}
		}
	},
	profile : {
		subscribe : function (DOM) {
			
			/* -----------------------------------------------------------
			 * This function is attached to the 'subscribe' buttons
			 * ---------------------------------------------------------- */
			 
			if($(DOM).attr('data-com') == 1) var state = true;
			else var state = false;
			
			var user = parseInt($("#profile-layer-public-buttons").attr("data-uid"));
			
			animation.profile.subscribe(state);
			dataHandler.ajaxHelper("resources/php/handlers/profileaction.php", {huserid : user, action : 1});
		},
		admire : function (DOM) {
			
			/* -----------------------------------------------------------
			 * This function is attached to the 'admire' buttons
			 * ---------------------------------------------------------- */
			 
			if($(DOM).attr('data-com') == 1) var state = true;
			else var state = false;
			
			var user = parseInt($("#profile-layer-public-buttons").attr("data-uid"));
			
			animation.profile.admire(state);
			dataHandler.ajaxHelper("resources/php/handlers/profileaction.php", {huserid : user, action : 2});
		},
		unSubX : function (userid) {
			
			/* -----------------------------------------------------------
			 * Unsubscribes a user via the 'x' button in the subscriptions
			 * ---------------------------------------------------------- */
			
			$(animation.globals.insertionContainer['sb'] + " div[data-uid='" + userid + "']").remove();
			dataHandler.ajaxHelper("resources/php/handlers/profileaction.php", {huserid : userid, action : 1});
		},
		settings : function () {
			
			/* -----------------------------------------------------------
			 * This code is the one to run once the submit button is pressed 
			 * for the settings layer in the profile sub layers
			 * ---------------------------------------------------------- */
			
			// Hide all settings errors //
			animation.error.hide('u');
			animation.error.hide('f');
			animation.error.hide('be');
			animation.error.hide('p');
			animation.error.hide('p2');
			animation.error.hide('po');
			
			// Collect Form Inputs //
			var input = { 
				u : $("#u").val(),
				f : $("#f").val(),
				be : $("#be").val(),
				p : $("#p").val(),
				p2 : $("#p2").val(),
				po : $("#po").val()
			};
			
			// Hide 'Saved' //
			animation.profile.settings.toggleSaved(false);
			
			dataHandler.ajaxHelper("resources/php/handlers/settings.php", input, animation.profile.settings.update);
		},
		changePropic : function () {
			
			// Hide Camera Input //
			$("#profpic-select").hide();
			
			if($("#propic-src").val()) {
				// Show Loading //
				$("#profpic-load").show();
				
				$("#propic-form").attr("action", DOMAIN + "resources/php/handlers/profpicchange.php");
				document.getElementById("propic-form").submit();
			}
		},
		confirmNewPropic : function (accepted) {
			$("#propic-confirm").hide();
			
			if (accepted) {
				dataHandler.ajaxHelper("resources/php/handlers/profpicconfirm.php", {choice : true}, animation.profile.updatePropicIcon);
			} else {
				dataHandler.ajaxHelper("resources/php/handlers/profile.php", {id : sysTrack.profileUserId}, animation.profile.update);
				dataHandler.ajaxHelper("resources/php/handlers/profpicconfirm.php", {choice : false});
				$("#propic-overlay").show();
				$("#profpic-select").show();
				$("#propic-src").val('');
			}
		}
	},
	post : {
		actionActiveImg : {endorse : "endorsed.png", repost : "reposted.png", comment : "commented.png"},
		actionDeadImg : {endorse : "endorse.png", repost : "repost.png", comment : "comment.png"},
		actionColor : {endorse : "#00B034", repost : "#00A8EC", comment : "#919191"},
		actionPhp : "resources/php/handlers/postaction.php",
		
		endorse : function () {
			
			/* -----------------------------------------------------------
			 * This function is responsable for the endorsements of posts
			 * when the endorsement button is pressed, it changes the color
			 * of the icon as well as the number
			 * ---------------------------------------------------------- */
			
			animation.post.buttonAnimation(this, 
				applet.post.actionActiveImg.endorse, 
				applet.post.actionDeadImg.endorse, 
				applet.post.actionColor.endorse, 'E');
			
			dataHandler.ajaxHelper(applet.post.actionPhp, {postid : $(this).attr("data-pid"), action : 1});
		},
		repost : function () {
			
			/* -----------------------------------------------------------
			 * This function is responsable for the reposting of posts
			 * when the repost button is pressed
			 * ---------------------------------------------------------- */
			
			if($(this).attr('data-uid') != sysTrack.profileUserId) {
				animation.post.buttonAnimation(this, 
					applet.post.actionActiveImg.repost, 
					applet.post.actionDeadImg.repost, 
					applet.post.actionColor.repost, 'R');
				
				dataHandler.ajaxHelper(applet.post.actionPhp, {postid : $(this).attr("data-pid"), action : 2});
			}
		},
		comment : function (pid) {
			
			/* -----------------------------------------------------------
			 * This function collects the input entered into the comment section
			 * for posts and is then sent to the server and animated into
			 * the comment section for the posts
			 * ---------------------------------------------------------- */
			
			// Generate the selector for the comment section //
			var layer = animation.utils.layerGen(pid);
			
			if($(layer + " .js-comment-enter").val() && $(layer + " .js-comment-enter").val().length <= 180) {
				dataHandler.ajaxHelper("resources/php/handlers/entercomment.php", {postid : pid, written : $(layer + " .js-comment-enter").val()}, animation.post.comment.add);
				$(layer + " .js-comment-enter").val('');
			} else return false;
		},
		moreComments : function () {
			
			/* -----------------------------------------------------------
			 * Searches the js-comment-section of it's parent's child and 
			 * counts the nodes to retrieves more comments from the server
			 * ---------------------------------------------------------- */
			
			var lastId = $(this).closest(".js-comment-c").find(".js-comment-section > div").attr("data-id");
			var postId = $(this).closest("div[data-post-id]").attr("data-post-id");
			
			dataHandler.ajaxHelper("resources/php/handlers/loadcomments.php", { postid : postId, last : lastId }, animation.post.comment.show);
		},
		deleteComment : function () {
			
			/* -----------------------------------------------------------
			 * Finds the id of the comment and sends a request to the server
			 * to delete it and then removes the node from the comment container
			 * ---------------------------------------------------------- */
			
			var cid = $(this).attr("data-cid");
			
			$(this).closest(".js-comment-node").remove();
			dataHandler.ajaxHelper("resources/php/handlers/delcomment.php", { cid : cid });
		}
	}
};