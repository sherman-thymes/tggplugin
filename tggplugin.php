<?php
/**
 * Plugin Name: The Good Gun
 * Plugin URI: https://github.com/sherman-thymes
 * Description: The Good Gun
 * Version: 1.0.0
 * Author: The Good Gun
 * Author URI: https://github.com/sherman-thymes
 * License: GPL2
*/

/**
_________
RESOURCES

https://github.com/YahnisElsts/plugin-update-checker#github-integration

https://codex.wordpress.org/Creating_Options_Pages
http://wordpress.stackexchange.com/questions/4991/how-to-use-checkbox-and-radio-button-in-options-page

https://codex.wordpress.org/Function_Reference/fetch_feed
http://simplepie.org/wiki/reference/start#simplepie_item


https://github.com/jublonet/codebird-js
https://github.com/jublonet/codebird-php

https://gist.github.com/cmbaughman/7281fbaae48e922eb727

http://stackoverflow.com/questions/4321914/wp-insert-post-with-a-form

*/

defined('ABSPATH') or die('No script kiddies please!');
define('TGGPLUGIN_GITHUB', 'https://github.com/sherman-thymes/thegoodgun/');
define('TGGPLUGIN_PLUGINKEY', 'Xrj6xz9mE49s5XprIt2zj1fSEihw69uS');

if(is_admin()){
	try{
		$TGGPLUGIN_CLASS = new TGGPLUGIN();
	} catch(Exception $e){
		echo 'Caught exception: ',  $e->getMessage(), "\n";
	}
}

class TGGPLUGIN
{
    private $options;
	private $feeds;
	//CONSTRUCT
    public function __construct(){
		require '_inc/codebird.php';
		require 'update/plugin-update-checker.php';
		$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
			constant('TGGPLUGIN_GITHUB'),
			__FILE__,
			'tggplugin'
		);
		
		$this->options = get_option('TGGPLUGIN_OPTIONS');
		$this->feeds = get_option('TGGPLUGIN_ALLFEEDS');
		
        add_action('admin_menu', array($this, 'TGGPLUGIN_PAGES'));
		add_action('admin_init', array($this, 'TGGPLUGIN_INIT'));
		
