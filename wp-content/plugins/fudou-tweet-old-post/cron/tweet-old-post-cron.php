<?php
/**
 * Front to the WordPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells WordPress to load the theme.
 *
 * @package WordPress4.2
 * @package Fudousan Tweet old post
 * Version: 1.6.0
 */

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */
define('WP_USE_THEMES', false);


/** Loads the WordPress Environment and Template */
require_once '../../../../wp-blog-header.php';


/**
 * cron 
 * Set Cron Sample
 * cd /home/`/www/wordpress/wp-content/plugins/fudou-tweet-old-post/cron; /usr/local/bin/php tweet-old-post-cron.php
 *
 */
if ( function_exists('fudo_top_generate_query') ) {
	function fudo_top_tweet_old_post_cron() {
		//check time and update the last tweet time
		//if ( fudo_top_opt_update_time() ) {
		//	update_option('top_opt_last_update', time());
			$result = fudo_top_generate_query();

			$result = mb_convert_encoding( $result, "JIS", "UTF-8" );
			$result	= str_replace( "<br />", "\n", $result );

			echo $result;
		//}
	}
	fudo_top_tweet_old_post_cron();
}
