<?php
	$int = 0;
	$rss = fetch_feed($feed[1]);
	$twitter_authed = TGGPLUGIN_TWITTERAUTHED();
	$maxitems = 0;
	if(!is_wp_error($rss)){
		$maxitems = $rss->get_item_quantity(100); 
		$rss_items = $rss->get_items(0, $maxitems);
	}
	if($maxitems == 0){
		_e( 'No items', 'my-text-domain' );
	} else {
		echo '<ul>';
		echo '<input type="hidden" name="hidden_format" id="hidden_format" value="'.$this->options['TGGPLUGIN_TWITTERFORMAT'].'"/>';
		foreach($rss_items as $item){
			$rss_id = $rss_page_int.'_'.$int;
			$rss_title = $item->get_title();
			$rss_url = $item->get_permalink();
			$rss_whole = $item->get_content();
			$rss_excerpt = $item->get_description();
			//$rss_featured = catch_first_image($rss_whole);
			
			echo '<li>';
			echo '<input class="thegoodgun-hide" id="toggle_btn_'.$rss_id.'" onclick="toggleTweetEditor(\''.$rss_page_int.'\',\''.$int.'\')" type="button" value="+"/>';
			echo $rss_title;
			echo '<div style="width:100%;display:none;" rows="5" id="toggle_div_'.$rss_id.'" >';
			echo $rss_excerpt;
			
			echo '<form method="post" action="">';
			echo '<input type="hidden" name="TGGPLUGIN_HIDDEN_TITLE" id="hidden_title_'.$rss_id.'" value="'.esc_html($rss_title).'"/>';
			echo '<input type="hidden" name="TGGPLUGIN_HIDDEN_URL" id="hidden_url_'.$rss_id.'" value="'.esc_html($rss_url).'"/>';
			echo '<input type="hidden" name="TGGPLUGIN_HIDDEN_WHOLE" id="hidden_whole_'.$rss_id.'" value="'.esc_html($rss_whole).'"/>';
			echo '<input type="hidden" name="TGGPLUGIN_HIDDEN_EXCERPT" id="hidden_excerpt_'.$rss_id.'" value="'.esc_html($rss_excerpt).'"/>';
			//echo 'Featured Image: (Leave blank for none)<br><input style="width:98%;" type="text" name="hidden_featured" id="hidden_featured_'.$rss_id.'" value="'.esc_html($rss_featured).'"/><br>';
			echo '<input type="submit" name="TGGPLUGIN_CREATEDRAFT" value="Create Draft" />';
			if($twitter_authed == True){
				echo '<input type="button" id="create_tweet_'.$rss_id.'" value="Edit Tweet" onclick="formatTweet(\''.$rss_page_int.'\',\''.$int.'\',\''.$format.'\')"  />';
			}
			
			echo '</form>';
			
			
			echo '</div>';
			echo '<br>';
			echo '</li>';
			echo '<hr>';
			
			$int += 1;
		}
		echo '</ul>';
	}
	echo '<hr>';
?>
