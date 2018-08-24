<?php
/**
 * Fudousan Tweet old post Core.
 *
 * @package Fudousan Tweet old post
 * @subpackage Fudousan Plugin
 * Version: 1.6.1
 */

function fudo_top_currentPageURL() {
	if(!isset($_SERVER['REQUEST_URI'])){
		$serverrequri = $_SERVER['PHP_SELF'];
	}else{
		$serverrequri =    $_SERVER['REQUEST_URI'];
	}
	$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
	$protocol = fudo_top_strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
	return $protocol."://".$_SERVER['SERVER_NAME'].$port.$serverrequri;

}
function fudo_top_strleft($s1, $s2) {
	return substr($s1, 0, strpos($s1, $s2));
}


//get random post and tweet
function fudo_top_opt_tweet_old_post() {
	return fudo_top_generate_query();
}
//get random post and tweet
function fudo_top_generate_query( $can_requery = true , $designation_post = '', $tweet_sleep = FUDOU_TOP_TWEET_SLEEP ){

	global $wpdb;
	global $is_fudou,$is_fudoukaiin;

	$rtrn_msg="";
	$omitCats	= get_option('top_opt_omit_cats');
	$maxAgeLimit	= get_option('top_opt_max_age_limit');
	$ageLimit	= get_option('top_opt_age_limit');
	$exposts	= get_option('top_opt_excluded_post');
	$exposts	= preg_replace('/,,+/', ',', $exposts);
	$top_opt_post_type	= get_option('top_opt_post_type');
	$top_opt_no_of_tweet	= get_option('top_opt_no_of_tweet');

	$top_opt_tweeted_posts = array();
	$top_opt_tweeted_posts = get_option('top_opt_tweeted_posts');

	//抽出期間対象
	$top_opt_date_modified = get_option('top_opt_date_modified');
	if( !$top_opt_date_modified ){
		$top_opt_date_modified = 'post_date';
	}

	//ツイート済
	if(!$top_opt_tweeted_posts){
		$top_opt_tweeted_posts = array();
	}
	if($top_opt_tweeted_posts != null){
		$already_tweeted = implode(",", $top_opt_tweeted_posts);
	}else{
		$already_tweeted="";
	}

	//個別停止
	if (substr($exposts, 0, 1) == ",") {
		$exposts = substr($exposts, 1, strlen($exposts));
	}
	if (substr($exposts, -1, 1) == ",") {
		$exposts = substr($exposts, 0, strlen($exposts) - 1);
	}

	//抽出期間
	if (!(isset($ageLimit) && is_numeric($ageLimit))) {
		$ageLimit = FUDOU_TOP_OPT_AGE_LIMIT;
	}
	if (!(isset($maxAgeLimit) && is_numeric($maxAgeLimit))) {
		$maxAgeLimit = FUDOU_TOP_OPT_MAX_AGE_LIMIT;
	}

	//カテゴリ
	if (!isset($omitCats)) {
		$omitCats = '';
	}

	//ツイート記事数
	if($top_opt_no_of_tweet<=0){
		$top_opt_no_of_tweet = 1;
	}
	if($top_opt_no_of_tweet>10){
		$top_opt_no_of_tweet = 10;
	}

	//type of post to tweet
	$post_type = " post_type='none' AND ";
	$tmp_type = 0;
	if ( get_option('top_opt_post_type_post')  ) {
		$top_opt_post_type_post_sql = " P.post_type='post' ";
	}else{
		$top_opt_post_type_post_sql = '';
	}
	if ( get_option('top_opt_post_type_page')  ) {
		$top_opt_post_type_page_sql = " P.post_type='page' ";
	}else{
		$top_opt_post_type_page_sql = '';
	}
	if ( get_option('top_opt_post_type_fudo') ) {
		$top_opt_post_type_fudo_sql =  " P.post_type='fudo' ";
	}else{
		$top_opt_post_type_fudo_sql = '';
	}

	if ( $top_opt_post_type_post_sql || $top_opt_post_type_page_sql || $top_opt_post_type_fudo_sql ){
		$post_type  = "( ";
		if ( $top_opt_post_type_post_sql ){ $post_type .= $top_opt_post_type_post_sql; $tmp_type=1;}
		if ( $top_opt_post_type_page_sql ){ if( $tmp_type==1 ) { $post_type .= 'OR';} $post_type .= $top_opt_post_type_page_sql; $tmp_type=1;}
		if ( $top_opt_post_type_fudo_sql ){ if( $tmp_type==1 ) { $post_type .= 'OR';} $post_type .= $top_opt_post_type_fudo_sql;}
		$post_type  .= ") AND ";
	}

	//会員物件除外
	$meta_dat = '';
	if( $is_fudou && $is_fudoukaiin ){
		$sql  = "SELECT P.ID ";
		$sql .= " FROM $wpdb->posts AS P ";
		$sql .= " INNER JOIN $wpdb->postmeta AS PM ON P.ID = PM.post_id ";
		$sql .= " WHERE  P.post_type='fudo' AND P.post_status = 'publish' AND P.post_password = ''";
		$sql .= " AND PM.meta_key='kaiin' AND PM.meta_value='1' ";
		$metas = $wpdb->get_results( $sql, ARRAY_A );
		if(!empty($metas)) {
			$i=0;
			foreach ( $metas as $meta ) {
				if($i!=0) $meta_dat .= ",";
				$meta_dat .= $meta['ID'];
				$i++;
			}
		}
	}

	$sql2 = '';

	$sql  = "SELECT P.ID , P.post_title ";
	$sql .= " FROM $wpdb->posts AS P ";
	$sql .= " WHERE ";
	$sql .=  $post_type;
	$sql .= " P.post_status = 'publish' AND P.post_password = ''";

	if( $designation_post ){
		//指定
		$sql = $sql . " AND P.ID = $designation_post";
	}else{

		//会員物件除外
		if( $meta_dat ){
			$sql = $sql . " AND P.ID NOT IN ($meta_dat)";
		}

		//抽出期間
		if(is_numeric( $ageLimit )){
			if($ageLimit > 0)
				$sql .= " AND P." . $top_opt_date_modified . " <= curdate( ) - INTERVAL " . $ageLimit .    " day";
		}
		if ($maxAgeLimit != 0) {
				$sql .= " AND P." . $top_opt_date_modified . " >= curdate( ) - INTERVAL " . $maxAgeLimit . " day";
		}

		//個別停止
		if (isset($exposts)) {
			if (trim( $exposts ) != '') {
				$sql .= " AND P.ID NOT IN (" . $exposts . ") ";
			}
		}

		//ツイート済
		if (isset($already_tweeted)) {
			if(trim($already_tweeted) !=""){
				$sql .= " AND P.ID NOT IN (" . $already_tweeted . ") ";
			}
		}

		//カテゴリ
		if ($omitCats != '') {
				$sql .= " AND NOT ( P.ID IN (";
				$sql .= " SELECT TR.object_id ";
				$sql .= " FROM " . $wpdb->prefix . "term_relationships AS TR ";
				$sql .= " INNER JOIN " . $wpdb->prefix . "term_taxonomy AS TT ON TR.term_taxonomy_id = TT.term_taxonomy_id ";
				$sql .= " WHERE TT.term_id IN (" . $omitCats . ")";
				$sql .= " ))";
		}


		//ランダム
		$sql .= " ORDER BY RAND() LIMIT $top_opt_no_of_tweet ";

	}

	$oldest_post = $wpdb->get_results( $sql );


	//excludeposte Top Page
	if( $designation_post == '0' ){
		//TopPage
		$oldest_post[0]->ID = '0';
		if( get_option('top_opt_post_type_toppage_text') ){
			$oldest_post[0]->post_title = get_option('top_opt_post_type_toppage_text');
		}else{
			$oldest_post[0]->post_title  = esc_attr( get_bloginfo('name') ) . ' | ' . esc_attr( get_bloginfo('description') );
		}
	}


	if( $oldest_post == null ){
		if( $can_requery ){
			//Already Tweeted Posts clear
			$top_opt_tweeted_posts = array();
			update_option( 'top_opt_tweeted_posts', $top_opt_tweeted_posts );
			return fudo_top_generate_query( false, '', $tweet_sleep );
		}else{
			return __("No post found to tweet. Please check your settings and try again.", 'TweetOldPost'); 
		}
	}


	if( isset( $oldest_post ) ){
		 $ret = '';
		 foreach( $oldest_post as $k=>$odp ){
			array_push( $top_opt_tweeted_posts, $odp->ID );
			$ret .= 'Tweet '.($k + 1) . ' ( '. $odp->post_title  .' )' . ' : ' .fudo_top_opt_tweet_post( $odp->ID, $tweet_sleep ).'<br />';
		}

	        //Already Tweeted++
		update_option('top_opt_tweeted_posts', $top_opt_tweeted_posts);
		return $ret;
	}
	return $rtrn_msg;
}


