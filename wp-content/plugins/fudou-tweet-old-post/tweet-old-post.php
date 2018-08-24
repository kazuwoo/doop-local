<?php   
/* 
Plugin Name: Fudousan Tweet old post
Plugin URI: 
Description: Fudousan Tweet old post For Fudousan Plugin.
Version: 1.6.1
Author: nendeb
Author URI: http://nendeb.jp
License: GPLv2
*/  

// Define current version constant
define( 'FUDOU_TWEETOLDPOST_VERSION', '1.6.1' );


/*  Copyright 2015 nendeb (email : nendeb@gmail.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/


if ( !defined('WP_CONTENT_URL') ) define( 'WP_CONTENT_URL', get_option('siteurl').'/wp-content' );
if ( !defined('WP_CONTENT_DIR') ) define( 'WP_CONTENT_DIR', ABSPATH.'wp-content' );
if ( !defined('WP_PLUGIN_URL') )  define( 'WP_PLUGIN_URL', WP_CONTENT_URL.'/plugins' );
if ( !defined('WP_PLUGIN_DIR') )  define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins' );

require_once( 'top-admin.php' );
require_once( 'top-core.php' );
require_once( 'top-excludepost.php' );

load_plugin_textdomain( 'TweetOldPost', false, dirname(plugin_basename(__FILE__)) . '/languages/' );

define ('FUDOU_TOP_OPT_INTERVAL', 24);
define ('FUDOU_TOP_OPT_AGE_LIMIT', 7);
define ('FUDOU_TOP_OPT_MAX_AGE_LIMIT', 60);
define ('FUDOU_TOP_TWEET_SLEEP', 10);


//nendeb55 Tweet Old Post
define( 'FUDOU_CONSUMER_KEY',    'mwtTuHGWu5Guz7rhZztPw' );
define( 'FUDOU_CONSUMER_SECRET', '42wlrfE9HlBVQth35XcM2nryOBCdwTHtnAJ4TgXYVl8' );
//OAuth callback
define( 'FUDOU_OAUTH_CALLBACK', admin_url( 'admin.php?page=TweetOldPost&callback' )  );



//check last tweet time against set interval and span
function fudo_top_tweet_old_post() {
	if ( fudo_top_opt_update_time() ) {
		update_option('top_opt_last_update', time());
		fudo_top_opt_tweet_old_post();
		$ready=false;
	}
}
add_action( 'init' , 'fudo_top_tweet_old_post' );


//admin page
function fudo_top_admin_actions() {  
	$top_admin = new Fudou_TOP_ADMIN;
	add_menu_page( "TweetOldPost", __('F-TweetOldPost','TweetOldPost'),'manage_options', 'TweetOldPost', array($top_admin,'top_admin') );

	$top_exclude = new Fudou_TOP_EXCLUDE;
	add_submenu_page("TweetOldPost", __('F-ExcludePosts','TweetOldPost'), __('F-ExcludePosts','TweetOldPost'), 'manage_options', __('ExcludePosts','TweetOldPost'), array($top_exclude, 'top_exclude'));
}  
add_action( 'admin_menu' , 'fudo_top_admin_actions' );


//authorize redilect
function fudo_top_authorize(){
	if ( isset($_GET['page']) && $_GET['page'] == 'TweetOldPost' ) {
		if ( isset( $_REQUEST['oauth_token'] ) ) {
			$auth_url= str_replace('oauth_token', 'oauth_token1', fudo_top_currentPageURL());
			$top_url = admin_url( 'admin.php?page=TweetOldPost' ) . substr($auth_url,strrpos($auth_url, "page=TweetOldPost") + strlen("page=TweetOldPost"));
			echo '<script language="javascript">window.location.href="'.$top_url.'";</script>';
			die;
		}
	}
}
add_action( 'admin_init' , 'fudo_top_authorize' , 1 );


//admin link
function fudo_top_plugin_action_links($links, $file) {
	static $this_plugin;
	if (!$this_plugin) {
		$this_plugin = plugin_basename(__FILE__);
	}

	if ($file == $this_plugin) {
		// The "page" query string value must be equal to the slug
		// of the Settings admin page we defined earlier, which in
		// this case equals "myplugin-settings".
		$settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=TweetOldPost">' . __('Settings') . '</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}
add_filter( 'plugin_action_links' , 'fudo_top_plugin_action_links', 10, 2 );


//optionselected
function fudou_top_opt_optionselected($opValue, $value) {
	if ($opValue == $value) {
		return 'selected="selected"';
	}
	return '';
}


/**
 * CONSUMER_KEY
 * 
 * If you want to change the CONSUMER_KEY.
 * add_filter( 'fudo_top_consumer_key', 'xxxxxx' }  );		//Your CONSUMER_KEY
 */
function fudo_top_consumer_key() {
	return apply_filters( 'fudo_top_consumer_key', FUDOU_CONSUMER_KEY );
}

/**
 * CONSUMER_SECRET
 * 
 * If you want to change the CONSUMER_SECRET.
 * add_filter( 'fudo_top_consumer_secret', 'xxxxxx' }  );	//Your CONSUMER_SECRET
 */
function fudo_top_consumer_secret() {
	return apply_filters( 'fudo_top_consumer_secret', FUDOU_CONSUMER_SECRET);
}


/**
 *
 * Fudousan Plugin Active Check.
 *
 * @since Fudousan Plugin 1.0.0
 */
function fudou_active_plugins_check_tweetoldpost(){
	global $is_fudou,$is_fudoukaiin;
	$fudo_active_plugins = get_option('active_plugins');
	if(is_array($fudo_active_plugins)) {
		foreach($fudo_active_plugins as $meta_box){
			if( $meta_box == 'fudoukaiin/fudoukaiin.php') $is_fudoukaiin=true;
			if( $meta_box == 'fudou/fudou.php') $is_fudou=true;
		}
	}
}
add_action( 'init' , 'fudou_active_plugins_check_tweetoldpost' );

/**
 *
 * View Version in Footer
 *
 */
function fudou_tweetoldpost_footer_version() {
	echo "<!-- FUDOU TWEETOLDPOST VERSION " . FUDOU_TWEETOLDPOST_VERSION . " -->\n";
}
add_filter( 'wp_footer' , 'fudou_tweetoldpost_footer_version' );

