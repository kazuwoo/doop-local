<?php
/**
 * admin class.
 *
 * @package Fudousan Tweet old post
 * @subpackage Fudousan Plugin
 * Version: 1.6.1
 */


/**
 *
 * TwitterOAuth redirect
 *
 * @since Fudousan Tweet old post Plugin 1.6.0
 */
function top_fudou_twitter_oauth_redirect( $oauth_callback_url ) {

	require_once WP_PLUGIN_DIR . '/fudou-tweet-old-post/twitteroauth/autoload.php';

	$consumer_key    = fudo_top_consumer_key();
	$consumer_secret = fudo_top_consumer_secret();

	// Build TwitterOAuth object with client credentials.
	$connection = new Abraham\TwitterOAuth\TwitterOAuth( $consumer_key, $consumer_secret );

	// Get temporary credentials.
	$request_token = $connection->oauth( 'oauth/request_token', array('oauth_callback' => $oauth_callback_url ));

	// Save temporary credentials to session For callback.
	$tmp_oauth_token	= $request_token['oauth_token'];
	$tmp_oauth_token_secret = $request_token['oauth_token_secret'];
	update_option('top_opt_tmp_oauth_token', $tmp_oauth_token );
	update_option('top_opt_tmp_oauth_token_secret', $tmp_oauth_token_secret );

	if( !isset( $request_token['oauth_token'] ) || $request_token['oauth_token'] == '' ){
		// Show notification if something went wrong.
		return  __("Could not connect to Twitter. Refresh the page or try again later..", 'TweetOldPost');
	}else{
		//Get twitter.com url
		$url = $connection->url('oauth/authenticate', array('oauth_token' => $request_token['oauth_token']));

		//Redirect to twitter.com
		echo '<script language="javascript">window.location.href= "' .$url . '";</script>';
		die;
	}
}

/**
 *
 * TwitterOAuth callback
 *
 * @since Fudousan Tweet old post Plugin 1.6.0
 */
function top_fudou_twitter_oauth_callback( $author_id = '' ) {

	require_once WP_PLUGIN_DIR . '/fudou-tweet-old-post/twitteroauth/autoload.php';

	$settings = fudo_top_get_settings();
	$consumer_key    = fudo_top_consumer_key();
	$consumer_secret = fudo_top_consumer_secret();

	$save_oauth = true;

	//Call request_token
	$tmp_oauth_token	= get_option('top_opt_tmp_oauth_token', true );
	$tmp_oauth_token_secret = get_option('top_opt_tmp_oauth_token_secret', true);

	// If the oauth_token is old redirect to the connect page.
	if (isset ($_REQUEST['oauth_token'] ) && $tmp_oauth_token !== $_REQUEST['oauth_token']) {
		update_option('top_opt_tmp_oauth_token', '' );
		update_option('top_opt_tmp_oauth_token_secret', '' );
		$tmp_oauth_token = '';
		$tmp_oauth_token_secret = '';

		return  __("Could not connect to Twitter. Refresh the page or try again later..", 'TweetOldPost');
	}

	// Create TwitteroAuth object with app key/secret and token key/secret from default phase
	$connection = new Abraham\TwitterOAuth\TwitterOAuth( $consumer_key, $consumer_secret, $tmp_oauth_token , $tmp_oauth_token_secret );

	// Save the access tokens. Normally these would be saved in a database for future use.
	$access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $_REQUEST['oauth_verifier']));

	//Get profile_image_url_https
	$connection = new Abraham\TwitterOAuth\TwitterOAuth( $consumer_key, $consumer_secret, $access_token['oauth_token'], $access_token['oauth_token_secret']);
	$content = $connection->get('account/verify_credentials');


	if( isset( $settings['user_id'] ) &&  $settings['user_id'] == $access_token['user_id'] ){
		$save_oauth = false;
	}else{
		if( !isset( $settings['user_id'] ) || ( isset( $settings['user_id'] ) && $settings['user_id'] == '' ) ){
			//Save temporary credentials.
			$settings['oauth_access_token']		= $access_token['oauth_token'];
			$settings['oauth_access_token_secret']	= $access_token['oauth_token_secret'];
			$settings['user_id']			= $access_token['user_id'];
			$settings['screen_name']		= $access_token['screen_name'];
			$settings['profile_image_url']		= $content->profile_image_url_https;
			$settings['author_id']			= $author_id;

			fudo_top_save_settings( $settings );
			$save_oauth = false;
		}
	}

	update_option('top_opt_tmp_oauth_token', '' );
	update_option('top_opt_tmp_oauth_token_secret', '' );
	return  '';
}


/**
 *
 * Class Fudou_TOP_ADMIN
 *
 * @since Fudousan Tweet old post Plugin 1.6.0
 */
class Fudou_TOP_ADMIN {

	public function __construct(){
		add_action( 'admin_head', array( $this, 'fudo_top_opt_head_admin' ) );
	}

	//admin css
	function fudo_top_opt_head_admin() {
		$stylesheet = plugins_url(str_replace(basename(__FILE__), "", plugin_basename(__FILE__))) . '/css/tweet-old-post.css';
		echo('<link rel="stylesheet" href="' . $stylesheet . '" type="text/css" media="screen" />');
	}