		register_activation_hook(__FILE__, array($this, 'TGGPLUGIN_ACTIVATE'));
		register_deactivation_hook(__FILE__, array($this, 'TGGPLUGIN_DEACTIVATE'));
    }
	
	//BUILD
	public function TGGPLUGIN_PAGES(){
		//Get all pages
		$pages = array(
			'tggplugin-settings',
			'tggplugin-feeds'
		);
		foreach($this->feeds As $feed){
			array_push($pages,'tggplugin-'.$feed[0]);
		}
		
		//Only enqueve scripts on plugin pages
		if(in_array($_GET['page'], $pages)){
			add_action('admin_enqueue_scripts', array($this, 'TGGPLUGIN_SCRIPTS'));
			
			//Delete feed
			if(isset($_POST['TGGPLUGIN_DELETEFEED_SUBMIT'])){
				$new_feeds = $this->feeds;
				unset($new_feeds[$_POST['TGGPLUGIN_DELETEFEED_ID']]);
				update_option('TGGPLUGIN_ALLFEEDS', array_values($new_feeds));
				$this->feeds = get_option('TGGPLUGIN_ALLFEEDS');
			}
			
			//Insert feed
			if(isset($_POST['TGGPLUGIN_ADDFEED_SUBMIT'])){
				$new_feed_name = $_POST['TGGPLUGIN_ADDFEED_NAME'];
				$new_feed_url = $_POST['TGGPLUGIN_ADDFEED_URL'];
				$new_feed_view = $_POST['TGGPLUGIN_ADDFEED_VIEW'];
				$isValid = True;
				//Validate variables
				if($isValid == True && $new_feed_name == ''){echo '<span class="thegoodgun-notauthed">Error: Empty Name</span>'; $isValid = False;}
				if($isValid == True && $new_feed_url == ''){echo '<span class="thegoodgun-notauthed">Error: Empty URL</span>'; $isValid = False;}
				if($isValid == True){if(filter_var($new_feed_url, FILTER_VALIDATE_URL) == FALSE) {echo '<span class="thegoodgun-notauthed">Error: Bad URL</span>'; $isValid = False;}}
				
				if($isValid == True){
					//Check if url exists
					foreach($this->feeds As $feed){
						if($feed[1] == $new_feed_url){
							echo '<span class="thegoodgun-notauthed">Error: Duplicate URL</span>'; $isValid = False;
							break;
						}
					}
				}
				
				if($isValid == True){
					$new_feeds = $this->feeds;
					array_push($new_feeds,array(
					$_POST['TGGPLUGIN_ADDFEED_NAME'],
					$_POST['TGGPLUGIN_ADDFEED_URL'],
					$_POST['TGGPLUGIN_ADDFEED_VIEW']
					));
					update_option('TGGPLUGIN_ALLFEEDS', array_values($new_feeds));
					$this->feeds = get_option('TGGPLUGIN_ALLFEEDS');
				}
			}
		}
		
		add_options_page('The Good Gun','The Good Gun','manage_options','tggplugin-settings',array($this, 'TGGPLUGIN_SETTINGS'),plugin_dir_url(__FILE__).'_inc/favicon.png');
		
		if(TGGPLUGIN_PLUGINENABLED()){
			add_menu_page('TGGPLUGIN Feeds','Feeds','manage_options','tggplugin-feeds',array($this, 'TGGPLUGIN_FEEDS'),plugin_dir_url(__FILE__).'_inc/favicon.png');
			
			$alreadyAdded = array();
			foreach($this->feeds As $feed){
				if(!in_array($feed[0], $alreadyAdded)){
					add_submenu_page(
						'tggplugin-feeds', 
						$feed[0], 
						$feed[0], 
						'manage_options', 
						'tggplugin-'.$feed[0], 
						array($this, 'TGGPLUGIN_FEED_PAGE')
					);
					array_push($alreadyAdded,$feed[0]);
				}
			}
		}
	}
	
	//SCRIPTS
	public function TGGPLUGIN_SCRIPTS(){
        wp_register_style('TGGPLUGIN_CSS', plugin_dir_url(__FILE__).'_inc/tggplugin.css');
        wp_enqueue_style('TGGPLUGIN_CSS');
		wp_enqueue_script('TGGPLUGIN_CB', plugin_dir_url(__FILE__).'_inc/codebird.js');
		wp_enqueue_script('TGGPLUGIN_JQUERY', plugin_dir_url(__FILE__).'_inc/jquery-3.1.1.min.js');
		wp_enqueue_script('TGGPLUGIN_JS', plugin_dir_url(__FILE__).'_inc/tggplugin.js');
	}
	
	/////////////////////////////
	// SETTINGS /////////////////
	/////////////////////////////
	public function TGGPLUGIN_INIT(){
		register_setting('TGGPLUGIN_OPTIONS_GROUPS','TGGPLUGIN_OPTIONS',array($this, 'SANITIZE'));
        add_settings_section('TGGPLUGIN_SECTION1','',array($this, 'TGGPLUGIN_SECTION1_INFO'),'tggplugin-settings');  
        add_settings_field('TGGPLUGIN_ISENABLED','Plugin Enabled',array($this, 'TGGPLUGIN_ISENABLED_CALLBACK'),'tggplugin-settings','TGGPLUGIN_SECTION1');      
        add_settings_section('TGGPLUGIN_SECTION2','Twitter Settings',array($this, 'TGGPLUGIN_SECTION2_INFO'),'tggplugin-settings');  
		add_settings_field('TGGPLUGIN_TWITTERKEY1','Consumer Key',array($this, 'TGGPLUGIN_TWITTERKEY1_CALLBACK'),'tggplugin-settings','TGGPLUGIN_SECTION2');      
		add_settings_field('TGGPLUGIN_TWITTERKEY2','Consumer Secret',array($this, 'TGGPLUGIN_TWITTERKEY2_CALLBACK'),'tggplugin-settings','TGGPLUGIN_SECTION2');  
		add_settings_field('TGGPLUGIN_TWITTERKEY3','Access Token',array($this, 'TGGPLUGIN_TWITTERKEY3_CALLBACK'),'tggplugin-settings','TGGPLUGIN_SECTION2');      
		add_settings_field('TGGPLUGIN_TWITTERKEY4','Access Token Secret',array($this, 'TGGPLUGIN_TWITTERKEY4_CALLBACK'),'tggplugin-settings','TGGPLUGIN_SECTION2');  
		add_settings_field('TGGPLUGIN_TWITTERFORMAT','Twitter Format',array($this, 'TGGPLUGIN_TWITTERFORMAT_CALLBACK'),'tggplugin-settings','TGGPLUGIN_SECTION2');  
		add_settings_field('TGGPLUGIN_TWITTERTAGS','Hashtags',array($this, 'TGGPLUGIN_TWITTERTAGS_CALLBACK'),'tggplugin-settings','TGGPLUGIN_SECTION2');  
	}
	
	public function TGGPLUGIN_SETTINGS(){
		//$this->options = get_option('TGGPLUGIN_OPTIONS');
		echo '<div class="wrap">';
		echo '<div class="tggplugin">';
		
		echo '<h1>The Good Gun Settings</h1>';
		echo '<form method="post" action="options.php">';
		submit_button();
		settings_fields('TGGPLUGIN_OPTIONS_GROUPS');
		do_settings_sections('tggplugin-settings');
		submit_button();
		echo '</form>';
		
		echo '</div>';
		echo '</div>';
	}
	
	public function SANITIZE($input){
        $new_input = array();
		if(isset($input['TGGPLUGIN_ISENABLED'])){$new_input['TGGPLUGIN_ISENABLED'] = absint($input['TGGPLUGIN_ISENABLED']);}
		
        if(isset($input['TGGPLUGIN_TWITTERKEY1'])){$new_input['TGGPLUGIN_TWITTERKEY1'] = sanitize_text_field(TGGPLUGIN_ENCRYPT($input['TGGPLUGIN_TWITTERKEY1'], constant('TGGPLUGIN_PLUGINKEY')));}
		if(isset($input['TGGPLUGIN_TWITTERKEY2'])){$new_input['TGGPLUGIN_TWITTERKEY2'] = sanitize_text_field(TGGPLUGIN_ENCRYPT($input['TGGPLUGIN_TWITTERKEY2'], constant('TGGPLUGIN_PLUGINKEY')));}
		if(isset($input['TGGPLUGIN_TWITTERKEY3'])){$new_input['TGGPLUGIN_TWITTERKEY3'] = sanitize_text_field(TGGPLUGIN_ENCRYPT($input['TGGPLUGIN_TWITTERKEY3'], constant('TGGPLUGIN_PLUGINKEY')));}
		if(isset($input['TGGPLUGIN_TWITTERKEY4'])){$new_input['TGGPLUGIN_TWITTERKEY4'] = sanitize_text_field(TGGPLUGIN_ENCRYPT($input['TGGPLUGIN_TWITTERKEY4'], constant('TGGPLUGIN_PLUGINKEY')));}
		if(isset($input['TGGPLUGIN_TWITTERFORMAT'])){$new_input['TGGPLUGIN_TWITTERFORMAT'] = $input['TGGPLUGIN_TWITTERFORMAT'];}
		if(isset($input['TGGPLUGIN_TWITTERTAGS'])){$new_input['TGGPLUGIN_TWITTERTAGS'] = $input['TGGPLUGIN_TWITTERTAGS'];}
		return $new_input;
    }
	
	public function TGGPLUGIN_ISENABLED_CALLBACK(){
		$checked = '';
		if($this->options['TGGPLUGIN_ISENABLED'] == True){$checked = 'checked';}
		echo '<input type="checkbox" name="TGGPLUGIN_OPTIONS[TGGPLUGIN_ISENABLED]" value="1" '.$checked.' />';
	}
	
	public function TGGPLUGIN_TWITTERKEY1_CALLBACK(){
	
		echo '<input type="text" name="TGGPLUGIN_OPTIONS[TGGPLUGIN_TWITTERKEY1]" value="'.esc_attr(TGGPLUGIN_DECRYPT($this->options['TGGPLUGIN_TWITTERKEY1'], constant('TGGPLUGIN_PLUGINKEY'))).'" />';
    }
	public function TGGPLUGIN_TWITTERKEY2_CALLBACK(){
		echo '<input type="text" name="TGGPLUGIN_OPTIONS[TGGPLUGIN_TWITTERKEY2]" value="'.esc_attr(TGGPLUGIN_DECRYPT($this->options['TGGPLUGIN_TWITTERKEY2'], constant('TGGPLUGIN_PLUGINKEY'))).'" />';
    }
	public function TGGPLUGIN_TWITTERKEY3_CALLBACK(){
		echo '<input type="text" name="TGGPLUGIN_OPTIONS[TGGPLUGIN_TWITTERKEY3]" value="'.esc_attr(TGGPLUGIN_DECRYPT($this->options['TGGPLUGIN_TWITTERKEY3'], constant('TGGPLUGIN_PLUGINKEY'))).'" />';
    }
	public function TGGPLUGIN_TWITTERKEY4_CALLBACK(){
		echo '<input type="text" name="TGGPLUGIN_OPTIONS[TGGPLUGIN_TWITTERKEY4]" value="'.esc_attr(TGGPLUGIN_DECRYPT($this->options['TGGPLUGIN_TWITTERKEY4'], constant('TGGPLUGIN_PLUGINKEY'))).'" />';
    }	
	public function TGGPLUGIN_TWITTERFORMAT_CALLBACK(){
		echo '<textarea style="width:100%;height:200px;" name="TGGPLUGIN_OPTIONS[TGGPLUGIN_TWITTERFORMAT]" >'.$this->options['TGGPLUGIN_TWITTERFORMAT'].'</textarea>';
	}	
	public function TGGPLUGIN_TWITTERTAGS_CALLBACK(){
		echo '<textarea style="width:100%;height:200px;" name="TGGPLUGIN_OPTIONS[TGGPLUGIN_TWITTERTAGS]" >'.$this->options['TGGPLUGIN_TWITTERTAGS'].'</textarea>';
	}	
	
	public function TGGPLUGIN_SECTION1_INFO(){
		
	}
	public function TGGPLUGIN_SECTION2_INFO(){
		$authed = TGGPLUGIN_TWITTERAUTHED();
		$watts = TGGPLUGIN_WATTSBLOCKED();
		if($authed[0] == True){
			echo '<span class="thegoodgun-authed">'.$authed[1].'</span><br>';
			if($watts[0] == True){
				echo '<span class="thegoodgun-authed">'.$watts[1].'</span>';
			} else {
				echo '<span class="thegoodgun-notauthed">'.$watts[1].'</span>';
			}
		} else {
			echo '<span class="thegoodgun-notauthed">'.$authed[1].'</span>';
		}
	}
	
	/////////////////////////////
	// FEEDS ////////////////////
	/////////////////////////////
	public function TGGPLUGIN_FEEDS(){
		if(TGGPLUGIN_PLUGINENABLED()){
			//Feeds page (ONLY)
			if(isset($_GET['page']) && $_GET['page'] == 'tggplugin-feeds'){
				echo '<div class="wrap">';
				echo '<div class="tggplugin">';
				
				//Add feed form
				echo '<h1>Add Feed:</h1>';
				echo '<form method="post" action="">';
				echo '<ul>';
				echo '<li>Name:</li>';
				echo '<li><input type="text" name="TGGPLUGIN_ADDFEED_NAME" value="'.$_POST['TGGPLUGIN_ADDFEED_NAME'].'" /></li>';
				echo '<li>URL:</li>';
				echo '<li><input style="width:100%;" type="text" name="TGGPLUGIN_ADDFEED_URL" value="'.$_POST['TGGPLUGIN_ADDFEED_URL'].'" /></li>';
				echo '<li>View:</li>';
				echo '<li><select name="TGGPLUGIN_ADDFEED_VIEW" >';
				$dir = new DirectoryIterator(dirname(__FILE__).'\_views');
				foreach($dir as $fileinfo){
					$view = str_replace(".php","",$fileinfo->getFilename());
					if(!$fileinfo->isDot()){
						echo '<option value="'.$view.'">'.$view.'</option>';
					}
				}
				echo '</select></li>';
				echo '<br>';
				echo '<li><input type="submit" name="TGGPLUGIN_ADDFEED_SUBMIT" value="Add Feed" /></li>';
				echo '</ul>';
				echo '</form>';
				
				echo '<br><hr><br>';
				
				//Display All feeds
				echo '<h1>All Feeds:</h1>';
				if(count($this->feeds) == 0){
					echo 'No feeds.';
				} else {
					echo '<table style="width:100%">';
					echo '<tr>';
					echo '<th width="20px" ></th>';
					echo '<th>Title</th>';
					echo '<th>URL</th> ';
					echo '</tr>';
					for($i = 0; $i <= count($this->feeds) - 1; $i++){
						echo '<form method="post" action="">';
						echo '<input type="hidden" name="TGGPLUGIN_DELETEFEED_ID" value="'.$i.'" />';
						echo '<tr>';
						echo '<td><input value="X" class="thegoodgun-delete" name="TGGPLUGIN_DELETEFEED_SUBMIT" type="submit" /></td>';
						echo '<td><a target="_BLANK" href="'.admin_url().'admin.php?page=tggplugin-'.$this->feeds[$i][0].'">'.$this->feeds[$i][0].'</a></td>';
						echo '<td><a target="_BLANK" href="'.$this->feeds[$i][1].'">'.$this->feeds[$i][1].'</a></td>';
						echo '</tr>';
						echo '</form>';
					}
					echo '</table>';
				}
				echo '</div>';
				echo '</div>';
			}
		}
	}
	
	public function TGGPLUGIN_FEED_PAGE(){
		//Create draft
		$pages = array();
		foreach($this->feeds As $feed){
			array_push($pages,'tggplugin-'.$feed[0]);
		}
		if(in_array($_GET['page'], $pages)){
			if(isset($_POST['TGGPLUGIN_CREATEDRAFT'])){
				$post_title = $_POST['TGGPLUGIN_HIDDEN_TITLE'];
				$post_url = $_POST['TGGPLUGIN_HIDDEN_URL'];
				$post_content = $_POST['TGGPLUGIN_HIDDEN_WHOLE'];
				$post_excerpt = $_POST['TGGPLUGIN_HIDDEN_EXCERPT'];
				//$post_featured = $_POST['hidden_featured'];
				
				$post_category = 0;

				$new_post = array(
						'ID' => '',
						'post_author' => $user->ID, 
						'post_category' => array($post_category),
						'post_content' => $post_content, 
						'post_excerpt' => $post_excerpt, 
						'post_title' => $post_title,
						'post_status' => 'draft'
					);
					
				$post_id = wp_insert_post($new_post);
				  
				
				// if(!empty($post_featured)){
					// // Add Featured Image to Post
					// $image_url        = $post_featured; // Define the image URL here
					
					// $image_name       = 'thegoodgun-'.$post_id.'.png';
					// $upload_dir       = wp_upload_dir(); // Set upload folder
					// $image_data       = file_get_contents($image_url); // Get image data
					// $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
					// $filename         = basename($unique_file_name); // Create image file name

					// // Check folder permission and define file location
					// if( wp_mkdir_p($upload_dir['path'])){
						// $file = $upload_dir['path'].'/'.$filename;
					// } else {
						// $file = $upload_dir['basedir'].'/'.$filename;
					// }
					// file_put_contents($file, $image_data);
					// $wp_filetype = wp_check_filetype($filename, null);
					// $attachment = array(
						// 'post_mime_type' => $wp_filetype['type'],
						// 'post_title'     => sanitize_file_name($filename),
						// 'post_content'   => '',
						// 'post_status'    => 'inherit'
					// );
					// $attach_id = wp_insert_attachment($attachment, $file, $post_id);
					// require_once(ABSPATH.'wp-admin/includes/image.php');
					// $attach_data = wp_generate_attachment_metadata($attach_id, $file);
					// wp_update_attachment_metadata($attach_id, $attach_data);
					// set_post_thumbnail($post_id, $attach_id);
				// }
			}
			
			//Single feed
			echo '<div class="wrap">';
			echo '<div class="tggplugin">';
			$rss_page = trim(str_replace('tggplugin-','',$_GET['page']));
			$rss_page_int = 0;
			echo '<h1>'.$rss_page.'</h1>';
			foreach($this->feeds As $feed){
				if($feed[0] == $rss_page){
					switch($feed[2]){
						case 'default':
							include('_views/default.php');
							break;
						default:
							
							include('_views/'.$feed[2].'.php');
							break;
					}
					$rss_page_int += 1;
				}
			}
			
			//Tweet form
			$key1 = TGGPLUGIN_DECRYPT($this->options['TGGPLUGIN_TWITTERKEY1'], constant('TGGPLUGIN_PLUGINKEY'));
			$key2 = TGGPLUGIN_DECRYPT($this->options['TGGPLUGIN_TWITTERKEY2'], constant('TGGPLUGIN_PLUGINKEY'));
			$key3 = TGGPLUGIN_DECRYPT($this->options['TGGPLUGIN_TWITTERKEY3'], constant('TGGPLUGIN_PLUGINKEY'));
			$key4 = TGGPLUGIN_DECRYPT($this->options['TGGPLUGIN_TWITTERKEY4'], constant('TGGPLUGIN_PLUGINKEY'));
			
			echo '<br>';
			echo '<a id="tweetEditor" href="#"></a>';
			echo '<h1>Tweet Editor:</h1>';
			
			if($twitter_authed == True){
				$insertAr = explode("\n", $this->options['TGGPLUGIN_TWITTERTAGS']);
				for($insert = 0; $insert <= count($insertAr) - 1; $insert++){
					if(trim($insertAr[$insert]) == Null){continue;}
					echo '<input class="thegoodgun-hashtag" type="button" value="'.trim($insertAr[$insert]).'" onClick="insertHashtag(\''.trim($insertAr[$insert]).'\')" />';
				}
				echo '<textarea id="tweet_editor" style="width:98%" rows="10"></textarea>';
				echo '<br><input onClick="sendTweet(\''.$key1.'\',\''.$key2.'\',\''.$key3.'\',\''.$key4.'\');" type="button" value="Send Tweet"/>';
				
			} else {
				echo '<span class="thegoodgun-notauthed">'.$authed[1].'</span>';
			}
			
			echo '</div>';
			echo '</div>';
		}
	
		
	}
   
	public function TGGPLUGIN_ACTIVATE(){
		//Add default options
		add_option('TGGPLUGIN_OPTIONS');
		$options = array();
		$options['TGGPLUGIN_ISENABLED'] = False;
		$options['TGGPLUGIN_TWITTERKEY1'] = TGGPLUGIN_ENCRYPT('a78i24oHkfX52IdcCvqp8OLJZ', constant('TGGPLUGIN_PLUGINKEY'));
		$options['TGGPLUGIN_TWITTERKEY2'] = TGGPLUGIN_ENCRYPT('jpOy3ivUJVvW6b2e12b9TPKBLMHCmmnLuQosMeJKq48JNP5NPG', constant('TGGPLUGIN_PLUGINKEY'));
		$options['TGGPLUGIN_TWITTERKEY3'] = TGGPLUGIN_ENCRYPT('826452967620485121-Hz6Tj7BZYhys5ng0zUvU8kSQLoY6cs4', constant('TGGPLUGIN_PLUGINKEY'));
		$options['TGGPLUGIN_TWITTERKEY4'] = TGGPLUGIN_ENCRYPT('bjMNT39nTsJBVOGq8q3tIeB9AIHc0sUF2fVJgBHJZgXiL', constant('TGGPLUGIN_PLUGINKEY'));
		$options['TGGPLUGIN_TWITTERFORMAT'] = '[HEADLINE]&#10;[URL]&#10;&#10;#GunSense';
		$options['TGGPLUGIN_TWITTERTAGS'] = '#GunSense&#10;#GunControl';
		update_option('TGGPLUGIN_OPTIONS', $options);
		
		add_option('TGGPLUGIN_ALLFEEDS');
		$feeds = array();
		array_push($feeds,array('BearingArms','https://bearingarms.com/feed/','default'));
		array_push($feeds,array('BearingArms','https://bearingarms.com/category/guns-saving-lives/feed/','default'));
		array_push($feeds,array('BearingArms','https://bearingarms.com/category/guns-and-gear/feed/','default'));
		array_push($feeds,array('TGG','http://www.thetruthaboutguns.com/feed/','default'));
		array_push($feeds,array('Ammoland','http://www.ammoland.com/feed/','default'));
		array_push($feeds,array('DGUs','https://www.reddit.com/r/dgu/?limit=100','reddit'));
		update_option('TGGPLUGIN_ALLFEEDS', $feeds);
		
	}
	
	
	public function TGGPLUGIN_DEACTIVATE(){
		delete_option('TGGPLUGIN_OPTIONS');
	}
	
	
} //END CLASS______________________________________________________________
//=========================================================================





