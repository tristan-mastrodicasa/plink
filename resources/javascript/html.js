//--------------------------------------------------

/* This JS code is a container for the html that needs
 * to be inserted into the application for items such as
 * search results, posts, etc */

//--------------------------------------------------

/*postId
	 * 			postRef (If reposted)
	 * 			userId
	 * 			insertId (client generated)*/

var html = {
	post : {
		head : [
			"<div class='width-full-mb-20 no-display' data-post-id='", // PostId
			"'><div class='card ly-standard-border ly-border-bottom-flat'><div class='width-full-mb-10 js-post-head'><a href='#2:1' class='page-navigation' data-callback='profile' data-param='", // UserId
			"'><img src='", // UserImage
			"' class='m-prof-pic-medium ly-standard-border left'></a><div class='ly-poster-details'><span class='ly-poster-lines width-full'><a href='#2:1' class='page-navigation t-invisible-link' data-callback='profile' data-param='", // UserId
			"'><span class='t-poster-first-name'>", // FirstName
			"</span><span class='t-poster-other'> @", // UserName
			"</span></a></span><span class='ly-poster-lines width-full'><span class='t-poster-other font-14'>", // Topic
			" · ", // Time
			// Optional Reposter Header //
			" · <a href='#2:1' class='page-navigation t-invisible-link' data-callback='profile' data-param='", // UserId
			"'><span class='ic-repost-small ly-poster-repost'></span> ", // Reposter Name
			"</a>",
			// *** //
			"</span></span></div><div class='clearfix'></div></div>"
		],
		body : {
			node : [ 
				"<div class='width-full-mb-10'>", // Content
				"</div>"
			],
			written : [
				"<p class='t-post-content-text mp-reset js-post-text'>", // Written
				"</p>"
			],
			image : {
				desktop : [
					"<div class='width-full-mb-10'><img src='", // ImageSrc
					"' class='m-post-media' onclick='animation.popup.open(\"i\", \"", // ImageBig
					"\")'></div>"
				],
				mobile : [
					"<div class='width-full-mb-10'><img src='", // ImageSrc
					"' class='m-post-media'></div>"
				]
			},
			openGraph : { 
				link : [
					"<a href='", // URL
					"' target = '_blank' class='t-invisible-link'>", // Content
					"</a>"
				],
				largeImg : [
					"<div class='ly-open-graph-b-img ly-open-graph-border ly-image-fill-center-c'><img src='", // Large Image
					"' class='ly-image-fill-center-img m-open-graph-b-img'></div>"
				],
				smallImg : [
					"<div class='ly-open-graph-f ly-open-graph-f-img ly-open-graph-border ly-image-fill-center-c'><img src='", // Small Image
					"' class='ly-image-fill-center-img max-height-full'></div>"
				],
				largeTitle : [
					"<div class='ly-open-graph-b border-box width-full padding-10 ly-open-graph-border'><div class='width-full'><h3 class='mp-reset t-open-graph-t'>", // Title
					"</h3></div>"
				],
				smallTitle : [
					"<div class='ly-open-graph-f-d ly-open-graph-border'><div class='width-full'><h3 class='mp-reset t-open-graph-t'>", // Title
					"</h3></div>"
				],
				description : [
					"<div class='width-full-mt-10'><p class='mp-reset t-open-graph-d'>", // Paragraph
					"</p></div>"
				],
				end : "</div><div class='clearfix'></div></div>"
			},
			youtube : [
				"<iframe class='m-post-media' height='300px' src='", // Youtube Link
				"' frameborder='0' allowfullscreen></iframe>"
			]
		},
		buttons : {
			container : [
				"<div class='width-full pb-2-toggle block-display'>", // All Buttons
				"<div class='clearfix'></div></div></div>" // End of Everything
			],
			postAnalysis : [
				"Views' class='ic-seen",
				"Endorsements' class='ic-endorsement",
				"Reposts' class='ic-repost",
				"Comments' class='ic-comment",
				"<div class='ly-statlet mb-20-toggle left'><div class='width-full-mb-10'><abbr title='", // Vary's
				" center'></abbr></div><p class='mp-reset center-text font-18'>", // Number
				"</p></div>"
			],
			induvidual : [
				"<div class='ly-post-action width-quarter left'><button class='b-post-action ", // Type of Button
				"' data-com='", // If the user has already commited an action
				"' data-pid='", // Post Id
				"'><div class='ic-b-action' style='background-image:url(\"", // The image of the button
				"\");'></div><span style='color: ",
				";' data-user-count='", // Number of users to have acted on stuff
				"'>", // Number of users to have acted on stuff
				"</span></button></div>",
				"' data-uid='" // Userid (stop own repost)
			]
		},
		comments : {
			container : [
				"<div class='ly-comment-color border-box width-full padding-10 ly-standard-border ly-border-top-flat nb-top js-comment-c'>", // Everything comment related
				"</div>"
			],
			section : "<div class='js-comment-section'></div>",
			more : [
				"<div class='width-full-mb-10'><div class='width-half left left-text'><span class='t-see-more-comments js-more-comments'>See more comments</span></div><div class='width-half left right-text'><span class='t-grey-13 js-comment-fraction'>", // Fraction
				"</span></div><div class='clearfix'></div></div>"
			],
			enter : [
				"<div class='width-full'><img src='", // Tiny User Picture
				"' class='m-prof-pic-small left ly-border-grey'><div class='ly-comment-written'><form onsubmit='applet.post.comment(", // Post Id
				"); return false;'><div class='in-comment-input-c border-box ly-border-grey'><div class='right'><div class='ly-comment-char-counter center-text'><span class='t-char-counter block-display ly-comment-char-counter js-comment-char-counter'>180</span></div></div><div class='height-full ly-comment-written-input'><input type='text' placeholder='Add comment ...' class='in-comment-input js-comment-enter border-box width-full'></div><div class='clearfix'></div></div></form></div><div class='clearfix'></div></div>"
			],
			node : [
				"<div class='width-full-mb-10 ly-comment-node js-comment-node' data-id='", // Comment id
				"'><a href='#2:1' class='page-navigation' data-callback='profile' data-param='", // Userid
				"'><img src='", // User Pic
				"' class='m-prof-pic-small left ly-border-grey'></a><div class='ly-comment-written'><div class='width-full'><a href='#2:1' class='page-navigation t-invisible-link' data-callback='profile' data-param='", // Userid
				"'><span class='t-poster-first-name font-13'>", // Firstname
				"</span></a><span class='t-grey-13'>", // Username
				"<span class='dot-sep'> · </span>", // Time
				"<span class='dot-sep'> · </span><span class='ly-comment-delete js-comment-delete' data-cid='", // Comment id
				"'>Delete</span>", // Add if userid = loaded id
				"</span></div><div class='ly-poster-lines width-full'><p class='mp-reset font-13'>", // The written comment
				"</p></div></div><div class='clearfix'></div></div>"
			]
		},
		edit : [
			"<div class='ly-standard-border border-box width-full js-textbox-c'><div class='border-box width-full padding-15'><textarea class='in-large-text ly-text-area width-full t-post-content-text js-textbox'></textarea></div><div class='ly-red-border ly-publish-error width-full no-display nb-right nb-left nb-bottom js-edit-error'><p class='t-error-text-publish js-edit-error-text'></p></div><div class='border-box width-full ly-standard-border padding-10 nb-right nb-left nb-bottom js-edit-submit-c'><button class='b-orange-white right js-edit-confirm'>Save</button><button class='b-white-black mr-5 right js-edit-cancel'>Cancel</button><div class='clearfix'></div></div></div>"
		]
	},
	profile : [
		"<div class='width-full-mb-15' data-uid='", // User Id
		"'>",
		"<div class='ly-unsub-block'><button class='b-clear ic-x' onclick='applet.profile.unSubX(", // User Id
		");'></button></div>",
		"<a href='#2:1' class='page-navigation t-invisible-link' data-callback='profile' data-param='", // User Id
		"'><div class='ly-sub-line'><img src='", // User Picture
		"' class='m-prof-pic-medium ly-standard-border left'><div class='ly-poster-details'><span class='ly-poster-lines width-full'><span class='t-poster-first-name'>", // First Name
		"</span></span><span class='ly-poster-lines width-full'><span class='t-poster-other'>", // Username
		"</span></span></div><div class='clearfix'></div></div><div class='clearfix'></div></a></div>"
	],
	search : {
		profileContainer : "<div class='width-full-mb-20'><div class='card ly-standard-border'><div class='insertion-container'></div><div class='width-full'><img src='../resources/images/misc/post-loader.gif' class='center' id='search-loader'><h3 class='font-20 center-text mp-reset no-display' id='search-end'>End of results</h3></div></div></div>",
		postContainer : "<div class='width-full-mb-20'><div class='insertion-container'></div><div class='width-full'><img src='../resources/images/misc/post-loader.gif' class='center' id='search-loader'><h3 class='font-20 center-text mp-reset no-display' id='search-end'>End of results</h3></div></div>"
	}
}