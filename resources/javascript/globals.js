//--------------------------------------------------

/* Constants to be used site wide */

//--------------------------------------------------


// As these are constants they need to be loaded everytime the page is downloaded //
var ERROR_MESSAGE = [, "Written input is too large, keep under 30,000 characters",
			"Written input is too large, keep under 150 characters",
			"Input must not be empty",
			"No file detected",
			"No topic selected, choose Other if unsure",
			"Error conecting to server, check internet connection",
			"Unexpected error occured. Try again later?",
			"Please enter a valid URL",
			"Sorry but you have reached our spam protection of 8 posts a day",
			"Only PNG, JPG and GIF is allowed.",
			"Image is too small, please keep above 500px for height and width",
			"Please keep image size under 10MB",
			"Some trouble has been had connecting to YouTube, try again later"];

var TOPIC = {s : "Blurb", ap : "Animals and Pets", ac : "Arts and Crafts", c : "Computers", f : "Fashion", fc : "Food and Cooking", fu : "Funny", 
		g : "Games", hf : "Health and Fitness", hc : "History and Culture", lo : "Lifestyle and Opinions", m : "Memes",
		mu : "Music", ne : "Nature and Environment", o : "Other", fg : "Friends and Gatherings", st : "Science and Tech",
		sp : "Sports", t : "Travel", tm : "Tv and Movies", wn : "World and News", e : "Entertaiment"};
		
var LAYER_TO_STRING = {"1:0" : 'm', "2:1" : 'p', "2:2" : 'pa', "2:3" : 'sb', "2:4" : 'l', "3:0" : 's'};
var LEVEL_COLOR = ["#737373", "#00A8EC", "#31B573", "#ED6C9E", "#AF5D9C", "#314396", "#FF0000", "#A7A400", "#004E49", "#000000"];
var SIZE_STRING = ["level-small", "level-medium", "level-large"];
var DOMAIN = "http://222.153.11.66/";