function TGGPLUGIN_ENCRYPT($input_string, $key){
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $h_key = hash('sha256', $key, TRUE);
    return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $h_key, $input_string, MCRYPT_MODE_ECB, $iv));
}
function TGGPLUGIN_DECRYPT($encrypted_input_string, $key){
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $h_key = hash('sha256', $key, TRUE);
    return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $h_key, base64_decode($encrypted_input_string), MCRYPT_MODE_ECB, $iv));
}	

function TGGPLUGIN_PLUGINENABLED(){
	$options = get_option('TGGPLUGIN_OPTIONS');
	if($options['TGGPLUGIN_ISENABLED'] == True){
		return True;
	} else {
		return False;
	}
}

function TGGPLUGIN_TWITTERAUTHED(){
	$options = get_option('TGGPLUGIN_OPTIONS');
	//Check keys first
	$key1 = TGGPLUGIN_DECRYPT($options['TGGPLUGIN_TWITTERKEY1'], constant('TGGPLUGIN_PLUGINKEY'));
	$key2 = TGGPLUGIN_DECRYPT($options['TGGPLUGIN_TWITTERKEY2'], constant('TGGPLUGIN_PLUGINKEY'));
	$key3 = TGGPLUGIN_DECRYPT($options['TGGPLUGIN_TWITTERKEY3'], constant('TGGPLUGIN_PLUGINKEY'));
	$key4 = TGGPLUGIN_DECRYPT($options['TGGPLUGIN_TWITTERKEY4'], constant('TGGPLUGIN_PLUGINKEY'));
	$keys = array($key1, $key2, $key3, $key4);
	
	//Check empty
	foreach($keys As $key){
		if($key == ''){
			return array(false, 'Error: Empty keys.');
		}
	}
	
	//https://github.com/jublonet/codebird-php
	\Codebird\Codebird::setConsumerKey($key1, $key2); // static, see README
	$cb = \Codebird\Codebird::getInstance();
	$cb->setToken($key3, $key4);
	$cb->setReturnFormat(CODEBIRD_RETURNFORMAT_ARRAY);
	$reply = $cb->statuses_userTimeline();
	
	if($reply['httpstatus'] == '200') {
		return array(true, 'Twitter authenticated.');
	} else {
		return array(false, 'Twitter NOT authenticated.');
	}
}