/**
 *
 * Tweet for the post
 *
 * @since Fudousan Tweet old post Plugin 1.6.0
 * @param  int $oldest_post as post_id
 * @param  int $tweet_sleep
 * @return str $tweet_status
 */
function fudo_top_opt_tweet_post( $oldest_post, $tweet_sleep ) {

	global $wpdb;

	$image_abspath	= array();
	$main_image	= array();
	$hashtags = '';
	$hashtag_length = 0;

	if( $oldest_post == 0 ){

	}else{

		$post			= get_post( $oldest_post );
		$post_author_id		= $post->post_author;

		$content		= "";
		$shorturl		= "";
		$tweet_type		= get_option( 'top_opt_tweet_type' );
		$additional_text	= get_option( 'top_opt_add_text' );
		$additional_last_text	= get_option( 'top_opt_add_last_text' );
		$include_link		= get_option( 'top_opt_include_link' );
		$custom_hashtag_option	= get_option( 'top_opt_custom_hashtag_option' );
		$custom_hashtag_field	= get_option( 'top_opt_custom_hashtag_field' );
		$twitter_hashtags	= get_option( 'top_opt_hashtags' );
		$url_shortener		= get_option( 'top_opt_url_shortener' );
		$custom_url_option	= get_option( 'top_opt_custom_url_option' );
		$to_short_url		= get_option( 'top_opt_use_url_shortner' );
		$use_inline_hashtags	= get_option( 'top_opt_use_inline_hashtags' );
		$hashtag_length		= get_option( 'top_opt_hashtag_length' );

		//top_opt_include_link
		if ($include_link != "false") {

			//Fix get_permalink to shorturl
			if( get_option('permalink_structure') == '' ){
				if( $post->post_type == 'fudo' ){
					$permalink = home_url( '?post_type=fudo&p=' . $oldest_post );
				}else{
					$permalink = home_url( '?p=' . $oldest_post );
				}
			}else{
				$permalink = get_permalink( $oldest_post );
			}

			//top_opt_custom_url_option
			if ($custom_url_option) {
				$custom_url_field = get_option('top_opt_custom_url_field');
				if (trim($custom_url_field) != "") {
					$permalink = trim(get_post_meta($post->ID, $custom_url_field, true));
				}
			}

			//top_opt_use_url_shortner
			if ($to_short_url) {
				if ($url_shortener == "bit.ly") {
					$bitly_key  = get_option('top_opt_bitly_key');
					$bitly_user = get_option('top_opt_bitly_user');
					$shorturl   = fudo_shorten_url($permalink, $url_shortener, $bitly_key, $bitly_user);
				} else {

					if ($url_shortener == "WP.me" || $url_shortener == "shortlink") {
						//shortlink
						if (function_exists('wp_get_shortlink')) {
							//$shorturl = wpme_get_shortlink( $oldest_post );
							$shorturl = wp_get_shortlink( $oldest_post );
						}
					}else{
						$shorturl = fudo_shorten_url($permalink, $url_shortener);
					}
				}
			} else {
				$shorturl = $permalink;
			}
		}




		//top_opt_tweet_type
		if ($tweet_type == "title" || $tweet_type == "titlenbody") {
			$title = stripslashes($post->post_title);
			$title = strip_tags($title);
			$title = preg_replace('/\s\s+/', ' ', $title);
		} else {
			$title = "";
		}
		if ($tweet_type == "body" || $tweet_type == "titlenbody") {
			$body = stripslashes($post->post_content);
			$body = strip_tags($body);
			$body = preg_replace('/\s\s+/', ' ', $body);
		} else {
			$body = "";
		}
		if ($tweet_type == "titlenbody") {
			if ($title == null) {
				$content = $body;
			} elseif ($body == null) {
				$content = $title;
			} else {
				$content = "「" . $title . "」" . $body;
			}
		} elseif ($tweet_type == "title") {
			$content = $title;
		} elseif ($tweet_type == "body") {
			$content = $body;
		}

		//top_opt_add_text
		if ($additional_text != "") {
			$content = $additional_text . " " . $content;
		}

		//top_opt_custom_hashtag_option
		$newcontent = '';
		if ($custom_hashtag_option != "nohashtag") {

		        if ($custom_hashtag_option == "common") {
				//common hashtag
				$tagname = $twitter_hashtags;

						if ( $use_inline_hashtags ) {
							if ( strrpos($content,  $tagname) === false ) {
								$hashtags .=  $tagname . " ";
							} else {
								$newcontent = $content;
							}
						} else {
								$hashtags .=  $tagname . " ";
						}

		        } elseif ( $custom_hashtag_option == "custom" ) {
				//post custom field hashtag
				if ( trim($custom_hashtag_field) != "" ) {

						$tagname = trim(get_post_meta($post->ID, $custom_hashtag_field, true));
						if ( $use_inline_hashtags ) {
							if ( strrpos($content,  $tagname) === false ) {
								$hashtags .=  $tagname . " ";
							} else {
								$newcontent = $content;
							}
						} else {
								$hashtags .=  $tagname . " ";
						}
				}


			} elseif  ($custom_hashtag_option == "categories" ) {
				$post_categories = get_the_category($post->ID);
				if ( $post_categories ) {
					foreach ($post_categories as $category) {
						$tagname = str_replace(".", "", str_replace(" ", "", $category->cat_name));
						if ( $use_inline_hashtags ) {
							if ( strrpos($content, "#" . $tagname) === false ) {
								$hashtags .= "#" . $tagname . " ";
							} else {
								$newcontent = $content;
								//$newcontent = preg_replace('/\b' . $tagname . '\b/i', "#" . $tagname, $content, 1);
							}
						} else {
								$hashtags .= "#" . $tagname . " ";
						}
					}
				}

			} elseif  ($custom_hashtag_option == "tags" ) {
				$post_tags = get_the_tags($post->ID);
				if ($post_tags) {
					foreach ($post_tags as $tag) {
						$tagname = str_replace(".", "", str_replace(" ", "", $tag->name));
						if ($use_inline_hashtags) {
							if (strrpos($content, "#" . $tagname) === false) {
								$hashtags .= "#" . $tagname . " ";
							} else {
								$newcontent = $content;
								//$newcontent = preg_replace('/\b' . $tagname . '\b/i', "#" . $tagname, $content, 1);
							}
						} else {
								$hashtags .= "#" . $tagname . " ";
						}
					}
				}
			}

			if ($newcontent != ""){
				$content = $newcontent;
			}
		}

		
		if ( $include_link != "false" ) {
			if (!is_numeric($shorturl) && (strncmp($shorturl, "http", strlen("http")) == 0)) {
			} else {
				return __("OOPS!!! problem with your URL shortning service. Some signs of error", 'TweetOldPost') . $shorturl . ".";
			}
		}

		//画像ファイル取得
		$main_image = fudo_top_get_img_in_post( $oldest_post );
	}


	//画像ファイル ABSPATH変換
	if( $main_image ){
		foreach( $main_image as $img ){
			$image_abspath[] = str_replace ( get_option('siteurl') . '/' , ABSPATH, $img );
		}
	}else{
		$image_abspath = array();
	}

	//文字数調整
	$message = fudo_set_tweet_length( $content, $shorturl, $hashtags, $hashtag_length , $additional_last_text, $main_image );

	//do Tweet
	if ( $message ) {

		//Twitt Status
		$tweet_go = true;

		$tweet_status = '';
		$settings = fudo_top_get_settings();

		if ( isset( $settings['oauth_access_token']) && isset( $settings['oauth_access_token_secret'] ) ) {
			if( $tweet_go ){
				$poststatus = fudo_top_img_update_status( $message , $image_abspath, $settings['oauth_access_token'] , $settings['oauth_access_token_secret'] ) ;

				if ( $poststatus == true ){
					$tweet_status .= '<br />' . __("Whoopie!!! Posted Successfully", 'TweetOldPost') . ' (' . $settings['screen_name'] . ')';
				}else{
					$tweet_status .= '<br />' . __("OOPS!!! there seems to be some problem while tweeting. Please try again. ", 'TweetOldPost') . ' (' . $settings['screen_name'] . ')';
				}
			}else{
				$tweet_status .= '<br />' . __("There had to skip the post of tweet. . ", 'TweetOldPost') . ' (' . $settings['screen_name'] . ')';
			}
		}
		return $tweet_status;
	}
}