	public function top_admin() {

		global $is_fudou,$is_fudoukaiin;

		//check permission
		if (current_user_can('edit_plugins')){

			$message = null;
			$message_updated = __("Tweet Old Post Options Updated.", 'TweetOldPost');
			$save = true;


			//on authorize redirect
			if ( isset( $_GET['redirect'] ) ) {
				$message = top_fudou_twitter_oauth_redirect( FUDOU_OAUTH_CALLBACK );
			}


			//on authorize callback
			if ( isset($_GET['callback'] ) && isset($_GET['oauth_verifier']) ) {
				$message = top_fudou_twitter_oauth_callback();
				echo '<script language="javascript">window.location.href= "' . admin_url( 'admin.php?page=TweetOldPost' ) . '";</script>';
				die;
			}


			$settings = fudo_top_get_settings();

			//on deauthorize
			if (isset($_GET['top']) && $_GET['top'] == 'deauthorize') {

				$user_id = isset($_GET['user']) ? $_GET['user'] : '';

				$settings = fudo_top_get_settings();
				if( $user_id == '' ){
					$settings['oauth_access_token'] = '';
					$settings['oauth_access_token_secret'] = '';
					$settings['user_id'] = '';
					$settings['screen_name'] = '';
					$settings['profile_image_url'] = '';
					$settings['author_id'] = '';
				}

				update_option('top_opt_tmp_oauth_token', '' );
				update_option('top_opt_tmp_oauth_token_secret', '' );

				fudo_top_save_settings($settings);
				echo '<script language="javascript">window.location.href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=TweetOldPost";</script>';
				die;

		        } else if (isset($_GET['top']) && $_GET['top'] == 'reset') {
			//on reset
				echo '<div id="message" class="updated fade">';
				echo '<p>' . __("All settings have been reset. Kindly update the settings for Tweet Old Post to start tweeting again.", 'TweetOldPost') . '</p>';
				echo '</div>';
			}



			//check if username and key provided if bitly selected
			if (isset($_POST['top_opt_url_shortener'])) {
				if ($_POST['top_opt_url_shortener'] == "bit.ly") {

					//check bitly username
					if (!isset($_POST['top_opt_bitly_user'])) {
						echo '<div id="message" class="updated fade">';
						echo '<p>' . __('Please enter bit.ly username.', 'TweetOldPost') . '</p>';
						echo '</div>';
						$save = false;

					} elseif (!isset($_POST['top_opt_bitly_key'])) {
						//check bitly key
						echo '<div id="message" class="updated fade">';
						echo '<p>' . __('Please enter bit.ly API Key.', 'TweetOldPost') . '</p>';
						echo '</div>';
						$save = false;
					}else {
						//if both the good to save
						$save = true;
					}
				}
			}

			//if submit and if bitly selected its fields are filled then save
			if (isset($_POST['submit']) && $save) {

				check_admin_referer('top_TweetOldPost_admin');

				$message = $message_updated;

				//TOP admin URL (current url)
				if (isset($_POST['top_opt_admin_url'])) {
					update_option('top_opt_admin_url', $_POST['top_opt_admin_url']);
				}

				//what to tweet 
				if (isset($_POST['top_opt_tweet_type'])) {
					update_option( 'top_opt_tweet_type', esc_attr( $_POST['top_opt_tweet_type'] ) );
				}

				//additional data
				if (isset($_POST['top_opt_add_text'])) {
					update_option( 'top_opt_add_text', esc_attr( $_POST['top_opt_add_text'] ) );
				}

				//additional last data
				if (isset($_POST['top_opt_add_last_text'])) {
					update_option( 'top_opt_add_last_text', esc_attr( $_POST['top_opt_add_last_text'] ) );
				}


				//include link
				if (isset($_POST['top_opt_include_link'])) {
					update_option('top_opt_include_link', $_POST['top_opt_include_link']);
				}

				//fetch url from custom field?
				if (isset($_POST['top_opt_custom_url_option'])) {
					update_option('top_opt_custom_url_option', true);
				} else {

					update_option('top_opt_custom_url_option', false);
				}

				//custom field to fetch URL from 
				if (isset($_POST['top_opt_custom_url_field'])) {
					update_option( 'top_opt_custom_url_field', esc_attr( $_POST['top_opt_custom_url_field'] ) );
				} else {
					update_option( 'top_opt_custom_url_field', '' );
				}

				//use URL shortner?
				if (isset($_POST['top_opt_use_url_shortner'])) {
					update_option('top_opt_use_url_shortner', true);
				} else {
					update_option('top_opt_use_url_shortner', false);
				}

				//url shortener to use
				if (isset($_POST['top_opt_url_shortener'])) {
					update_option( 'top_opt_url_shortener', esc_attr( $_POST['top_opt_url_shortener'] ) );
					if ($_POST['top_opt_url_shortener'] == "bit.ly") {
						if (isset($_POST['top_opt_bitly_user'])) {
							update_option('top_opt_bitly_user', $_POST['top_opt_bitly_user']);
						}
						if (isset($_POST['top_opt_bitly_key'])) {
							update_option('top_opt_bitly_key', $_POST['top_opt_bitly_key']);
						}
					}
				}

				//hashtags option
				if (isset($_POST['top_opt_custom_hashtag_option'])) {
					update_option('top_opt_custom_hashtag_option', $_POST['top_opt_custom_hashtag_option']);
				} else {
					update_option('top_opt_custom_hashtag_option', "nohashtag");
				}

				//post date
				if (isset($_POST['top_opt_date_modified'])) {
					update_option('top_opt_date_modified', $_POST['top_opt_date_modified']);
				} else {
					update_option('top_opt_date_modified', "post_date");
				}

				//use inline hashtags
				if (isset($_POST['top_opt_use_inline_hashtags'])) {
					update_option('top_opt_use_inline_hashtags', true);
				} else {
					update_option('top_opt_use_inline_hashtags', false);
				}

				 //hashtag length
				if (isset($_POST['top_opt_hashtag_length'])) {
					update_option('top_opt_hashtag_length', $_POST['top_opt_hashtag_length']);
				} else {
					update_option('top_opt_hashtag_length', 0);
				}

				//custom field name to fetch hashtag from 
				if (isset($_POST['top_opt_custom_hashtag_field'])) {
					update_option('top_opt_custom_hashtag_field', $_POST['top_opt_custom_hashtag_field']);
				} else {
					update_option('top_opt_custom_hashtag_field', '');
				}

				//default hashtags for tweets
				if (isset($_POST['top_opt_hashtags'])) {
					update_option('top_opt_hashtags', $_POST['top_opt_hashtags']);
				} else {
					update_option('top_opt_hashtags', '');
				}

				//tweet interval 
				if (isset($_POST['top_opt_interval'])) {
					if (is_numeric($_POST['top_opt_interval']) && $_POST['top_opt_interval'] > 0) {
						update_option('top_opt_interval', $_POST['top_opt_interval']);
					} else {
						update_option('top_opt_interval', "4");
					}
				}

				//minimum post age to tweet
				if (isset($_POST['top_opt_age_limit'])) {
					if (is_numeric($_POST['top_opt_age_limit']) && $_POST['top_opt_age_limit'] >= 0) {
						update_option('top_opt_age_limit', $_POST['top_opt_age_limit']);
					} else {
						update_option('top_opt_age_limit', "30");
					}
				}

				//maximum post age to tweet
				if (isset($_POST['top_opt_max_age_limit'])) {
					if (is_numeric($_POST['top_opt_max_age_limit']) && $_POST['top_opt_max_age_limit'] > 0) {
						update_option('top_opt_max_age_limit', $_POST['top_opt_max_age_limit']);
					} else {
						update_option('top_opt_max_age_limit', "0");
					}
				}

				//number of posts to tweet
				if (isset($_POST['top_opt_no_of_tweet'])) {
					if (is_numeric($_POST['top_opt_no_of_tweet']) && $_POST['top_opt_no_of_tweet'] > 0) {
						update_option('top_opt_no_of_tweet', $_POST['top_opt_no_of_tweet']);
					} else {
						update_option('top_opt_no_of_tweet', "1");
					}
				}



				//type of post to tweet
				if (isset($_POST['top_opt_post_type'])) {
					update_option('top_opt_post_type', $_POST['top_opt_post_type']);
				}


				//type of post to tweet
				if ( isset($_POST['top_opt_post_type_post'])) {
					update_option('top_opt_post_type_post', true );
				}else{
					update_option('top_opt_post_type_post', false );
				}

				//type of page to tweet
				if ( isset($_POST['top_opt_post_type_page'])) {
					update_option('top_opt_post_type_page', true );
				}else{
					update_option('top_opt_post_type_page', false );
				}

				//type of fudo to tweet
				if ( isset($_POST['top_opt_post_type_fudo'])) {
					update_option('top_opt_post_type_fudo', true );
				}else{
					update_option('top_opt_post_type_fudo', false );
				}




				//option to enable image
				if ( isset($_POST['top_enable_image'])) {
					update_option('top_enable_image', true);
				}else{
					update_option('top_enable_image', false);
				}

				//option to enable image count
				if (isset($_POST['top_enable_image_su'])) {
					if (is_numeric($_POST['top_enable_image_su']) && $_POST['top_enable_image_su'] > 0) {
						update_option('top_enable_image_su', $_POST['top_enable_image_su']);
					} else {
						update_option('top_enable_image_su', "4");
					}
				}


				//fudou categories to omit from tweet
				$tax_input_bukken = '';
				if( $is_fudou ){
					if (isset($_POST['tax_input'])) {
						$tax_input = $_POST['tax_input'];

						$tax_input_bukken = implode(',', $tax_input['bukken']);
					}
				}

				//categories to omit from tweet
				if (isset($_POST['post_category'])) {
					$selected_category = implode(',', $_POST['post_category'] );

					if( $tax_input_bukken ){
						$selected_category .= ',' . $tax_input_bukken;
					}

					update_option('top_opt_omit_cats', $selected_category );
				} else {
					update_option('top_opt_omit_cats', '');
				}

				//successful update message
				print('
					<div id="message" class="updated fade">
						<p>' . __('Tweet Old Post Options Updated.', 'TweetOldPost') . '</p>
					</div>');
				} elseif (isset($_POST['tweet'])) {
		        		//tweet now clicked
					//$tweet_msg = fudo_top_opt_tweet_old_post();
					$tweet_msg = fudo_top_generate_query( true , '', 2 );
					print('
					<div id="message" class="updated fade">
						<p>' . __($tweet_msg, 'TweetOldPost') . '</p>
					</div>');
				} elseif (isset($_POST['reset'])) {
					fudo_top_reset_settings();
						echo '<script language="javascript">window.location.href= "' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=TweetOldPost&top=reset";</script>';
					die;
				}


			//set up data into fields from db
				
				//what to tweet?
				$tweet_type = get_option('top_opt_tweet_type');
				if (!isset($tweet_type)) {
					$tweet_type = "title";
				}

				//additional text
				$additional_text = get_option('top_opt_add_text');
				if (!isset($additional_text)) {
					$additional_text = "";
				}

				//additional last text
				$additional_last_text = get_option('top_opt_add_last_text');
				if (!isset($additional_last_text)) {
					$additional_last_text = "";
				}


				//include link in tweet
				$include_link = get_option('top_opt_include_link');
				if (!isset($include_link)) {
					$include_link = "no";
				}

				//use custom field to fetch url
				$custom_url_option = get_option('top_opt_custom_url_option');
				if (!isset($custom_url_option)) {
					$custom_url_option = "";
				} elseif ($custom_url_option)
					$custom_url_option = "checked";
				else
					$custom_url_option="";

				//custom field name for url
				$custom_url_field = get_option('top_opt_custom_url_field');
				if (!isset($custom_url_field)) {
					$custom_url_field = "";
				}

				//use url shortner?
				$use_url_shortner = get_option('top_opt_use_url_shortner');
				if (!isset($use_url_shortner)) {
					$use_url_shortner = "";
				} elseif ($use_url_shortner)
					$use_url_shortner = "checked";
				else
					$use_url_shortner="";

				//url shortner
				$url_shortener = get_option('top_opt_url_shortener');
				if (!isset($url_shortener)) {
					$url_shortener = "";
				}

				//bitly key
				$bitly_api = get_option('top_opt_bitly_key');
				if (!isset($bitly_api)) {
					$bitly_api = "";
				}

				//bitly username
				$bitly_username = get_option('top_opt_bitly_user');
				if (!isset($bitly_username)) {
					$bitly_username = "";
				}

				//hashtag option
				$custom_hashtag_option = get_option('top_opt_custom_hashtag_option');
				if (!isset($custom_hashtag_option)) {
					$custom_hashtag_option = "nohashtag";
				}

				//post date
				$top_opt_date_modified = get_option('top_opt_date_modified');
				if (!isset($top_opt_date_modified)) {
					$top_opt_date_modified = "post_date";
				}


				//use inline hashtag
				$use_inline_hashtags = get_option('top_opt_use_inline_hashtags');
				if (!isset($use_inline_hashtags)) {
					$use_inline_hashtags = "";
				} elseif ($use_inline_hashtags)
					$use_inline_hashtags = "checked";
				else
					$use_inline_hashtags="";

				 //hashtag length
				$hashtag_length = get_option('top_opt_hashtag_length');
				if (!isset($hashtag_length)) {
					$hashtag_length = "20";
				}

				//custom field 
				$custom_hashtag_field = get_option('top_opt_custom_hashtag_field');
				if (!isset($custom_hashtag_field)) {
					$custom_hashtag_field = "";
				}

				//default hashtag
				$twitter_hashtags = get_option('top_opt_hashtags');
				if (!isset($twitter_hashtags)) {
					$twitter_hashtags = "";
				}

				//interval
				$interval = get_option('top_opt_interval');
				if (!(isset($interval) && is_numeric($interval))) {
					$interval = FUDOU_TOP_OPT_INTERVAL;
				}

				//min age limit
				$ageLimit = get_option('top_opt_age_limit');
				if (!(isset($ageLimit) && is_numeric($ageLimit))) {
					$ageLimit = FUDOU_TOP_OPT_AGE_LIMIT;
				}

				//max age limit
				$maxAgeLimit = get_option('top_opt_max_age_limit');
				if (!(isset($maxAgeLimit) && is_numeric($maxAgeLimit))) {
					$maxAgeLimit = FUDOU_TOP_OPT_MAX_AGE_LIMIT;
				}

				//number of post to tweet
				$top_opt_no_of_tweet = get_option('top_opt_no_of_tweet');
				if (!(isset($top_opt_no_of_tweet) && is_numeric($top_opt_no_of_tweet))) {
					$top_opt_no_of_tweet = "1";
				}

				//type of post to tweet
				$top_opt_post_type = get_option('top_opt_post_type');
				if (!isset($top_opt_post_type)) {
					$top_opt_post_type = "post";
				}

				//type of top page to tweet
				if ( get_option('top_opt_post_type_toppage')  ) {
					$top_opt_post_type_toppage = 'checked="checked"';
				}else{
					$top_opt_post_type_toppage = '';
				}
				//type of post to tweet
				if ( get_option('top_opt_post_type_post')  ) {
					$top_opt_post_type_post = 'checked="checked"';
				}else{
					$top_opt_post_type_post = '';
				}
				if ( get_option('top_opt_post_type_page')  ) {
					$top_opt_post_type_page = 'checked="checked"';
				}else{
					$top_opt_post_type_page = '';
				}
				if ( get_option('top_opt_post_type_fudo') ) {
					$top_opt_post_type_fudo = 'checked="checked"';
				}else{
					$top_opt_post_type_fudo = '';
				}

				//check enable image
				$top_enable_image = get_option('top_enable_image');
				if (!isset($top_enable_image)) {
					$top_enable_image = "";
				} elseif ($top_enable_image)
					$top_enable_image = "checked";
				else
					$top_enable_image="";

				//check enable image count
				$top_enable_image_su = get_option('top_enable_image_su');
				if (!(isset($top_enable_image_su) && is_numeric($top_enable_image_su))) {
					$top_enable_image_su = "4";
				}

				//set omitted categories
				$omitCats = get_option('top_opt_omit_cats');
				if (!isset($omitCats)) {
					$omitCats = '';
				}

				?>

				<div class="wrap">
					<h2><?php echo __('Fudousan Tweet old post', 'TweetOldPost'); ?></h2>
				<div id="poststuff">

				<div id="post-body">
				<div id="post-body-content">

					<form id="top_opt" name="top_TweetOldPost" action="<?php echo admin_url( 'admin.php?page=TweetOldPost' ); ?>" method="post">
						<input type="hidden" name="top_opt_action" value="top_opt_update_settings" />

						<fieldset class="options">
							<div class="option">
								<label for="top_opt_twitter_username"><?php echo __('Account Login', 'TweetOldPost'); ?></label>
								<div id="profile-box">

				<?php
								wp_nonce_field('top_TweetOldPost_admin'); 

								if ( !$settings["oauth_access_token"] ) {
									/* Build an image link to start the redirect process. */
									echo '<a href="' . admin_url( 'admin.php?page=TweetOldPost&redirect' ) . '"><img src="' . plugins_url(str_replace(basename(__FILE__), "", plugin_basename(__FILE__))) . '/images/darker.png" alt="Sign in with Twitter"/></a>';

								} else {
									if( isset($settings["profile_image_url"] )  ){
										echo '<img class="avatar" src="' . $settings["profile_image_url"] . '" alt="" />';
									}
									if( isset($settings["screen_name"] ) ){
										echo '<h4>' . $settings["screen_name"] . '</h4>';
									}

									echo '<p>' . __('Your account has  been authorized.', 'TweetOldPost') . ' 　<a href="' . admin_url( 'admin.php?page=TweetOldPost' )  . '&top=deauthorize" onclick=\'return confirm("' . __('Are you sure you want to deauthorize your Twitter account?', 'TweetOldPost') . '");\'> (' . __('Click to deauthorize', 'TweetOldPost') . ')</a> <br/>	</p>
										<div class="retweet-clear"></div>';
							        }


				print('
								</div>
							</div>

							<div class="option grp">
								<label for="top_opt_tweet_type">' . __('Tweet Content', 'TweetOldPost') . '<br /><span class="desc">What do you want to share?</span></label>
								<select id="top_opt_tweet_type" name="top_opt_tweet_type" style="width:150px">
									<option value="title" ' . fudou_top_opt_optionselected("title", $tweet_type) . '>' . __('Title Only ', 'TweetOldPost') . ' </option>
									<option value="body" ' . fudou_top_opt_optionselected("body", $tweet_type) . '>' . __('Body Only ', 'TweetOldPost') . ' </option>
									<option value="titlenbody" ' . fudou_top_opt_optionselected("titlenbody", $tweet_type) . '>' . __('Title & Body ', 'TweetOldPost') . ' </option>
								</select>
	                                                        
							</div>


							<!-- 追加テキスト -->
							<div class="option grp">
								<label for="top_opt_add_text">' . __('Additional Top Text', 'TweetOldPost') . '<br /><span class="desc">Text added to top your auto posts.</span></label>
								<input type="text" name="top_opt_add_text" id="top_opt_add_text" value="' . $additional_text . '" autocomplete="off" />
							</div>

							<div class="option grp">
								<label for="top_opt_add_last_text">' . __('Additional Last Text', 'TweetOldPost') . '<br /><span class="desc">Text added Last to your auto posts.</span></label>
								<input type="text" name="top_opt_add_last_text" id="top_opt_add_last_text" value="' . $additional_last_text . '" autocomplete="off" />
							</div>


							<!-- リンク -->
							<div class="option grp">
								<label for="top_opt_include_link">' . __('Include Link', 'TweetOldPost') . '<br /><span class="desc">Include a link to your post?</span></label>
								<select id="top_opt_include_link" name="top_opt_include_link" style="width:150px" onchange="javascript:showURLOptions()">
									<option value="false" ' . fudou_top_opt_optionselected("false", $include_link) . '> ' . __('No ', 'TweetOldPost') . '</option>
									<option value="true" '  . fudou_top_opt_optionselected("true", $include_link) . '> ' . __('Yes ', 'TweetOldPost') . '</option>
								</select>
							</div>

							<div id="urloptions" style="display:none">
		                                                <div class="option">
									<label for="top_opt_custom_url_option">' . __('Fetch URL from custom field', 'TweetOldPost') . '</label>
									<input onchange="return showCustomField();" type="checkbox" name="top_opt_custom_url_option" ' . $custom_url_option . ' id="top_opt_custom_url_option" />
								</div>

								<div id="customurl" style="display:none;">
									<div class="option">
										<label for="top_opt_custom_url_field">' . __('Custom field name to fetch URL to be tweeted with post', 'TweetOldPost') . '</label>
										<input type="text" name="top_opt_custom_url_field" id="top_opt_custom_url_field" value="' . $custom_url_field . '" autocomplete="off" />
									</div>
								</div>

								<div class="option">
									<label for="top_opt_use_url_shortner">' . __('Use URL shortner?', 'TweetOldPost') . '<br /><span class="desc">Shorten the link to your post.</span></label>
									<input onchange="return showshortener()" type="checkbox" name="top_opt_use_url_shortner" id="top_opt_use_url_shortner" ' . $use_url_shortner . ' />
									
								</div>

								<div  id="urlshortener">
									<div class="option">
										<label for="top_opt_url_shortener">' . __('URL Shortener Service', 'TweetOldPost') . '</label>
										<select name="top_opt_url_shortener" id="top_opt_url_shortener" onchange="javascript:showURLAPI()" style="width:100px;">
				');

											//WordPress shortlink or JetPack
											if (function_exists('wp_get_shortlink')) {
												if (function_exists('wpme_get_shortlink')) {
													echo '<option value="WP.me" ' . fudou_top_opt_optionselected('WP.me', $url_shortener) . '>' . __('WP.me', 'TweetOldPost') . '</option>';
												}else{
													echo '<option value="shortlink" ' . fudou_top_opt_optionselected('shortlink', $url_shortener) . '>' . __('shortlink', 'TweetOldPost') . '</option>';
												}
											}
				print('
												<option value="is.gd" '  . fudou_top_opt_optionselected('is.gd', $url_shortener) . '>' . __('is.gd', 'TweetOldPost') . '</option>
												<option value="bit.ly" ' . fudou_top_opt_optionselected('bit.ly', $url_shortener) . '>' . __('bit.ly', 'TweetOldPost') . '</option>
												<option value="su.pr" '  . fudou_top_opt_optionselected('su.pr', $url_shortener) . '>' . __('su.pr', 'TweetOldPost') . '</option>
												<option value="tr.im" '  . fudou_top_opt_optionselected('tr.im', $url_shortener) . '>' . __('tr.im', 'TweetOldPost') . '</option>
												<option value="3.ly" '   . fudou_top_opt_optionselected('3.ly', $url_shortener) . '>' . __('3.ly', 'TweetOldPost') . '</option>
												<option value="u.nu" '   . fudou_top_opt_optionselected('u.nu', $url_shortener) . '>' . __('u.nu', 'TweetOldPost') . '</option>
												<option value="1click.at" ' . fudou_top_opt_optionselected('1click.at', $url_shortener) . '>' . __('1click.at', 'TweetOldPost') . '</option>
												<option value="tinyurl" ' . fudou_top_opt_optionselected('tinyurl', $url_shortener) . '>' . __('tinyurl', 'TweetOldPost') . '</option>
										</select>
									</div>
									<div id="showDetail" style="display:none">
										<div class="option">
											<label for="top_opt_bitly_user">' . __('bit.ly Username', 'TweetOldPost') . '</label>
											<input type="text" name="top_opt_bitly_user" id="top_opt_bitly_user" value="' . $bitly_username . '" autocomplete="off" />
										</div>
										
										<div class="option">
											<label for="top_opt_bitly_key">' . __('bit.ly API Key', 'TweetOldPost') . '</label>
											<input type="text" name="top_opt_bitly_key" id="top_opt_bitly_key" value="' . $bitly_api . '" autocomplete="off" />
										</div>
									</div>
		                                                </div>
							</div>

							<!-- 画像 -->
	                                                <div class="option grp">
								<label for="top_enable_image">' . __('include image in tweet: ', 'TweetOldPost') . '</label>
								<input type="checkbox" name="top_enable_image" id="top_enable_image" ' . $top_enable_image . '  onchange="javascript:return showEnableImageField()" /> 
								' . __('Large Image in the Content or thumbnail are eligible.', 'TweetOldPost') . '
							</div>

							<div id="enable_image" style="display:none;">
		                                                <div class="option">
									<label for="top_enable_image_su">' . __('include image count in tweet: ', 'TweetOldPost') . '</label>
									<select id="top_enable_image_su" name="top_enable_image_su">
										<option value="1" ' . fudou_top_opt_optionselected(1, $top_enable_image_su) . '>1</option>
										<option value="2" ' . fudou_top_opt_optionselected(2, $top_enable_image_su) . '>2</option>
										<option value="3" ' . fudou_top_opt_optionselected(3, $top_enable_image_su) . '>3</option>
										<option value="4" ' . fudou_top_opt_optionselected(4, $top_enable_image_su) . '>4</option>
									</select>
								</div>
							</div>


							<!-- ハッシュタグ -->
	                                                <div class="option grp">
								<label for="top_opt_custom_hashtag_option">' . __('#Hashtags', 'TweetOldPost') . '<br /><span class="desc">Include #hashtags in your auto posts.</span></label>
								<select name="top_opt_custom_hashtag_option" id="top_opt_custom_hashtag_option" onchange="javascript:return showHashtagCustomField()">
										<option value="nohashtag" '  . fudou_top_opt_optionselected('nohashtag', $custom_hashtag_option) . '>' . __('Don`t add any hashtags', 'TweetOldPost') . '</option>
										<option value="common" '     . fudou_top_opt_optionselected('common', $custom_hashtag_option) . '>' . __('Common hashtag for all tweets', 'TweetOldPost') . '</option>    
										<option value="categories" ' . fudou_top_opt_optionselected('categories', $custom_hashtag_option) . '>' . __('Create hashtags from categories', 'TweetOldPost') . '</option>
										<option value="tags" '       . fudou_top_opt_optionselected('tags', $custom_hashtag_option) . '>' . __('Create hashtags from tags', 'TweetOldPost') . '</option>
										<option value="custom" '     . fudou_top_opt_optionselected('custom', $custom_hashtag_option) . '>' . __('Get hashtags from custom fields', 'TweetOldPost') . '</option>
								</select>
							</div>

							<div id="inlinehashtag" style="display:none;">
								<div class="option">
									<label for="top_opt_use_inline_hashtags">' . __('Use inline hashtags', 'TweetOldPost') . '</label>
									<input type="checkbox" name="top_opt_use_inline_hashtags" id="top_opt_use_inline_hashtags" ' . $use_inline_hashtags . ' /> 
								</div>

		                                                <div class="option">
									<label for="top_opt_hashtag_length">' . __('Maximum Hashtag length', 'TweetOldPost') . '</label>
									<input type="text" name="top_opt_hashtag_length" id="top_opt_hashtag_length" value="' . $hashtag_length . '" /> 
									' . __('Set this to 0 to include all hashtags', 'TweetOldPost') . '
								</div>
							</div>

							<div id="customhashtag" style="display:none;">
								<div class="option">
									<label for="top_opt_custom_hashtag_field">' . __('Custom field name', 'TweetOldPost') . '</label>
									<input type="text" name="top_opt_custom_hashtag_field" id="top_opt_custom_hashtag_field" value="' . $custom_hashtag_field . '" autocomplete="off" />
									' . __('fetch hashtags from this custom field', 'TweetOldPost') . '
								</div>
								
								</div>
		                                                <div id="commonhashtag" style="display:none;">
								<div class="option">
									<label for="top_opt_hashtags">' . __('Common #hashtags for your tweets', 'TweetOldPost') . '</label>
									<input type="text" name="top_opt_hashtags" id="top_opt_hashtags" value="' . $twitter_hashtags . '" autocomplete="off" />
									' . __('Include #, like #thoughts', 'TweetOldPost') . '
								</div>
							</div>

							<!-- ツイート間隔 -->
							<div class="option  grp">
								<label for="top_opt_interval">' . __('Minimum interval between tweets', 'TweetOldPost') . ' <br /><span class="desc">What should be minimum time between your tweets?</span></label>
								<input type="text" style="width:50px" id="top_opt_interval" maxlength="5" value="' . $interval . '" name="top_opt_interval" /> ' . __('Hour / Hours', 'TweetOldPost') . ' (' . __('If set to 0 it will take default as 24 hours', 'TweetOldPost') . ')
							</div>
							
							<div class="option grp">
								<label for="top_opt_age_limit">' . __('Minimum age of post to be eligible for tweet', 'TweetOldPost') . '<br /><span class="desc">Include post in tweets if at least this age.</span></label>
								<input type="text" style="width:50px" id="top_opt_age_limit" maxlength="5" value="' . $ageLimit . '" name="top_opt_age_limit" /> ' . __('Day / Days', 'TweetOldPost') . ' (' . __('enter 0 for today', 'TweetOldPost') . ')
							</div>

							<div class="option">
								<label for="top_opt_max_age_limit">' . __('Maximum age of post to be eligible for tweet', 'TweetOldPost') . '<br /><span class="desc">Don\'t include posts older than this.</span></label>
								<input type="text" style="width:50px" id="top_opt_max_age_limit" maxlength="5" value="' . $maxAgeLimit . '" name="top_opt_max_age_limit" /> ' . __('Day / Days', 'TweetOldPost') . ' (' . __('If you dont want to use this option enter 0 or leave blank', 'TweetOldPost') . ')<br/>
								' . __('Post older than specified days will not be tweeted.', 'TweetOldPost') . '
							</div>

							<div class="option">
								<label for="top_opt_date_modified">' . __('Date Target of Post for Tweet', 'TweetOldPost') . '<br /><span class="desc">Post Date or  Post Modified for Tweet.</span></label>
								<select name="top_opt_date_modified" id="top_opt_custom_hashtag_option">
										<option value="post_date"     ' . fudou_top_opt_optionselected('post_date', $top_opt_date_modified) . '>' . __('Post Date', 'TweetOldPost') . '</option>
										<option value="post_modified" ' . fudou_top_opt_optionselected('post_modified', $top_opt_date_modified) . '>' . __('Post Modified', 'TweetOldPost') . '</option>    
								</select>
							</div>

							<!-- 一回のツイート投稿数 -->
	                                                <div class="option grp">
								<label for="top_opt_no_of_tweet">' . __('Number Of Posts To Tweet', 'TweetOldPost') . '<br/><span class="desc">Number of tweets to share each time.</span></label>
								<input type="text" style="width:50px" id="top_opt_no_of_tweet" value="' . $top_opt_no_of_tweet . '" name="top_opt_no_of_tweet" /></b>  
							</div>


							<!-- 投稿タイプ -->
						    	<div class="option category grp">
								<table>
									<tr>
									<td>
										<label for="top_opt_post_type" class="catlabel">' . __('Tweet Post Type', 'TweetOldPost') . '<br/> <span class="desc">What type of items do you want to share?</span></label>
									</td>
									<td>
									<div class="checkboxlist">
										<ul id="postchecklist" class="list:category categorychecklist form-no-clear">

											<li><label for="top_opt_post_type_post"><input type="checkbox" name="top_opt_post_type_post" id="top_opt_post_type_post" value="1" '.$top_opt_post_type_post.' />  ' . __('post', 'TweetOldPost') . ' </label></li>
											<li><label for="top_opt_post_type_page"><input type="checkbox" name="top_opt_post_type_page" id="top_opt_post_type_page" value="1" '.$top_opt_post_type_page.'  /> ' . __('page', 'TweetOldPost') . ' </label></li>
				');
											if( $is_fudou ){
												echo '<li><label for="top_opt_post_type_fudo"><input type="checkbox" name="top_opt_post_type_fudo" id="top_opt_post_type_fudo" value="1" '.$top_opt_post_type_fudo.'  /> ' . __('fudo', 'TweetOldPost') . ' </label></li>';
											}
				?>
										</ul>
									</div>
									</td>
									</tr>
								</table>
							</div>

						    	<div class="option category grp">
								<table>
								<tr>
								<td><label class="catlabel"><?php echo  __('Categories to Omit from tweets', 'TweetOldPost'); ?><br/>
									<span class="desc">Check categories not to share.</span></label>
								</td>
								<td>

					<?php
									echo '<div class="checkboxlist">';
									echo __('POST category', 'TweetOldPost');
									echo '<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">';
								        wp_category_checklist(0, 0, explode(',', $omitCats));
									echo '</ul>';
									echo '</div>';

								if( $is_fudou ){
									echo '<br /><div class="checkboxlist">';
									echo __('fudo category', 'TweetOldPost');
									echo '<ul id="taxonomychecklist" class="list:category categorychecklist form-no-clear">';
								 	$terms_checklist = array(
										'descendants_and_self' => 0,
										'selected_cats' => explode(',', $omitCats),
										'popular_cats' => false,
										'walker' => null,
										'taxonomy' => 'bukken',
										'checked_ontop' =>  true,
									);
									wp_terms_checklist(0, $terms_checklist);
									echo '</ul>';
									echo '</div>';
								}
					?>
								</td></tr></table>
								</div>
							</div>
						</fieldset>

						<p class="submit">
							<input type="submit" name="submit" onclick="javascript:return validate()" value="<?php echo __('Update Tweet Old Post Options', 'TweetOldPost'); ?>" />
							<input type="submit" name="tweet" value="<?php echo __('Tweet Now', 'TweetOldPost'); ?>" />
	                                                <input type="submit" name="reset" onclick="javascript:return resetSettings();" value="<?php echo __('Reset Settings', 'TweetOldPost'); ?>" />
						</p>
					</form>
				</div>
				</div>
				</div>


					<script language="javascript" type="text/javascript">

					var top_image_uploaders = new Array ( 
						"top_opt_post_type_toppage_image1",
						"top_opt_post_type_toppage_image2",
						"top_opt_post_type_toppage_image3",
						"top_opt_post_type_toppage_image4"
					);

					function showURLAPI(){
						var urlShortener=document.getElementById("top_opt_url_shortener").value;
						if(urlShortener=="bit.ly"){
							document.getElementById("showDetail").style.display="block";
							
						}else{
							document.getElementById("showDetail").style.display="none";
						}
					}

					function showEnableImageField(){
						if(document.getElementById("top_enable_image").checked){
							document.getElementById("enable_image").style.display="block";
						}else{
							document.getElementById("enable_image").style.display="none";
						}
					}


					function validate(){
						if(document.getElementById("showDetail").style.display=="block" && document.getElementById("top_opt_url_shortener").value=="bit.ly"){
							if(trim(document.getElementById("top_opt_bitly_user").value)==""){
								alert("Please enter bit.ly username.");
								document.getElementById("top_opt_bitly_user").focus();
								return false;
							}

							if(trim(document.getElementById("top_opt_bitly_key").value)==""){
								alert("Please enter bit.ly API key.");
								document.getElementById("top_opt_bitly_key").focus();
								return false;
							}
						}

						if(trim(document.getElementById("top_opt_interval").value) != "" && !isNumber(trim(document.getElementById("top_opt_interval").value)))	{
							alert("Enter only numeric in Minimum interval between tweet");
							document.getElementById("top_opt_interval").focus();
							return false;
					        }

						if(trim(document.getElementById("top_opt_no_of_tweet").value) != "" && !isNumber(trim(document.getElementById("top_opt_no_of_tweet").value))){
					            alert("Enter only numeric in Number Of Posts To Tweet");
							document.getElementById("top_opt_no_of_tweet").focus();
							return false;
					        }

						 if(trim(document.getElementById("top_opt_age_limit").value) != "" && !isNumber(trim(document.getElementById("top_opt_age_limit").value))){
							alert("Enter only numeric in Minimum age of post");
							document.getElementById("top_opt_age_limit").focus();
							return false;
						}
						if(trim(document.getElementById("top_opt_max_age_limit").value) != "" && !isNumber(trim(document.getElementById("top_opt_max_age_limit").value))){
					            alert("Enter only numeric in Maximum age of post");
							document.getElementById("top_opt_max_age_limit").focus();
							return false;
					        }
						if(trim(document.getElementById("top_opt_max_age_limit").value) != "" && trim(document.getElementById("top_opt_max_age_limit").value) != 0){

							if(eval(document.getElementById("top_opt_age_limit").value) > eval(document.getElementById("top_opt_max_age_limit").value)){
								alert("Post max age limit cannot be less than Post min age iimit");
								document.getElementById("top_opt_age_limit").focus();
								return false;
							}
						}
					}

					function trim(stringToTrim) {
						return stringToTrim.replace(/^\s+|\s+$/g,"");
					}



					function tweet_top_page(){
						if(document.getElementById("top_opt_post_type_toppage").checked){
							document.getElementById("toppage_image").style.display="block";
						}else{
							document.getElementById("toppage_image").style.display="none";
						}
					}

					function showCustomField(){
						if(document.getElementById("top_opt_custom_url_option").checked){
							document.getElementById("customurl").style.display="block";
						}else{
							document.getElementById("customurl").style.display="none";
						}
					}

					function showHashtagCustomField(){
						if(document.getElementById("top_opt_custom_hashtag_option").value=="custom"){
							document.getElementById("customhashtag").style.display="block";
							document.getElementById("commonhashtag").style.display="none";
							document.getElementById("inlinehashtag").style.display="block";
						}else if(document.getElementById("top_opt_custom_hashtag_option").value=="common"){
							document.getElementById("customhashtag").style.display="none";
							document.getElementById("commonhashtag").style.display="block";
							document.getElementById("inlinehashtag").style.display="block";
						}else if(document.getElementById("top_opt_custom_hashtag_option").value=="nohashtag"){
							document.getElementById("customhashtag").style.display="none";
							document.getElementById("commonhashtag").style.display="none";
							document.getElementById("inlinehashtag").style.display="none";
						}else{
							document.getElementById("inlinehashtag").style.display="block";
							document.getElementById("customhashtag").style.display="none";
							document.getElementById("commonhashtag").style.display="none";
						}
					}

					function showURLOptions(){
						if(document.getElementById("top_opt_include_link").value=="true"){
							document.getElementById("urloptions").style.display="block";
						}else{
							document.getElementById("urloptions").style.display="none";
						}
					}

					function isNumber(val){
					    if(isNaN(val)){
					        return false;
					    }else{
					        return true;
					    }
					}

					function showshortener(){
						if((document.getElementById("top_opt_use_url_shortner").checked)){
							document.getElementById("urlshortener").style.display="block";
						}else{
							document.getElementById("urlshortener").style.display="none";
						}
					}

					function setFormAction(){
						if(document.getElementById("top_opt_admin_url").value == ""){
							var loc=location.href;
							var and_count = loc.lastIndexOf("&");

							if( and_count > 0 ){
								loc = loc.substring( 0,and_count );
							}
							 document.getElementById("top_opt_admin_url").value=loc;
							document.getElementById("top_opt").action=loc;
						}else{
							document.getElementById("top_opt").action=document.getElementById("top_opt_admin_url").value;
						}
					}

					function resetSettings(){
						var re = confirm("<?php echo __('This will reset all the setting, including your account, omitted categories, and your excluded posts. \nAre you sure you want to reset all the settings?', 'TweetOldPost'); ?>");
						if(re==true){
							document.getElementById("top_opt").action=location.href;
							return true;
						}else{
							return false;
						}
					}

				//	setFormAction();
					showURLAPI();
					showEnableImageField()
					showshortener();
				//	tweet_top_page();
					showCustomField();
					showHashtagCustomField();
					showURLOptions();
					</script>
			<?php

		} else {
			print('
				<div id="message" class="updated fade">
					<p>' . __('You do not have enough permission to set the option. Please contact your admin.', 'TweetOldPost') . '</p>
				</div>
			');
		}
	}

}	//class Fudou_TOP_ADMIN
