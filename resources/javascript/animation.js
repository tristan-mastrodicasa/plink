//--------------------------------------------------

/* Following JS code provides all of the front end
 * processes which directly change the UI */
 
/* Layers are commonly associated with single characters
 * m: Main Feed
 * p: Profile Feed
 * pa: Analysis Feed
 * sb: Subscription box
 * l: Liked Posts 
 * s: Search Layer */

//--------------------------------------------------

////////////////////////////
// Main Animation Object
var animation = {
	globals : {
		insertionContainer : {sb : "div[data-focus-layer='2'] div[data-sub-focus-layer='3'] div.sub-insertion-container", m : "div[data-focus-layer='1'] div.post-insertion-container",
		p : "div[data-focus-layer='2'] div[data-sub-focus-layer='1'] div.post-insertion-container", pa : "div[data-focus-layer='2'] div[data-sub-focus-layer='2'] div.post-insertion-container",
		l : "div[data-focus-layer='2'] div[data-sub-focus-layer='4'] div.post-insertion-container", s : "div[data-focus-layer='3'] div.insertion-container"},
		loaders : {pa : "#pa-loader", p : "#profile-post-loader", sb : "#sub-loader", l : "#liked-loader", s : "#search-loader", pc : "#profile-loader", m : "#m-loader"},
		endBox : {pa : "#pa-end", p : "#profile-post-end", sb : "#sub-end", l : "#liked-end", s : "#search-end", m : "#m-end"}
	},
	error : {
		show : function (elem, errMessage = false) {
			
			/* -----------------------------------------------------------
			 * Function deals with border color changes and error message display
			 * ---------------------------------------------------------- */
			 
			$("#" + elem).css("border-color", "#FF0000");
			if(errMessage != false) {
				$("#" + elem + "-m").text(errMessage);
				$("#" + elem + "-m").css("display", "block");
			}
		},
		hide : function (elem) {
			
			/* -----------------------------------------------------------
			 * Change element border color and text to that of a correct input
			 * ---------------------------------------------------------- */
			
			$("#" + elem).css("border-color", "");
			$("#" + elem + "-m").css("display", "none");
			
			// For Loggining In //
			if(typeof(inputMethod) !== 'undefined' && inputMethod == 2) {
				$("#p").css("border-color", "#E6E6E6");
				$("#u").css("border-color", "#E6E6E6");
				$("#p-m").css("display", "none");
			}
		}
	},
	utils : {
		layerGen : function (pid) {
			
			/* -----------------------------------------------------------
			 * Creates the selector for the post UI (the post container)
			 * ---------------------------------------------------------- */
			 
			var layer = "div[data-focus-layer='" + sysTrack.currentLayer + "'] ";
			if(sysTrack.currentSubLayer != 0) layer += "div[data-sub-focus-layer='" + sysTrack.currentSubLayer + "'] ";
			layer += "div[data-post-id='" + pid + "']";
			
			return layer;
		},
		levelDisplay : function (level, size) {
			
			/* -----------------------------------------------------------
			 * This function compiles the background image and color appropiate
			 * to the level entered and size defined
			 * ----------------------------------------------------------*/
			
			var levelCount = 0,
			levelIncrements = 0;
			
			if(level < 100){
				while(1 == 1){
					levelIncrements += 10;
					levelCount++;
					
					if(level < levelIncrements){
						levelIncrements -= 10;
						var levelTop = levelIncrements + 9;
						
						return [DOMAIN + "resources/images/levels/" + SIZE_STRING[size] + "/level-" + levelIncrements + "-" + levelTop + ".png", LEVEL_COLOR[levelIncrements / 10]];
					}
				}
			}else{
				levelIncrements = 100;
				while(1 == 1){
					levelIncrements += 100;
					levelCount++;
					
					if(level < levelIncrements){
						levelIncrements -= 100;
						var levelTop = levelIncrements + 99;
						
						return [DOMAIN + "resources/images/levels/" + SIZE_STRING[size] + "/level-" + levelIncrements + "-" + levelTop + ".png", LEVEL_COLOR[(levelIncrements / 100)]];
					}
				}
			}
		},
		numberPrettify : function (num) {
			
			/* -----------------------------------------------------------
			 * Returns human readable strings form of a number 
			 * ----------------------------------------------------------*/
			 
			var prettyNum;
			if(num >= 1000 && num <= 1000000){
				prettyNum = Math.round((num / 1000) * 10) / 10;
				return prettyNum + 'k';
			}else if(num >= 1000000){
				prettyNum = Math.round((num / 1000000) * 10) / 10;
				return prettyNum + 'M';
			}
			
			return num;
		},
		textDynamify : function (str, more, postid, increment) {
			if(str) {
				///((https?|ftp)://(\S*?\.\S*?))([\s)\[\]{},;"\':<]|\.\s|$)/g;
				var writtenKeys = linkify.find(str);
				var existingUrls = [];
				var existingHash = [];
				var writtenKeyLen = writtenKeys.length;
				
				// Sanitize written text (compress url's linkify hashtags etc) //
				for(var p = 0; p < writtenKeyLen; p++) {
					if(writtenKeys[p].type == "url") {
						
						if(writtenKeys[p].value.length > 60){
							var urlText = writtenKeys[p].value.substring(0, 60) + "...";
							urlText = "<a class='t-post-content-link' href='" + writtenKeys[p].href + "' target='_blank'>" + urlText + "</a><br>";
						}else{
							var urlText = "<a class='t-post-content-link' href='" + writtenKeys[p].href + "' target='_blank'>" + writtenKeys[p].value + "</a>";
						}
						
						if(existingUrls.indexOf(writtenKeys[p].value) < 0){
							str = str.replace(new RegExp(dataHandler.escapeRegExp(writtenKeys[p].value), 'g'), urlText);
							existingUrls.push(writtenKeys[p].value);
						}
						
					}else if(writtenKeys[p].type == "hashtag"){
						
						if(existingHash.indexOf(writtenKeys[p].value) < 0){
							str = str.replace(new RegExp(dataHandler.escapeRegExp(writtenKeys[p].value) + '(?!\\S)', 'g'), 
							"<a href='#3:0' class='page-navigation t-post-content-link' data-callback='search' data-param='" + writtenKeys[p].value + "'>" + 
							writtenKeys[p].value + "</a>");
							existingHash.push(writtenKeys[p].value);
						}
					}
				}
				
				var seeMore = '';
				if(more){
					seeMore = 
						"<a class='t-see-more t-post-content-link' data-pid='" + 
						postid + 
						"' data-increment='" + increment + 
						"'>See More</a>";
				}
				
				return str + seeMore;
			}
		},
		toggleLoad : function (state, layer) {
			
			/* -----------------------------------------------------------
			 * Hides or shows the load or 'end of result' message for each 
			 * of the layers
			 * ----------------------------------------------------------*/
			
			if(state) {
				$(animation.globals.endBox[layer]).hide();
				$(animation.globals.loaders[layer]).show();
			} else {
				$(animation.globals.endBox[layer]).show();
				$(animation.globals.loaders[layer]).hide();
			}
		},
		popupMenu : function(evt, type, DOM = false) {
			
			// Clear the menu options //
			$("#popm-del").hide();
			$("#popm-edit").hide();
			$("#popm-rep").hide();
			
			// Construct the menu options //
			switch(type) {
				case 'm':
					if($(DOM).attr("data-uid") == sysTrack.profileUserId){
						sysTrack.pidToBeDel = $(DOM).attr("data-pid");
						sysTrack.pidToBeEdit = $(DOM).attr("data-pid");
						
						$("#popm-del").show();
						$("#popm-edit").show();
					} else {
						sysTrack.pidToBeRep = $(DOM).attr("data-pid");
						
						$("#popm-rep").show();
					}
					
					break;
			}
			
			$("#popup-menu").css({
				top: (evt.pageY - ($("#popup-menu").height() + 15)),
				left: (evt.pageX - 37.5)
			}).show();
		}
	},
	popup : { 
		notification : function (data) {
			
			/* -----------------------------------------------------------
			 * This function takes the response from notify.php and calls the 
			 * appropiate popup function
			 * ----------------------------------------------------------*/
			
			if(data.message == 'w') animation.popup.open('n', 0);
			else if (data.message == 'l') {
				sysTrack.refUsername = data.username;
				animation.popup.open('l', data.level);
			} else if (data.message == 'b') {
				window.location.replace(DOMAIN + "banned.php?id=" + sysTrack.profileUserId);
			}
			
		},
		open : function (subject, media) {
			
			/* -----------------------------------------------------------
			 * This function simply gives popup boxes the display:block css
			 * when called and closes when confirm or cancel is clicked
			 * ----------------------------------------------------------*/
			
			$("#popup-box").show();
			$("#pop-card-wrapper").show();
			
			switch(subject) {
				
				case 'l':
					// Get the level stuff and replace the appropiate text //
					var levelInfo = animation.utils.levelDisplay(media, 2);
					$("#level-up-number").text(media);
					$("#level-up-number").css("color", levelInfo[1]);
					$("#level-up-background").css("background-image", 'url("' + levelInfo[0] + '")');
					
					if(media == 5 || media == 10 || media == 15 || media == 25) {
						$("#pop-share-link-c").show();
						$("#pop-share-link").text(DOMAIN + "index.php?ref=" + sysTrack.refUsername);
						var message = "You have progressed to level " + media + "<br>Want to level up faster? Share this link to gain influence";
					} else {
						$("#pop-share-link-c").hide();
						var message = "You have progressed to level " + media;
					}
					
					$("#level-up-info").html(message);
					$("#level-up-box").toggle(400);
					$("#pop-buttons").show();
					$("#pop-confirm").bind("click", function(){ animation.popup.close("#level-up-box") });
					$("#pop-cancel").bind("click", function(){ animation.popup.close("#level-up-box") });
					break;
				
				case 'n':
					// Get message stuff and replace //
					$("#notice-box").toggle(400);
					$("#pop-buttons").show();
					$("#pop-confirm").bind("click", function(){ animation.popup.close("#notice-box") });
					$("#pop-cancel").bind("click", function(){ animation.popup.close("#notice-box") });
					break;
				
				case 'i':
					// Insert large image //
					$("#pop-card-wrapper").hide();
					$("#image-prev-popup").attr("src", "");
					$("#image-prev-popup").attr("src", media);
					$("#image-preview-box").show();
					$("#image-preview-box").css({
						"width" : ($(window).width() * 0.8), 
						"margin-left" : ($(window).width() * 0.1),
						"height" : ($(window).height() * 0.8),
						"margin-top" : ($(window).height() * 0.1)
						});
					$("#pop-buttons").hide();
					
					$("#popup-box").bind("click", function(){ animation.popup.close("#image-preview-box") });
					break;
				case 'd':
					// A confrimation to delete a post //
					$("#del-confirm-box").show();
					$("#pop-buttons").show();
					dataHandler.assignEventListener("click", "js-pop-confirm", function(){ animation.popup.close("#del-confirm-box"); animation.post.remove(sysTrack.pidToBeDel); });
					dataHandler.assignEventListener("click", "js-pop-cancel", function(){ animation.popup.close("#del-confirm-box") });
					break;
					
				case 'r':
					// A confrimation to report a post //
					$("#rep-confirm-box").show();
					$("#pop-buttons").show();
					dataHandler.assignEventListener("click", "js-pop-confirm", function(){ 
						animation.popup.close("#rep-confirm-box"); 
						dataHandler.ajaxHelper("resources/php/handlers/reportpost.php", {postid : sysTrack.pidToBeRep}); 
					});
					dataHandler.assignEventListener("click", "js-pop-cancel", function(){ animation.popup.close("#rep-confirm-box") });
					break;
			}
		},
		close : function (untoggle) {
			$(untoggle).hide();
			$("#popup-box").hide();
			$("#popup-box").unbind("click");
		}
	},
	pageNav : {
		changeLayer : function (focusLayer, subLayer) {
			
			/* -----------------------------------------------------------
			 * Previous Layers are given display:none values (so they disappear)
			 * New Layers are given display:block values
			 * ---------------------------------------------------------- */
			
			// Checks the current layer (active page) is not the same as the link pressed //
			// Otherwise no change in page will commence and only sublayer //
			if(sysTrack.currentLayer != focusLayer){
				if(!sysTrack.mobile) $("div[data-focus-layer = '" + sysTrack.currentLayer + "']").fadeOut(200);
				else $("div[data-focus-layer = '" + sysTrack.currentLayer + "']").hide();
			}
			
			animation.pageNav.changeSublayer(focusLayer, subLayer);
			
			if(sysTrack.currentLayer != focusLayer){
				if(!sysTrack.mobile) $("div[data-focus-layer = '" + focusLayer + "']").fadeIn(200);
				else $("div[data-focus-layer = '" + focusLayer + "']").show();
			}
			
			if(focusLayer != 1) window.scrollTo(0,0);
			else $(window).scrollTop(sysTrack.mainScrollPosition);
			
			
			// Update Global Variables with new Focus Layers //
			sysTrack.currentLayer = focusLayer;
			sysTrack.currentSubLayer = subLayer;
		},
		changeSublayer : function (focusLayer, subLayer) {
			
			/* -----------------------------------------------------------
			 * Previous sub-Layers are given display:none values (so they disappear)
			 * New sub-Layers are given display:block values
			 * ---------------------------------------------------------- */
			 
			// If the current active layer doesn't have a sub layer to hide then continue //
			if(sysTrack.currentSubLayer != 0) $("div[data-focus-layer = '" + sysTrack.currentLayer + "'] div[data-sub-focus-layer = '" + sysTrack.currentSubLayer + "']").css("display", "none");
			
			// If the new active layer doesn't have a sub layer to show then continue //
			if(subLayer != 0) $("div[data-focus-layer = '" + focusLayer + "'] div[data-sub-focus-layer = '" + subLayer + "']").css("display", "block");
		}
	},
	portal : {
		countdown : {
			
			/* -----------------------------------------------------------
			 * Thanks to https://www.sitepoint.com/build-javascript-countdown-timer-no-dependencies/
			 * for the following code
			 * ---------------------------------------------------------- */
			 
			getTimeRemaining : function (endtime) {
			var t = Date.parse(endtime) - Date.parse(new Date());
			
			var seconds = Math.floor((t / 1000) % 60);
			var minutes = Math.floor((t / 1000 / 60) % 60);
			var hours = Math.floor((t / (1000 * 60 * 60)) % 24);
			var days = Math.floor(t / (1000 * 60 * 60 * 24));
			return {
				'total': t,
				'days': days,
				'hours': hours,
				'minutes': minutes,
				'seconds': seconds
			  };
			},
			initializeClock : function (id, endtime) {
				var clock = document.getElementById(id);
				var daysSpan = clock.querySelector('.days');
				var hoursSpan = clock.querySelector('.hours');
				var minutesSpan = clock.querySelector('.minutes');
				var secondsSpan = clock.querySelector('.seconds');
			
				function updateClock() {
					var t = animation.portal.countdown.getTimeRemaining(endtime);
					
					if (t.total <= 0) {
						clearInterval(timeinterval);
						
						if(t.total <= 0 && t.total > -100) window.location.replace(DOMAIN);
					}
					
					daysSpan.innerHTML = t.days;
					hoursSpan.innerHTML = ('0' + t.hours).slice(-2);
					minutesSpan.innerHTML = ('0' + t.minutes).slice(-2);
					secondsSpan.innerHTML = ('0' + t.seconds).slice(-2);
					
				}
				
				updateClock();
				var timeinterval = setInterval(updateClock, 1000);
			}
			
		},
		currentStep : function (con) {
			
			/* -----------------------------------------------------------
			 * Changes interface according the signup phase
			 * ---------------------------------------------------------- */
			 
			if(con == 1){
				$("#element-set-1").hide();
				$("#li").css("display", "none");
				$("#su").attr("value", "Agree");
				$("#element-set-2").show();
				$("#ti").hide();
			}else if(con == 2){
				$("#element-set-1").css("display", "none");
				$("#element-set-2").css("display", "none");
				$("#element-set-4").css("display", "none");
				$("#li").css("display", "none");
				$("#element-set-3").css("display", "block");
			}
		},
		toggle : function () {
			
			/* -----------------------------------------------------------
			 * Change between Login and Signup Input
			 * ---------------------------------------------------------- */
			
			animation.error.hide("f");
			animation.error.hide("u");
			animation.error.hide("se");
			animation.error.hide("p");
				
			if(inputMethod == 1) {
				inputMethod = 2;
				$("input[name='inputMethod']").attr("value", 2);
				
				$("#f-c").toggle(400);
				$("#se-c").toggle(400);
				$("#ti").text("Sign Up");
				$("#ti").attr("value", "Login");
			} else {
				inputMethod = 1;
				$("input[name='inputMethod']").attr("value", 1);
				
				$("#f").trigger("onblur");
				$("#u").trigger("onblur");
				$("#se").trigger("onblur");
				$("#p").trigger("onblur");
				$("#f-c").toggle(400);
				$("#se-c").toggle(400);
				$("#ti").text("Login");
				$("#ti").attr("value", "Sign Up");
			}
		},
		responseHandler : function (data) {
			
			/* -----------------------------------------------------------
			 * This function expects 
			 * InputMethod = 1, 2
			 * Step = 0, 1, 2
			 * Error messages under f, u, se, p
			 * ---------------------------------------------------------- */
			
			if(data.inputMethod == 1) {
				var nextStep = true;
				
				for(var key in data.form) {
					if(data.form.hasOwnProperty(key)) {
						if(data.form[key] != 0) {
							animation.error.show(key, data.form[key]);
							nextStep = false;
						}
					}
				}
				
				if(nextStep == true) {
					animation.portal.currentStep(data.step);
					$("input[name='step']").attr("value", data.step);
				}
				else animation.portal.currentStep(0);
			} else {
				if(data.p == 'b') {
					window.location.replace(DOMAIN + "banned.php?id=" + data.uid);
				} else if(data.p != 0) {
					animation.error.show('p', data.p);
					animation.error.show('u');
				} else window.location.replace(DOMAIN + "home/");
			}
		},
		passRecovery : {
			message : function () {
				$("#ch").hide();
				$("#username").hide();
				$("#pass-recov-message").show();
			},
			responseHandler : function (data) {
				
				if(data.p == 0) {
					window.location.replace(DOMAIN + "home/");
				} else {
					animation.error.show("pass-new", data.p);
					animation.error.show("pass-retype");
				}
			}
		}
	},
	publish : {
		selectors : { 
			
		},
		setSelectors : function () {
			this.selectors = {
				selTriBox : $("#selector-triangles"),
				mainPubUi : $("#post-publish-ui"),
				pubSubBar : $("#submit-container"),
				pubTxtInt : $("#textbox"),
				pubVidoUi : $("#video-input-c"),
				pubImagUi : $("#image-input"),
				pubSelTop : $("#publish-topic-chooser"),
				pubSelBlb : $("#publish-topic-chooser-blurb"),
				errorAlrt : $("#error-message"),
				pubUplTyp : $("#upload-type"),
				charCount : $("#blurb-character-counter")
			}
		},
		article : function (state) {
			if (state) this.selectors.pubTxtInt.attr("placeholder", "What did you want to say ...");
			
		},
		image : function (state) {
			if (state) { 
				this.selectors.pubTxtInt.attr("placeholder", "Say something about this photo ...");
				this.selectors.pubImagUi.show();
			} else this.selectors.pubImagUi.hide();
		},
		video : function (state) {
			if (state) { 
				this.selectors.pubTxtInt.attr("placeholder", "Say something about this video ...");
				this.selectors.pubVidoUi.show();
			} else this.selectors.pubVidoUi.hide();
		},
		blurb : function (state) {
			if (state) {
				this.selectors.pubTxtInt.attr("placeholder", "Write something to your subscribers ...");
				this.selectors.pubTxtInt.on("keyup", animation.publish.blurbCounter);
				this.selectors.pubTxtInt.trigger("keyup");
				this.selectors.pubSelTop.hide();
				this.selectors.pubSelBlb.show();
				this.selectors.charCount.show();
			} else {
				this.selectors.pubTxtInt.off("keyup", animation.publish.blurbCounter);
				this.selectors.pubSelTop.show();
				this.selectors.pubSelBlb.hide();
				this.selectors.charCount.hide();
			}
		},
		optionIndex : [, "article", "image", "video", "blurb"],
		optionButton : function () {
			
			/* -----------------------------------------------------------
			 * This function handles the selecting animation for publishing
			 * posts
			 * ---------------------------------------------------------- */
			
			var selected = parseInt($(this).attr("data-pub-op"));
			
			var curTriLoc = $("span[data-pub-tri ='" + sysTrack.currentPubOp + "']"),
				curPubInt = $("div[data-pub-layer ='" + sysTrack.currentPubOp + "']"),
				newTriLoc = $("span[data-pub-tri ='" + selected + "']"),
				newPubInt = $("div[data-pub-layer ='" + selected + "']");
			
			// If the post writer has not been deployed //
			if(!sysTrack.currentPubOp){
				animation.publish.setSelectors();
				animation.publish.selectors.selTriBox.show();
				animation.publish.selectors.mainPubUi.show();
			} else curTriLoc.css("background-image", "");
			
			newTriLoc.css("background-image", "url('" + DOMAIN + "resources/images/post-options/selector-triangle.png')");
			newPubInt.show();
			
			if(sysTrack.currentPubOp) animation.publish[animation.publish.optionIndex[sysTrack.currentPubOp]] (false);
			animation.publish[animation.publish.optionIndex[selected]] (true);
			
			// Updating the system tracker //
			sysTrack.currentPubOp = selected;
			
			// Updating upload type //
			animation.publish.selectors.pubUplTyp.attr("value", selected);
			
			// Probe the elastic text area function (on update) //
			animation.publish.selectors.pubTxtInt.trigger("update");
			
			// Hiding Error message //
			animation.publish.hideError();
		},
		blurbCounter : function () {
			
			/* -----------------------------------------------------------
			 * This function is a proccess to monitor the blurb 
			 * publishing so that the user is reminded on how many characters
			 * they have left
			 * ---------------------------------------------------------- */
			
			var charCount = 150 - ($("#textbox").val().length);
							
			if(charCount < 0){
				$("#blurb-character-counter").css("color", "#FF0000");
				sysTrack.blurbCharWatch = 1;
			}
			else if(sysTrack.blurbCharWatch == 1) $("#blurb-character-counter").css("color", "#AAB8C2");
			
			$("#blurb-character-counter").text(charCount);
		},
		responseHandler : function (data) {
			
			/* -----------------------------------------------------------
			 * Called when a user publishes a post and a reponse is waiting
			 * to be sent back
			 * ---------------------------------------------------------- */
			
			$("#publish-loading-sign").hide();
			if(!data) animation.publish.showError(ERROR_MESSAGE[6]);
			
			if(data.errorMsg) animation.publish.showError(data.errorMsg);
			else {
				animation.post.compiler([data.postJson]);
				
				// Reset the input //
				$("#image-src").val('');
				$("#textbox").val('');
				$("#image-url-input").val('');
				$("#video-input").val('');
				$("#publish-topic-chooser").get(0).selectedIndex = 'n';
			}
		},
		showError : function (errMsg) {
			
			/* -----------------------------------------------------------
			 * This function handles the error messaging for publishing posts
			 * if an error code of 0 is passed then the error message is cleared
			 * ---------------------------------------------------------- */
			
			sysTrack.pubErrorStatus = 1;
			$("#submit-container").css("border-top-color", "#FF0000");
			$("#error-message").show();
			
			if(errMsg % 1 === 0) $("#error-message-text").text(ERROR_MESSAGE[errMsg]);
			else $("#error-message-text").text(errMsg);
		},
		hideError : function () { 
			
			/* -----------------------------------------------------------
			 * Hides any error messages in the main publish UI
			 * ---------------------------------------------------------- */
			
			if(sysTrack.pubErrorStatus == 1){
				$("#error-message").hide();
				$("#submit-container").css("border-top-color", "#AAB8C2");
				sysTrack.pubErrorStatus = 0;
			}
		}
	},
	profile : { 
		update : function (data) {
			
			/* -----------------------------------------------------------
			 * Retrieves the server information and updates the profile layer
			 * ---------------------------------------------------------- */
			
			dataHandler.clearNodes(animation.globals.insertionContainer['p']);
			
			$("#profile-layer-picture").css("background-image", "url('" + data.profilePicture + "')");
			$("#profile-layer-name").text(data.profileName);
			$(".profile-links").attr("data-param", data.profileId);
			
			var levelInfo = animation.utils.levelDisplay(data.profileLevel, 1);
			
			if(!data.profileOwner){
				$("#profile-layer-public-buttons").show();
				$("#profile-layer-public-buttons").attr("data-uid", data.profileId);
				$("#profile-layer-private-buttons").hide();
				
				if(data.isSubscribed) animation.profile.subscribe(false);
				else animation.profile.subscribe(true);
				
				if(data.isAdmired) animation.profile.admire(false);
				else animation.profile.admire(true);
				
			}else{
				$("#profile-layer-public-buttons").hide();
				$("#profile-layer-private-buttons").show();
			}
			
			$("#profile-layer-level").css("color", levelInfo[1]);
			$("#profile-layer-level").text(data.profileLevel);
			$("#profile-layer-level-back").css("background-image", 'url("' + levelInfo[0] + '")');
			$("#profile-layer-xp").text("Currrent Influence: " + data.profileXp);
			$("#profile-layer-xp-tg").text("To Next Level: " + data.profileXpToNext);
			
			$("#user-join-date").html("<b>Joined:</b> " + data.joined);
			$("#user-login-date").html("<b>Last Active:</b> " + data.lastActive);
			
			var topicMajority;
			if(data.topic != 'n' && data.topic != 's' && data.topic != 0) topicMajority = TOPIC[data.topic];
			else topicMajority = "None";
			
			$("#user-topic-majority").html("<b>Topic:</b> " + topicMajority);
			
			$("#profile-public-stat-sub").text(animation.utils.numberPrettify(data.subscribers));
			$("#profile-public-stat-adm").text(animation.utils.numberPrettify(data.admirations));
			$("#profile-public-stat-end").text(animation.utils.numberPrettify(data.endorsements));
			$("#profile-public-stat-pst").text(animation.utils.numberPrettify(data.posts));
			
			sysTrack.currentLoadedUser = data.profileId;
			
			// Hide profile card and show loading sign //
			$("#profile-card").show();
			$(animation.globals.loaders.pc).hide();
			
			dataHandler.scrollEngine('p', true);
		},
		updatePropicIcon : function (data) {
			$("#top-nav-propic").attr("src", data.tinyPic);
			sysTrack.profileUserPic = data.tinyPic;
		},
		analysis : function (data) {
			
			/* -----------------------------------------------------------
			 * Retrieves the server information and updates profile analysis
			 * ---------------------------------------------------------- */
			
			$("#pa-prof-seen").text(data.profileSeen);
			$("#pa-prof-sub").text(data.subscribers);
			$("#pa-prof-adm").text(data.admirations);
			$("#pa-post-end").text(data.endorsements);
			$("#pa-prof-posts").text(data.posts);
			$("#pa-post-rep").text(data.reposts);
			$("#pa-post-com").text(data.comments);
			$("#pa-post-seen").text(data.postSeen);
			
			sysTrack.scrollLoad = false;
			
			dataHandler.scrollEngine('pa', true);
			
			sysTrack.scrollLoad = true;
		},
		hoverFix : function (DOM, state) {
			
			/* -----------------------------------------------------------
			 * On mobile the CSS :hover sucks (doesn't work) so this code 
			 * manually changes the styles (must run if on mobile)
			 * ---------------------------------------------------------- */
			 
			if(!state){
				$(DOM + " div").attr("style", "background-image: url('" + DOMAIN + "resources/images/buttons/ticked.png')");
				$(DOM).attr("style", "color: #FFFFFF; background-color: #FF751A;");
			} else {
				$(DOM).attr("style", "color: #FF751A; background-color: #FFFFFF;");
			}
		},
		subscribe : function (state) {
			
			/* -----------------------------------------------------------
			 * Updates the subscribe button based on it's current state (the opposite)
			 * ---------------------------------------------------------- */
			 
			if(!state){
				$("#prof-subscribe").html("Subscribed<div class='ic-ticked'></div>");
				$("#prof-subscribe").addClass("b-profile-action");
				$("#prof-subscribe").attr("data-com", 1);
			} else {
				$("#prof-subscribe").text("Subscribe");
				$("#prof-subscribe").removeClass("b-profile-action");
				$("#prof-subscribe").attr("data-com", 0);
			}
			
			if(sysTrack.mobile == 1) this.hoverFix("#prof-subscribe", state);
		},
		admire : function (state) {
			
			/* -----------------------------------------------------------
			 * Updates the admire button based on it's current state (the opposite)
			 * ---------------------------------------------------------- */
			 
			if(!state){
				$("#prof-admire").html("Admired<div class='ic-ticked'></div>");
				$("#prof-admire").addClass("b-profile-action");
				$("#prof-admire").attr("data-com", 1);
			} else {
				$("#prof-admire").text("Admire");
				$("#prof-admire").removeClass("b-profile-action");
				$("#prof-admire").attr("data-com", 0);
			}
			
			if(sysTrack.mobile == 1) this.hoverFix("#prof-admire", state);
		},
		settings : {
			toggleSaved : function (state) {
				if(state) {
					$("div.ly-set-button").css("width", "159px");
					$("#setting-saved-sign").show();
					$("#ss").attr("value", "Saved");
				} else {
					$("#setting-saved-sign").hide();
					$("div.ly-set-button").css("width", "119px");
					$("#ss").attr("value", "Save Changes");
				}
			},
			toggleInfo : function (DOMID, state) {
				if(state) $("#" + DOMID + "-i").show();
				else $("#" + DOMID + "-i").hide();
			},
			update : function (data) {
				
				/* -----------------------------------------------------------
				 * Handles success or failure operations
				 * ---------------------------------------------------------- */
				
				var success = true;
				
				for(var key in data) {
					if(data.hasOwnProperty(key)) {
						if(data[key] != 0) {
							animation.error.show(key, data[key]);
							success = false;
						}
					}
				}
				
				if(success) {
					$("#p").attr("value", '');
					$("#p2").attr("value", '');
					$("#po").attr("value", '');
					
					// Show Success //
					animation.profile.settings.toggleSaved(true);
					setTimeout(function(){animation.profile.settings.toggleSaved(false);},2500);
					
					dataHandler.ajaxHelper("resources/php/handlers/profile.php", {id : sysTrack.profileUserId}, animation.profile.update);
					$("#propic-confirm").hide();
					$("#propic-overlay").show();
					$("#profpic-select").show();
				}
			},
			profilePicture : function (data) {
				
				// Hide Loading //
				$("#profpic-load").hide();
				
				if(data.error != 0){
					alert("Image is invalid, either too small, too big or not an image");
					$("#profpic-select").show();
				} else {
					$("#propic-overlay").hide();
					$("#profile-layer-picture").css("background-image", "url('" + data.tempImagePath + "')");
					$("#propic-confirm").show();
				}
			}
		}
	},
	post : {
		actionActiveState : [
			DOMAIN + "resources/images/buttons/endorsed.png",
			DOMAIN + "resources/images/buttons/reposted.png",
			DOMAIN + "resources/images/buttons/commented.png"
		],
		actionActiveColor : [
			"#00B034",
			"#00A8EC",
			"#919191"
		],
		classId : [
			"post-E",
			"post-R",
			"post-C",
			"post-M"
		],
		populate : function (response) {
			
			/* -----------------------------------------------------------
			 * Responsable for the population of posts and subscriptions for
			 * the different layers
			 * ----------------------------------------------------------*/
			
			var more = true;
			
			if(response.posts == null) var arrayLength = 0;
			else var arrayLength = response.posts.length;
			
			if(response.meta.method == 1) { 
				dataHandler.clearNodes(animation.globals.insertionContainer[response.meta.layer]);
				animation.utils.toggleLoad(true, response.meta.layer);
			}
			
			if(response.meta.layer == 's') {
				if(response.meta.type == 'h') sysTrack.firstHashId = response.meta.hashtagFirstId;
				if(response.meta.type == 'h' && arrayLength != 3) more = false;
				else if(response.meta.type == 'u' && arrayLength != 11) more = false;
			}
			
			if((response.meta.layer == 'l' || response.meta.layer == 'p' || response.meta.layer == 'pa' || response.meta.layer == 'm') && arrayLength != 3) more = false;
			else if(response.meta.layer == 'sb' && arrayLength != 11) more = false;
			
			if(response.meta.layer != 'sb' && response.meta.type != 'u') animation.post.compiler(response.posts);
			else animation.post.subCompiler(response.posts, response.meta.layer);
			
			// Load posts until they run off the page //
			if(more == true) dataHandler.populateTillScroll();
			
			// Show or hide loader //
			animation.utils.toggleLoad(more, response.meta.layer);
		},
		compiler : function (information) {
			
			/* -----------------------------------------------------------
			 * This function handles the task of populating posts in the
			 * three different layers. 
			 * Method refers to wether or not to
			 * refresh and delete all the posts in a section (new profile)
			 * or append or prepend more posts (scrolling down in the home page)
			 * Information is the array of objects to convert to posts
			 * Refer to //postcompiler.class.php// for object structure
			 * ---------------------------------------------------------- */
			 
			if (information != null) var arrayLength = information.length;
			else var arrayLength = -1;
			
			for(var i = 0; i < arrayLength; i++) {
				
				var layer = animation.utils.layerGen(information[i].postMeta.postId);
				
				if(!$(layer).length) {
					
					var postHtml = 
						html.post.head[0] + information[i].postMeta.postId + 
						html.post.head[1] + information[i].postMeta.userId + 
						html.post.head[2] + information[i].postHead.userPicture + 
						html.post.head[3] + information[i].postMeta.userId + 
						html.post.head[4] + information[i].postHead.userName + 
						html.post.head[5] + information[i].postHead.userUsername + 
						html.post.head[6];
						
					if(information[i].postHead.postType == 2 && information[i].postHead.postTopic == 's') postHtml += "Changed Profile Picture";
					else postHtml += TOPIC[information[i].postHead.postTopic];
						
					if(information[i].postHead.timePosted == false) postHtml += html.post.head[7] + "Just Now";
					else postHtml += html.post.head[7] + information[i].postHead.timePosted;
					
					if(information[i].postHead.reposterName){
						postHtml +=
							html.post.head[8] + information[i].postHead.reposterId + 
							html.post.head[9] + information[i].postHead.reposterName + 
							html.post.head[10];
					}
					
					postHtml += html.post.head[11];
						
					// Add the written text //
					var cleanString = animation.utils.textDynamify(information[i].postContent.written, information[i].postContent.writtenPrev, information[i].postMeta.postId, 1);
					
					if(cleanString) postHtml += html.post.body.node[0] + html.post.body.written[0] + cleanString + html.post.body.written[1] + html.post.body.node[1];
					
					// Add image //
					if(information[i].postContent.imageUrl) {
						
						var largeI = DOMAIN + "user/images/large/" + information[i].postContent.imageUrl;
						var smallI = DOMAIN + "user/images/medium/" + information[i].postContent.imageUrl;
						
							
						if(!sysTrack.mobile) {
							postHtml +=
								html.post.body.image.desktop[0] + smallI + 
								html.post.body.image.desktop[1] + largeI + 
								html.post.body.image.desktop[2];
						} else {
							postHtml +=
								html.post.body.image.mobile[0] + smallI + 
								html.post.body.image.mobile[1];
						}
					}
					
					// Add Opengraph Preview //
					if(information[i].openGraph) {
						postHtml += html.post.body.node[0] + html.post.body.openGraph.link[0] + information[i].openGraph.url + html.post.body.openGraph.link[1];
						
						if(information[i].postHead.postType == 1 && information[i].openGraph.mediaSize != null) {
							dataHandler.verify.imageUrl(information[i].openGraph.media, dataHandler.qualityControl, 2000);
						}
						
						if(information[i].postHead.postType == 3 && information[i].openGraph.mediaSize == 1) {
							postHtml += html.post.body.youtube[0] + information[i].openGraph.media + "?rel=0&showinfo=0&autohide=2&iv_load_policy=3" + html.post.body.youtube[1];
						}else if(information[i].openGraph.mediaSize == 1 || (sysTrack.mobile == 1 && information[i].openGraph.mediaSize == 0) || information[i].postHead.postType == 3){
							postHtml += html.post.body.openGraph.largeImg[0] + information[i].openGraph.media + html.post.body.openGraph.largeImg[1];
						}else if(information[i].openGraph.mediaSize == 0){
							postHtml += html.post.body.openGraph.smallImg[0] + information[i].openGraph.media + html.post.body.openGraph.smallImg[1];
						}
						
						if(information[i].openGraph.mediaSize == 1 || information[i].openGraph.mediaSize == null || (sysTrack.mobile == 1 && information[i].openGraph.mediaSize == 0)) {
							postHtml += html.post.body.openGraph.largeTitle[0] + information[i].openGraph.title + html.post.body.openGraph.largeTitle[1];
						} else {
							postHtml += html.post.body.openGraph.smallTitle[0] + information[i].openGraph.title + html.post.body.openGraph.smallTitle[1];
						}
							
						if(information[i].openGraph.description){
							postHtml += html.post.body.openGraph.description[0] + information[i].openGraph.description + html.post.body.openGraph.description[1];
						}
						
						postHtml += html.post.body.openGraph.link[2] + html.post.body.openGraph.end;
					}
					
					// Add the buttons //
					var actions = { 
						// Always goes Endorse, Repost, Comment
						commited : [0, 0, 0], // Whether the user has allready committed an action
						displayedNum : ['', '', ''], // The prettyfied number
						imageState : [ // The color of the icon depending on whether the user has committed an action
							DOMAIN + "resources/images/buttons/endorse.png",
							DOMAIN + "resources/images/buttons/repost.png",
							DOMAIN + "resources/images/buttons/comment.png"
						],
						colorState : [
							"#AAB8C2",
							"#AAB8C2",
							"#AAB8C2"
						],
						retrievedInfo : {
							committed : [
								information[i].postActions.userEnd,
								information[i].postActions.userRep,
								information[i].postActions.userCom
							],
							currentActionPop : [
								information[i].postActions.endNum,
								information[i].postActions.repNum,
								information[i].postActions.comNum
							]
						}
					}
					
					postHtml += html.post.buttons.container[0];
					
					// Standard post buttons //
					if(!information[i].postActions.seenNum){
						
						for (var x = 0; x <= 2; x++) {
							if(actions.retrievedInfo.committed[x]) { 
								actions.commited[x] = 1;
								actions.imageState[x] = animation.post.actionActiveState[x];
								actions.colorState[x] = animation.post.actionActiveColor[x];
							}
							
							if(actions.retrievedInfo.currentActionPop[x] > 0) actions.displayedNum[x] = animation.utils.numberPrettify(actions.retrievedInfo.currentActionPop[x]);
							
							postHtml += 
								html.post.buttons.induvidual[0] + animation.post.classId[x] + 
								html.post.buttons.induvidual[1] + actions.commited[x] + 
								html.post.buttons.induvidual[2] + information[i].postMeta.postId;
								
							if(x == 1) postHtml +=  html.post.buttons.induvidual[8] + information[i].postMeta.userId;
							
							postHtml += 
								html.post.buttons.induvidual[3] + actions.imageState[x] + 
								html.post.buttons.induvidual[4] + actions.colorState[x] + 
								html.post.buttons.induvidual[5] + actions.retrievedInfo.currentActionPop[x] + 
								html.post.buttons.induvidual[6] + actions.displayedNum[x] + 
								html.post.buttons.induvidual[7];
							
							// Update the numbers for other loaded posts with the same postid //
							var selector = "button[data-pid='" + information[i].postMeta.postId + "']." + animation.post.classId[x] + " span";
							$(selector).attr("data-user-count", actions.retrievedInfo.currentActionPop[x]);
							if(actions.retrievedInfo.currentActionPop[x] != 0) $(selector).text(animation.utils.numberPrettify(actions.retrievedInfo.currentActionPop[x]));
							else $(selector).text('');
						}
					} else {
						actions.retrievedInfo.currentActionPop.unshift(information[i].postActions.seenNum);
						
						for (var x = 0; x <= 3; x++) {
							postHtml +=
								html.post.buttons.postAnalysis[4] + html.post.buttons.postAnalysis[x] + 
								html.post.buttons.postAnalysis[5] + actions.retrievedInfo.currentActionPop[x] + 
								html.post.buttons.postAnalysis[6];
						}
					}
					
					// Add the 'more' button //
					if(!information[i].postActions.seenNum) {
						postHtml +=
							html.post.buttons.induvidual[0] + animation.post.classId[3] + 
							html.post.buttons.induvidual[2] + information[i].postMeta.postId + 
							html.post.buttons.induvidual[8] + information[i].postMeta.userId + 
							"'><div class='ic-b-more'></div></button></div>";
					}
					
					postHtml += html.post.buttons.container[1];
					
					// Define which layer the posts are accessing //
					var layer = animation.globals.insertionContainer[information[i].postMeta.layer];
					
					// Into the post insertion layers //
					switch(information[i].postMeta.method){
						
						case 1: 
							$(layer).prepend(postHtml);
							break;
						case 2: 
							$(layer).append(postHtml);
							break;
						
					}
					
					if(information[i].postMeta.posted) {
						$("div[data-post-id = '" + information[i].postMeta.postId).toggle(100);
						
						// Close the input //
						$("#selector-triangles").toggle(100);
						$("#post-publish-ui").toggle(100);
						$("span[data-pub-tri ='" + sysTrack.currentPubOp + "']").css("background-image", "");
						
						animation.publish[animation.publish.optionIndex[sysTrack.currentPubOp]] (false);
						sysTrack.currentPubOp = 0;
					} else $("div[data-post-id = '" + information[i].postMeta.postId).show();
					
					// Assign the button functions //
					if(information[i].postContent.writtenPrev) dataHandler.assignEventListener("click", "t-see-more", dataHandler.post.seeMoreText);
					
				}
			}
			
			dataHandler.assignEventListener("click", "page-navigation", applet.linkEngine.callback);
			dataHandler.assignEventListener("click", "post-E", applet.post.endorse);
			dataHandler.assignEventListener("click", "post-R", applet.post.repost);
			dataHandler.assignEventListener("click", "post-C", function(){ dataHandler.ajaxHelper("resources/php/handlers/loadcomments.php", {postid : $(this).attr("data-pid"), last : 0 }, animation.post.comment.show) });
			dataHandler.assignEventListener("click", "post-M", function(evt) { animation.utils.popupMenu(evt, 'm', this); });
			dataHandler.assignEventListener("blur", "post-M", function(){ $("#popup-menu").hide(); });
		},
		subCompiler : function (information, layer) {
			
			var arrayLength = information.length;
			
			for(var i = 0; i < arrayLength; i++) {
				
				var subHtml = 
					html.profile [0] + information[i].userid + 
					html.profile [1];
				
				if(layer == 'sb') {
					// To unsubscribe //
					subHtml += 
						html.profile [2] + information[i].userid + 
						html.profile [3];
				}
				
				subHtml += 
					html.profile [4] + information[i].userid + 
					html.profile [5] + information[i].profilePicture + 
					html.profile [6] + information[i].name + 
					html.profile [7] + '@' + information[i].username +
					html.profile [8];
				
				$(animation.globals.insertionContainer[layer]).append(subHtml);
			}
			
			dataHandler.assignEventListener("click", "page-navigation", applet.linkEngine.callback);
		},
		buttonAnimation : function (obj, actionActive, actionDead, color, action) {
			
			/* -----------------------------------------------------------
			 * This function handles the animations for the post buttons
			 * NOTE: I will need to check how I am going to store data in 
			 * DOM elements (like posts) once loaded from the server
			 * 
			 * Also I should look for a CSS solution to speed up action 'times'
			 * ---------------------------------------------------------- */
			
			// Retrieves the post id and commitment status //
			var pid = parseInt($(obj).attr("data-pid"));
			var com = parseInt($(obj).attr("data-com"));
			
			// Constructing the image and number DOM selectors //
			var imgDOM = "button.post-" + action + "[data-pid ='" + pid + "'] div";
			var numDOM = "button.post-" + action + "[data-pid ='" + pid + "'] span";
			
			var userCount = parseInt($(numDOM).attr("data-user-count"));
			
			if(com == 0){
				$(imgDOM).css("background-image", "url('" + DOMAIN + "resources/images/buttons/" + actionActive + "')");
				$(numDOM).css("color", color);
				
				$("button.post-" + action + "[data-pid ='" + pid + "']").attr("data-com", "1");
				
				$(numDOM).attr("data-user-count", userCount + 1);
				
				if(!userCount) $(numDOM).text(1);
				else $(numDOM).text(animation.utils.numberPrettify(userCount + 1));
				
			}else if(com == 1){
				$(imgDOM).css("background-image", "url('" + DOMAIN + "resources/images/buttons/" + actionDead + "')");
				$(numDOM).css("color", "#AAB8C2");
				
				$("button.post-" + action + "[data-pid ='" + pid + "']").attr("data-com", "0");
				
				$(numDOM).attr("data-user-count", userCount - 1);
				
				if((userCount - 1) == 0) $(numDOM).text('');
				else $(numDOM).text(animation.utils.numberPrettify(userCount - 1));
				
			}
		},
		updateText : function (data) { 
			
			/* -----------------------------------------------------------
			 * Updates the post text after clicking 'see more'
			 * ---------------------------------------------------------- */
			
			$("div.width-full-mb-20[data-post-id='" + data.postid + "']").find("p.t-post-content-text").html(animation.utils.textDynamify(data.written, data.more, data.postid, data.increment));
			dataHandler.assignEventListener("click", "t-see-more", dataHandler.post.seeMoreText);
		},
		comment : {
			show : function (data) {
				
				/* -----------------------------------------------------------
				 * This function handles the task of showing comments after the 
				 * user presses the 'comment' button on posts. It will close if the
				 * server returns a postid and layer which the post is already 
				 * showing comments on. Otherwise it will open the comment UI with
				 * some preview comments
				 * ---------------------------------------------------------- */
				
				// Update the active comment layer //
				var layer = animation.utils.layerGen(data.pid);
				
				// Generate HTML //
				if($(layer + " div.js-comment-c").length > 0 && data.buttonClicked == true) {
					$(layer + " div.js-comment-c").remove();
				} else if(data.buttonClicked == true) {
					var commentHtml = html.post.comments.container[0];
					
					// See more comments //
					if(data.allComments == false) {
						commentHtml += 
							html.post.comments.more[0] + html.post.comments.more[1];
					}
					
					// Comment Section //
					commentHtml += html.post.comments.section;
					
					// User Comment Enter //
					commentHtml += 
						html.post.comments.enter[0] + sysTrack.profileUserPic + 
						html.post.comments.enter[1] + data.pid + 
						html.post.comments.enter[2];
					
					commentHtml += html.post.comments.container[1];
					
					$(layer).append(commentHtml);
					
				}
				
				// Populate comments //
				animation.post.comment.add(data, 0, false);
				
				// Update Fraction //
				var loaded = $(layer + " .js-comment-section > div").length;
				$(layer + " .js-comment-fraction").text(loaded + " out of " + (loaded + data.fraction));
				
			},
			add : function (data, pid = 0, append = true) {
				
				/* -----------------------------------------------------------
				 * This function prepends more comments to a posts comment section
				 * ---------------------------------------------------------- */
				
				if(pid == 0) pid = data.pid;
				
				// Generate the selector for the comment section //
				var layer = animation.utils.layerGen(pid);
				
				if (data.comments != null) var arrayLength = data.comments.length;
				else var arrayLength = -1;
				
				if(data.ownPost == true) var ownPost = true;
				else var ownPost = false;
				
				for(var i = 0; i < arrayLength; i++) {
					if(append) $(layer + " .js-comment-section").append(animation.post.comment.nodeGen(data.comments[i], ownPost));
					else $(layer + " .js-comment-section").prepend(animation.post.comment.nodeGen(data.comments[i], ownPost));
				}
				
				// Apply the Navigation event //
				dataHandler.assignEventListener("click", "page-navigation", applet.linkEngine.callback);
				dataHandler.assignEventListener("click", "js-more-comments", applet.post.moreComments);
				dataHandler.assignEventListener("click", "js-comment-delete", applet.post.deleteComment);
				dataHandler.assignEventListener("keyup", "js-comment-enter", animation.post.comment.charMonitor);
			},
			nodeGen: function (nodeInfo, ownPost = false, xss = false) {
				
				/* -----------------------------------------------------------
				 * Generates the html node to be appened to the comment section
				 * xss means if the written input has been converted to HTML 
				 * special chars
				 * ---------------------------------------------------------- */
				
				var node = 
					html.post.comments.node[0] + nodeInfo.comid + 
					html.post.comments.node[1] + nodeInfo.userid + 
					html.post.comments.node[2] + nodeInfo.profilePic + 
					html.post.comments.node[3] + nodeInfo.userid + 
					html.post.comments.node[4] + nodeInfo.profileName + 
					html.post.comments.node[5] + nodeInfo.profileUserName + 
					html.post.comments.node[6] + nodeInfo.time;
				
				if (xss) {
					nodeInfo.written = dataHandler.htmlspecialchars(nodeInfo.written);
				}
				
				if(nodeInfo.userid == sysTrack.profileUserId || ownPost == true) {
					node += 
						html.post.comments.node[7] + nodeInfo.comid + html.post.comments.node[8];
				}
				
				node +=
					html.post.comments.node[9] + nodeInfo.written + html.post.comments.node[10];
					
				return node;
			},
			charMonitor : function () {
				
				/* -----------------------------------------------------------
				 * This function is a proccess to monitor the comment character
				 * count
				 * ---------------------------------------------------------- */
				
				var charCount = 180 - $(this).val().length;
				var selector = $(this).closest(".js-comment-c").find(".js-comment-char-counter");
				
				if(charCount < 0) $(selector).css("color", "#FF0000");
				else $(selector).css("color", "#AAB8C2");
				
				$(selector).text(charCount);
			}
		},
		remove : function (pid) {
			
			/* -----------------------------------------------------------
			 * Removes the post defined by the passed pid
			 * ---------------------------------------------------------- */
			
			$("div[data-post-id='" + pid + "']").remove();
			
			dataHandler.ajaxHelper("resources/php/handlers/delpost.php", {pid : pid});
		},
		edit : {
			open : function (data) {
				
				/* -----------------------------------------------------------
				 * Inserts a post editing UI inside the post which then sends the
				 * finished text back to server and updates the text of the post 
				 * (which is hidden)
				 * 
				 * It is the response function for the seemore.php and takes the full
				 * text and enters it into the UI
				 * ---------------------------------------------------------- */
				
				if(sysTrack.pidEditActive != 0 && sysTrack.pidEditActive != data.postid) animation.post.edit.close(sysTrack.pidEditActive);
				
				if(sysTrack.pidEditActive != data.postid) {
					var selector = "div[data-focus-layer='" + sysTrack.currentLayer + "'] ";
					
					if(sysTrack.currentSubLayer != 0) selector += "div[data-sub-focus-layer='" + sysTrack.currentSubLayer + "'] ";
					
					$(selector + "div[data-post-id='" + data.postid + "'] .js-post-text").hide();
					
					// Construct HTML //
					var UiHtml = html.post.edit[0];
					
					// Insert HTML with the witten input as the value //
					if(data.written != null && data.written != ''){
						$(selector + "div[data-post-id='" + data.postid + "'] .js-post-text").parent().prepend(UiHtml);
						$(selector + "div[data-post-id='" + data.postid + "'] .js-textbox").val(data.written);
					} else $(selector + "div[data-post-id='" + data.postid + "'] .js-post-head").after(html.post.body.node[0] + UiHtml + html.post.body.node[1]);
					
					// Apply the javascript event trackers to the UI boxes //
					$(".js-textbox").elastic(); // Textarea resizing for input text
					dataHandler.assignEventListener("click", "js-edit-cancel", function(){ animation.post.edit.close(data.postid); });
					dataHandler.assignEventListener("click", "js-edit-confirm", function(){ 
						animation.post.edit.hideError(data.postid); 
						dataHandler.ajaxHelper("resources/php/handlers/editpost.php", {pid : data.postid, text : $(selector + "div[data-post-id='" + data.postid + "'] .js-textbox").val()}, animation.post.edit.response);
					});
					
					sysTrack.pidEditActive = data.postid;
				}
			},
			response : function (data) {
				
				/* -----------------------------------------------------------
				 * After a change to a post is sent to the server this function
				 * checks whether it was a success or fail and updates the interface
				 * accordingly
				 * ---------------------------------------------------------- */
				
				if(data.errorCode == 0) {
					if($("div[data-post-id='" + data.pid + "'] .js-post-text").length == 0 && data.text != null) {
						$("div[data-post-id='" + data.pid + "'] .js-textbox-c").parent().html(html.post.body.written[0] + html.post.body.written[1]);
					}
					
					if(data.text == null) $("div[data-post-id='" + data.pid + "'] .js-post-text").remove();
					else $("div[data-post-id='" + data.pid + "'] .js-post-text").html(animation.utils.textDynamify(data.text, false, 0, 0));
					animation.post.edit.close(data.pid);
					
					dataHandler.assignEventListener("click", "page-navigation", applet.linkEngine.callback);
				} else animation.post.edit.showError(data.pid, data.errorCode);
			},
			close : function(pid) {
				
				/* -----------------------------------------------------------
				 * This function closes a post's edit text UI
				 * ---------------------------------------------------------- */
				 
				$("div[data-post-id='" + pid + "'] .js-textbox-c").remove();
				$("div[data-post-id='" + pid + "'] .js-post-text").show();
				sysTrack.pidEditActive = 0;
			},
			showError : function (pid, message) {
				
				/* -----------------------------------------------------------
				 * This function handles the error messaging for when editing posts
				 * ---------------------------------------------------------- */
				
				$("div[data-post-id='" + pid + "'] .js-textbox-c .js-edit-submit-c").css("border-top-color", "#FF0000");
				$("div[data-post-id='" + pid + "'] .js-textbox-c .js-edit-error").show();
				$("div[data-post-id='" + pid + "'] .js-textbox-c .js-edit-error-text").text(message);
			},
			hideError : function (pid) {
				
				/* -----------------------------------------------------------
				* Hides any error messages in the edit UI for posts
				* ---------------------------------------------------------- */
				
				$("div[data-post-id='" + pid + "'] .js-textbox-c .js-edit-error").hide();
				$("div[data-post-id='" + pid + "'] .js-textbox-c .js-edit-submit-c").css("border-top-color", "#AAB8C2");
			}
		}
	},
	search : {
		query : function (string) {
			
			/* -----------------------------------------------------------
			 * When called, this function looks at the current value in the 
			 * top navigation search bar and sends it's contents to search.php
			 * 
			 * This function is also resonsable for creating the insertion layer
			 * for either the posts (hashtagged) or users (which will be put into a card)
			 * ---------------------------------------------------------- */
			
			clientString = string.replace(/\s/g, '');
			sysTrack.activeSearchQ = string;
			
			// Generate the insertion container //
			dataHandler.clearNodes(".js-search-c");
			if(clientString.charAt(0) == '#') $(".js-search-c").append(html.search.postContainer);
			else if(clientString != '') $(".js-search-c").append(html.search.profileContainer);
			
			dataHandler.ajaxHelper("resources/php/handlers/search.php", {query : string, last : 0}, animation.post.populate);
			window.location.hash = '#3:0';
		},
		toggleBar : function () {
			
			/* -----------------------------------------------------------
			 * When called, this function deploys the search bar on mobile
			 * depending on the state of sysTrack.searchActive
			 * ---------------------------------------------------------- */
			
			// Also shift the search info down by the number of pixels as the height of teh search bar //
			if(sysTrack.searchActive == 0) {
				$("#mobile-search-bar").show();
				$("#search-info").css("margin-top", "50px");
				$("#search-icon").addClass("ic-search-x");
				$("#search-icon").removeClass("ic-search-link");
				sysTrack.searchActive = 1;
			} else {
				$("#mobile-search-bar").hide();
				$("#mobile-search").val('');
				$("#search-info").css("margin-top", "0px");
				$("#search-icon").addClass("ic-search-link");
				$("#search-icon").removeClass("ic-search-x");
				sysTrack.searchActive = 0;
			}
		}
	}
};