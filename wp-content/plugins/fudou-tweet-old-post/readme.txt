=== fudou-tweet-old-post ===
Contributors: nendeb
Tags: Fudousan Tweet old post
Requires at least: 4.1.5
Tested up to: 4.2
Stable tag: 1.6.1

This plug-in is a modification of the Tweet old post ver4.0.7 For Fudousan Plugin.

== Description ==

Tweet Old Posts is a plugin designed to tweet your older posts to get more traffic. 
This plug-in is a modification of the Tweet old post ver4.0.7 For Fudousan Plugin.

== Installation ==

Following are the steps to install the Fudousan Tweet old post plugin

1. Download the latest version of the Fudousan Tweet old post Plugin to your computer from here.
2. With an FTP program, access your site?s server.
3. Upload (copy) the Plugin file(s) or folder to the /wp-content/plugins folder.
4. In your WordPress Administration Panels, click on Plugins from the menu.
5. You should see Fudousan Tweet old post Plugin listed. If not, with your FTP program, check the folder to see if it is installed. If it isn?t, upload the file(s) again. If it is, delete the files and upload them again.
6. To turn the Fudousan Tweet old post Plugin on, click Activate.
7. Check your Administration Panels or WordPress blog to see if the Plugin is working.
8. You can change the plugin options from TweetOldPosts under settings menu.


== Credits ==

This plugin uses [Tweet old post] by Ajay Matharu
-http://www.ajaymatharu.com/wordpress-plugin-tweet-old-posts/

This plugin uses [TwitterOAuth]
-PHP library for working with Twitter's OAuth API.
-https://github.com/abraham/twitteroauth


= Other Notes =
 * If you want to change the CONSUMER_KEY.
	add_filter( 'fudo_top_consumer_key', 'xxxxxx' }  );	//Your CONSUMER_KEY

 * If you want to change the CONSUMER_SECRET.
	add_filter( 'fudo_top_consumer_secret', 'xxxxxx' }  );	//Your CONSUMER_SECRET

 * If you want to change the Minimum Image Wide.
	add_filter( 'twitter_photo_sizes_min', 400 );

 * If you want to change the Minimum Image Height.
	add_filter( 'twitter_photo_sizes_min2', 150 );

 * If you want to change the Image Type.
	add_filter( 'twitter_photo_size', 'large' );	//thumbnail、medium、large、full 

 * cron 
	Set Cron Sample
	cd /home/～/www/wordpress/wp-content/plugins/fudou-tweet-old-post/cron; /usr/local/bin/php tweet-old-post-cron.php

 * If I want to stop the Tweet so use the cron.
	remove_action( 'init' , 'fudo_top_tweet_old_post' );


== Upgrade Notice ==

The required WordPress version has been changed and now requires WordPress 4.1.5 or higher
PHP version 5.3.2 or later


== Changelog ==
= v1.6.1 =
* Fixed top-core.php.
* Fixed top-admin.php.

= v1.6.0 =
* Fixed WordPress4.2.
* Update TwitterOAuth API

= v1.5.0 =
* Fixed WordPress4.0.

= v0.0.3 =
** Fixed mysql_real_escape.

= v0.0.2 =
** Fixed top-excludepost.

= v0.0.1 =
** Beta version of the plugin.

** Base Tweet old post v4.0.7**