//Shorten URL send request to passed url and return the response
function fudo_send_request($url, $method='GET', $data='', $auth_user='', $auth_pass='') {

	$ch = curl_init($url);
	if (strtoupper($method) == "POST") {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	}
	if (ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off') {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	}
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if ($auth_user != '' && $auth_pass != '') {
		curl_setopt($ch, CURLOPT_USERPWD, "{$auth_user}:{$auth_pass}");
	}
	$response = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if ($httpcode != 200) {
		return $httpcode;
	}
	return $response;
}


// bitly short url returns a result form url
function fudo_top_curl_get_result($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}
function fudo_top_get_bitly_short_url($url,$login,$appkey,$format='txt') {
	$connectURL = 'http://api.bit.ly/v3/shorten?login='.$login.'&apiKey='.$appkey.'&uri='.urlencode($url).'&format='.$format;
	return fudo_top_curl_get_result($connectURL);
}


//Shorten long URLs with is.gd or bit.ly.
function fudo_shorten_url($the_url, $shortener='is.gd', $api_key='', $user='') {

	if (($shortener == "bit.ly") && isset($api_key) && isset($user)) {
		 $response = fudo_top_get_bitly_short_url($the_url, $user, $api_key);
	} elseif ($shortener == "su.pr") {
		$url = "http://su.pr/api/simpleshorten?url={$the_url}";
		$response = fudo_send_request($url, 'GET');
	} elseif ($shortener == "tr.im") {
		$url = "http://api.tr.im/api/trim_simple?url={$the_url}";
		$response = fudo_send_request($url, 'GET');
	} elseif ($shortener == "3.ly") {
		$url = "http://3.ly/?api=em5893833&u={$the_url}";
		$response = fudo_send_request($url, 'GET');
	} elseif ($shortener == "tinyurl") {
		$url = "http://tinyurl.com/api-create.php?url={$the_url}";
		$response = fudo_send_request($url, 'GET');
	} elseif ($shortener == "u.nu") {
		$url = "http://u.nu/unu-api-simple?url={$the_url}";
		$response = fudo_send_request($url, 'GET');
	} elseif ($shortener == "1click.at") {
		$url = "http://1click.at/api.php?action=shorturl&url={$the_url}&format=simple";
		$response = fudo_send_request($url, 'GET');
	} else {
		$url = "http://is.gd/api.php?longurl={$the_url}";
		$response = fudo_send_request($url, 'GET');
	}
	return $response;
}


//Shrink a tweet and accompanying URL down to 140 chars. mb_strlen
function fudo_set_tweet_length( $message, $url, $twitter_hashtags="", $hashtag_length=0 , $additional_last_text="", $main_image ) {

	//25文字+1  pic.twitter.com/HyCcYH9Qfi
	if( $main_image ){
		$image_length = 26;
	}else{
		$image_length = 0;
	}

	$tags = $twitter_hashtags;
	$message_length = mb_strlen($message);

	$url_length = mb_strlen($url);
	//Too long URL
	if( $url_length > 140 ){
		$url_length = 0;
		$url = '';
	}

	$additional_last_text_length = mb_strlen($additional_last_text);

	//$cur_length = mb_strlen($tags);
	if ($hashtag_length == 0)
		$hashtag_length = mb_strlen($tags);

	if ($twitter_hashtags != "") {
		if (strlen($tags) > $hashtag_length) {
			$tags = mb_substr($tags, 0, $hashtag_length);
			$tags = mb_substr($tags, 0, mb_strrpos($tags, ' '));
		}
		$hashtag_length = mb_strlen($tags);
	}

	if ( $message_length + $url_length + $hashtag_length + $image_length + $additional_last_text_length > 140) {
		$shorten_message_to = 140 - $url_length - $hashtag_length - $image_length - $additional_last_text_length;
		$shorten_message_to = $shorten_message_to - 4;

		if( $shorten_message_to < 0 ){
			$shorten_message_to = 0;
		}

		if ( $message_length > $shorten_message_to ) {
			$message = mb_substr($message, 0, $shorten_message_to);
			$message = mb_substr($message, 0, mb_strrpos($message, ' '));
			$message = $message . "...";
		}
	}

	if( $tags != '' ){
		return $message . " " . $additional_last_text . " " . $url . " " . $tags;
	}else{
		return $message . " " . $additional_last_text . " " . $url;
	}
}


//check time and update the last tweet time
function fudo_top_opt_update_time() {
        return fudo_top_to_update();
}

function fudo_top_to_update() {

	global $wpdb;
	$ret=0;

	//prevention from caching
	$last  = $wpdb->get_var("select SQL_NO_CACHE option_value from $wpdb->options where option_name = 'top_opt_last_update';");
	//$last = get_option('top_opt_last_update');
	$interval = get_option('top_opt_interval');

	if((trim($last)=='') || !(isset($last))){
		$last=0;
	}
	if (!(isset($interval))) {
		$interval = FUDOU_TOP_OPT_INTERVAL;
	}else if(!(is_numeric($interval))){
		$interval = FUDOU_TOP_OPT_INTERVAL;
	}

	$interval = $interval * 60 * 60;
	/*
	if (false === $last) {
	    $ret = 1;
	} else if (is_numeric($last)) {
	    $ret = ( (time() - $last) > ($interval ));
	}
	 
	 */

	if (is_numeric($last)) {
		$ret = ( (time() - $last) > ($interval ));
	}else{
		$ret = 0;
	}
	return $ret;
}


//get_settings
function fudo_top_get_settings() {
	global $top_defaults;

	$settings = $top_defaults;

	$wordpress_settings = get_option('top_settings');
	if ($wordpress_settings) {
		foreach ($wordpress_settings as $key => $value) {
			$settings[$key] = $value;
		}
	}
	return $settings;
}


//save
function fudo_top_save_settings($settings) {
	update_option( 'top_settings', $settings );
}

//reset
function fudo_top_reset_settings(){

	delete_option('top_settings');
	update_option('top_enable_log','');
	update_option('top_opt_add_text','');
	update_option('top_opt_add_text_at','beginning');
	update_option('top_opt_add_last_text','');
	update_option('top_opt_age_limit',FUDOU_TOP_OPT_AGE_LIMIT);
	update_option('top_opt_bitly_key','');
	update_option('top_opt_bitly_user','');
	update_option('top_opt_custom_hashtag_field','');
	update_option('top_opt_custom_hashtag_option','nohashtag');
	update_option('top_opt_custom_url_field','');
	update_option('top_opt_custom_url_option','');
	update_option('top_opt_hashtags','');
	update_option('top_opt_hashtag_length','20');
	update_option('top_opt_include_link','no');
	update_option('top_opt_interval',FUDOU_TOP_OPT_INTERVAL);
	delete_option('top_opt_last_update');
	update_option('top_opt_max_age_limit',FUDOU_TOP_OPT_MAX_AGE_LIMIT);
	update_option('top_opt_omit_cats','');
	update_option('top_opt_tweet_type','title');
	delete_option('top_opt_tweeted_posts');
	update_option('top_opt_url_shortener','');
	update_option('top_opt_use_inline_hashtags','');
	update_option('top_opt_use_url_shortner','');
	update_option('top_opt_admin_url','');

	update_option('top_opt_post_type_post', false );
	update_option('top_opt_post_type_page', false );
	update_option('top_opt_post_type_fudo', false );
	update_option('top_enable_image', false);
	update_option('top_enable_image_su', 4);
	update_option('top_opt_date_modified', 'post_date');
}


//投稿内画像検索
function fudo_top_get_img_in_post( $post_id ) {

	global $wpdb;
	global $is_fudou;

	//Twitter image Width Min limit
	$twitter_photo_sizes_min = apply_filters( 'twitter_photo_sizes_min', 400 );

	//Twitter image Height Min limit
	$twitter_photo_sizes_min2 = apply_filters( 'twitter_photo_sizes_min2', 150 );

	//Twitter image Type
	$twitter_photo_size = apply_filters( 'twitter_photo_size', 'large' );	//thumbnail、medium、large、full 


	//option to enable image
	$top_enable_image    = get_option('top_enable_image');
	$top_enable_image_su = get_option('top_enable_image_su');
	if (!(isset($top_enable_image_su) && is_numeric($top_enable_image_su))) {
		$top_enable_image_su = "4";
	}

	//画像
	$main_image = array();

	if( $top_enable_image ){

		$i = 0;
		//アイキャッチ画像
		if( !$main_image ){
			//thumbnail、medium、large、full 
			$eye_image = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), $twitter_photo_size );
			if( $eye_image ){
				//?Supported image formats are PNG, JPG and GIF. Animated GIFs are not supported.
				$file_type= strtolower( substr(strrchr($eye_image[0], '.'), 1) );
				if ( $file_type == 'jpg' || $file_type == 'png' || $file_type == 'gif'){
					//元画像が大きいかどうか
					$image_width  = $eye_image[1];
					$image_height = $eye_image[2];
					if( $image_width >= $twitter_photo_sizes_min && $image_height >= $twitter_photo_sizes_min2 ){
						$main_image[]  = $eye_image[0];
						$i++;
					}
				}
			}
		}

		//物件画像
		if( $i < $top_enable_image_su  ){
		if( $is_fudou ){

			for( $imgid=1; $imgid<=30; $imgid++ ){

				$fudoimg_data = get_post_meta( $post_id, "fudoimg$imgid", true );

				if( $fudoimg_data ){

					$sql  = "";
					$sql .=  "SELECT P.ID";
					$sql .=  " FROM $wpdb->posts AS P";
					$sql .=  " WHERE P.post_type ='attachment' AND P.guid LIKE '%/$fudoimg_data' ";
				//	$sql = $wpdb->prepare($sql,'');
					$metas = $wpdb->get_row( $sql );

					if ( !empty($metas) ){
						//thumbnail、medium、large、full 
						$fudoimg = wp_get_attachment_image_src( $metas->ID, $twitter_photo_size );

						if( $fudoimg ){
							//Supported image formats are PNG, JPG and GIF. Animated GIFs are not supported.
							$file_type= strtolower( substr(strrchr($fudoimg[0], '.'), 1) );
							if ( $file_type == 'jpg' || $file_type == 'png' || $file_type == 'gif'){
								//元画像が大きいかどうか
								$image_width  = $fudoimg[1];
								$image_height = $fudoimg[2];
								if( $image_width >= $twitter_photo_sizes_min && $image_height >= $twitter_photo_sizes_min2 ){
									//重複チェック
									if ( in_array( $fudoimg[0], $main_image ) === false ){
										if( $i < $top_enable_image_su  ){
											$main_image[]  = $fudoimg[0];
											$i++;
										}
									}
								}
							}
						}
					}
				}

				if( $i > $top_enable_image_su  ){
					break;
				}
			} //for
		}
		}

		//記事内画像
		if( $i < $top_enable_image_su  ){

			$_post = get_post( $post_id ); 
			$post_content = $_post->post_content;
			$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post_content, $matches);
			if( $output> 0 ){
				foreach ( $matches[1] as $image ) {

					//サムネイル画像から画像ID検索
						preg_match('/([^\/]+?)(-e\d+)?(-\d+x\d+)?(\.\w+)?$/', $image, $match);
						$post_name = $match[1];
						$sql = "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = 'attachment'";
						$sql = $wpdb->prepare($sql, $post_name);
						$attachmentid = $wpdb->get_var($sql);

					//無かった場合URLから画像ID検索
					if( !$attachmentid ){
						$post_name = $matches[1][0];
						$sql = "SELECT ID FROM $wpdb->posts WHERE guid = %s AND post_type = 'attachment'";
						$sql = $wpdb->prepare($sql, $post_name);
						$attachmentid = $wpdb->get_var($sql);
					}

					if( $attachmentid ){
						//thumbnail、medium、large、full 
						$post_img = wp_get_attachment_image_src( $attachmentid , $twitter_photo_size );
						if( $post_img ){
							//?Supported image formats are PNG, JPG and GIF. Animated GIFs are not supported.
							$file_type= strtolower( substr(strrchr($post_img[0], '.'), 1) );
							if ( $file_type == 'jpg' || $file_type == 'png' || $file_type == 'gif'){
								//元画像が大きいかどうか
								$image_width  = $post_img[1];
								$image_height = $post_img[2];
								if( $image_width >= $twitter_photo_sizes_min && $image_height >= $twitter_photo_sizes_min2 ){
									//重複チェック
									if ( in_array( $post_img[0], $main_image ) === false ){
										if( $i < $top_enable_image_su  ){
											$main_image[]  = $post_img[0] ;
											$i++;
										}
									}
								}
							}
						}
					}
					if( $i > $top_enable_image_su  ){
						break;
					}

				} //foreach
			}
		} //記事内画像

	} //top_enable_image

	return $main_image;
}


//記事 TwitterOAuth
function fudo_top_img_update_status( $message , $image_abspath, $oauth_access_token, $oauth_access_token_secret ) {

	$media_ids = array();
	$settings = fudo_top_get_settings();

	$consumer_key    = fudo_top_consumer_key();
	$consumer_secret = fudo_top_consumer_secret();

	// Load this library.
	require_once 'twitteroauth/autoload.php';

	$connection = new Abraham\TwitterOAuth\TwitterOAuth( $consumer_key, $consumer_secret, $oauth_access_token , $oauth_access_token_secret );

	foreach ( $image_abspath as $path ) {
		$media = $connection->upload( 'media/upload', array( 'media' => $path ) );
		$media_ids[] = $media->media_id_string;
	}

	if( !empty( $media_ids ) ){
		$parameters = array(
			'status' => $message,
			'media_ids' => implode( ',', $media_ids )
		);
	}else{
		$parameters = array(
			'status' => $message
		);
	}

	$result = $connection->post( 'statuses/update', $parameters );

	if( isset($result->id) ){
		return true;
	}else{
		return false;
	}
}
