function toggleTweetEditor(num1, num2){
	var x = document.getElementById('toggle_div_' + num1 + '_' + num2);
	var y = document.getElementById('toggle_btn_' + num1 + '_' + num2);
	if (x.style.display === 'none') {
		y.value = '-';
		x.style.display = 'block';
	} else {
		y.value = '+';
		x.style.display = 'none';
	}
}

function insertHashtag(ht){
	var txt = document.getElementById('tweet_editor').value;
	if(txt.includes(ht) == 0){
		document.getElementById('tweet_editor').value = txt + '\n' + ht;
	}
}

function formatTweet(num1, num2){
	var title = document.getElementById('hidden_title_' + num1 + '_' + num2).value;
	var url = document.getElementById('hidden_url_' + num1 + '_' + num2).value;
	var format = document.getElementById('hidden_format').value;
	format = format.replace("[HEADLINE]", title);
	format = format.replace("[URL]", url);
	document.getElementById('tweet_editor').value = format;
	window.location.href = "#"+'tweetEditor';
}

function sendTweet(key1, key2, key3, key4){
	var cb = new Codebird;
	cb.setConsumerKey(key1, key2);
	cb.setToken(key3, key4);
	var tweet = document.getElementById('tweet_editor').value;

	var params = {
		status: tweet
	};
	
	cb.__call(
		"statuses_update",
		params,
		function (reply, rate, err) {
			// ...
		}
	);
}


function testJSFILE(){alert('Working!!!');}