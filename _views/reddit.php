<?php
	
	
	function get_string_between($string, $start, $end){
		$string = ' ' . $string;
		$ini = strpos($string, $start);
		if ($ini == 0) return '';
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		return substr($string, $ini, $len);
	}
	$int = 0;
	$limit = '100'; //How many headlines to get
	$showStickies = 0;
	//Flairs whitelist/blacklist
	$flairs = array(); //Which flairs to always show
	$flairs[''] = 1; //Empty
	$flairs['Bad DGU'] = 1;
	$flairs['Bad Form'] = 1;
	$flairs['Bad Title'] = 1;
	$flairs['CCW'] = 1;
	$flairs['CCW-No Shots'] = 1;
	$flairs['Follow Up'] = 1;
	$flairs['Historic'] = 1;
	$flairs['Legal'] = 1;
	$flairs['Analysis'] = 1;
	$flairs['Stats'] = 1;
	$flairs['Tragic'] = 1;
	$flairs['Animals'] = 1;
	$flairs['Sub Req\'d'] = 1;
	
	//Set URL
	$base_reddit = 'http://www.reddit.com/r/dgu/.json'; //Base URL
	$full_reddit = $base_reddit.'?limit='.$limit; //Full URL
	$string_reddit = ''; //full json string
	
	//Get string_reddit or die and throw error message
	$file = file_get_contents($full_reddit);
	if ($file === false) {
		die('<b style="color:red;">ERROR: URL FAILED!!!</b>');
	} else {
		$string_reddit = file_get_contents($full_reddit); //full json string
	}
	
	//Decode json and get children
	$json = json_decode($string_reddit, true); //Create json from string
	$children = $json['data']['children']; //Get all the children
	$int = 0;
	
	
	//Output
	echo 'REDDIT:<br>';
	echo '<ul>';
	
	foreach ($children as $child){
		$rss_id = $rss_page_int.'_'.$int;
		$id = $child['data']['id']; //Headline ID
		$flair = $child['data']['link_flair_text']; //Flair type
		$title = $child['data']['title']; //Headline Title
		$url = $child['data']['url']; //External link to story
		$stickied = $child['data']['stickied']; //Is stickied
		
		
		echo '<li>';
		echo '<input class="thegoodgun-hide" id="toggle_btn_'.$rss_id.'" onclick="toggleTweetEditor(\''.$rss_page_int.'\',\''.$int.'\')" type="button" value="+"/>';
		echo $title;
		echo '<div style="width:100%;display:none;" rows="5" id="toggle_div_'.$rss_id.'" >';
		echo $title;
		
		
		if($authed[0]){
			echo '<input type="button" id="create_tweet_'.$rss_id.'" value="Edit Tweet" onclick="formatTweet(\''.$rss_page_int.'\',\''.$int.'\')"  />';
		}
		echo '</div>';
		echo '<br>';
		echo '</li>';
		echo '<hr>';
		
		
		$int += 1;
	}
	
	echo '</ul>';
	
	
	// $int = 0;
	// $rss = fetch_feed($feed[1]);
	// $maxitems = 0;
	// if(!is_wp_error($rss)){
		// $maxitems = $rss->get_item_quantity(100); 
		// $rss_items = $rss->get_items(0, $maxitems);
	// }
	// if($maxitems == 0){
		// _e( 'No items', 'my-text-domain' );
	// } else {
		// echo '<ul>';
		// echo '<input type="hidden" name="hidden_format" id="hidden_format" value="'.$options['thegoodgun_twitter_format'].'"/>';
		// foreach($rss_items as $item){
			// $rss_id = $rss_page_int.'_'.$int;
			// $rss_title = $item->get_title();
			// $rss_url = $item->get_permalink();
			// $rss_whole = $item->get_content();
			// $rss_excerpt = $item->get_description();
			
			// echo '<li>';
			// echo '<input class="thegoodgun-hide" id="toggle_btn_'.$rss_id.'" onclick="toggleTweetEditor(\''.$rss_page_int.'\',\''.$int.'\')" type="button" value="+"/>';
			// echo $rss_title;
			// echo '<div style="width:100%;display:none;" rows="5" id="toggle_div_'.$rss_id.'" >';
			// echo $rss_excerpt;
			
			// echo '<form method="post" action="">';
			// echo '<input type="hidden" name="hidden_title" id="hidden_title_'.$rss_id.'" value="'.esc_html($rss_title).'"/>';
			// echo '<input type="hidden" name="hidden_url" id="hidden_url_'.$rss_id.'" value="'.esc_html($rss_url).'"/>';
			// echo '<input type="hidden" name="hidden_whole" id="hidden_whole_'.$rss_id.'" value="'.esc_html($rss_whole).'"/>';
			// echo '<input type="hidden" name="hidden_excerpt" id="hidden_excerpt_'.$rss_id.'" value="'.esc_html($rss_excerpt).'"/>';
			// echo '<input type="submit" name="submit" value="Create Draft" />';
			// echo '<input type="button" id="create_tweet_'.$rss_id.'" value="Edit Tweet" onclick="formatTweet(\''.$rss_page_int.'\',\''.$int.'\',\''.$format.'\')"  />';
			// echo '</form>';
			
			
			// echo '</div>';
			// echo '<br>';
			// echo '</li>';
			// echo '<hr>';
			
			// $int += 1;
		// }
		// echo '</ul>';
	// }
	// echo '<hr>';
?>