function TGGPLUGIN_WATTSBLOCKED(){
	$options = get_option('TGGPLUGIN_OPTIONS');
	//Check keys first
	$key1 = TGGPLUGIN_DECRYPT($options['TGGPLUGIN_TWITTERKEY1'], constant('TGGPLUGIN_PLUGINKEY'));
	$key2 = TGGPLUGIN_DECRYPT($options['TGGPLUGIN_TWITTERKEY2'], constant('TGGPLUGIN_PLUGINKEY'));
	$key3 = TGGPLUGIN_DECRYPT($options['TGGPLUGIN_TWITTERKEY3'], constant('TGGPLUGIN_PLUGINKEY'));
	$key4 = TGGPLUGIN_DECRYPT($options['TGGPLUGIN_TWITTERKEY4'], constant('TGGPLUGIN_PLUGINKEY'));
	
	//https://github.com/jublonet/codebird-php
	\Codebird\Codebird::setConsumerKey($key1, $key2); // static, see README
	$cb = \Codebird\Codebird::getInstance();
	$cb->setToken($key3, $key4);
	$cb->setReturnFormat(CODEBIRD_RETURNFORMAT_ARRAY);
	
	$params = [
		'screen_name' => 'shannonrwatts'
	];
	$reply = $cb->friendships_create($params);
	
	if($reply['errors'][0]['code'] == '162') {
		return array(true, 'Congratulations, you are blocked by Shannon Watts!');
	} else {
		return array(false, 'How are you NOT blocked by Shannon Watts?');
	}
}

?>
