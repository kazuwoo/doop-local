<?php
/**
 * exclude class.
 *
 * @package Fudousan Tweet old post Pro
 * @subpackage Fudousan Plugin
 * Version: 2.6.0
 */
class Fudou_TOP_EXCLUDE {

	public function __construct(){
		add_action('admin_init', array( $this, 'fudoukaiin_thickbox' ));
	}

	//thickbox
	function fudoukaiin_thickbox(){
		if (function_exists('add_thickbox')) add_thickbox();
	}

	public function top_exclude() {

		global $is_fudou,$is_fudoukaiin;
		global $wpdb;

		if (current_user_can('manage_options')) {

			$message = null;
			$message_updated = __("Tweet Old Post Options Updated.", 'TweetOldPost');
			$response = null;
			$records_per_page = 20;
			$omit_cat=get_option('top_opt_omit_cats');
			$update_text = "Exclude Selected";
			$search_term="";
			$ex_filter="all";
			$cat_filter=0;


			$maxAgeLimit = get_option('top_opt_max_age_limit');
			$ageLimit = get_option('top_opt_age_limit');

			$top_opt_date_modified = get_option('top_opt_date_modified');
			if( !$top_opt_date_modified ) $top_opt_date_modified = 'post_date';


			if ((!isset($_GET["paged"])) && (!isset($_POST["delids"])) ) {
				$exposts = get_option('top_opt_excluded_post');
			} else {
				$exposts = $_POST["delids"];
			}


			$exposts = preg_replace('/,,+/', ',', $exposts);
			if (substr($exposts, 0, 1) == ",") {
				$exposts = substr($exposts, 1, strlen($exposts));
			}
			if (substr($exposts, -1, 1) == ",") {
				$exposts = substr($exposts, 0, strlen($exposts) - 1);
			}
			$excluded_posts = explode(",", $exposts);


			if ( !isset($_GET['paged']) ){
				$_GET['paged'] = 1;
			}


			echo '<div class="wrap">';
			echo '<h2>' . __('Fudousan Exclude Post', 'TweetOldPost') . '</h2>';


			if (isset($_POST["excludeall"])) {

				if ( substr($_POST["delids"], 0, -1) == "" ) {
					echo '<div id="message" style="margin-top:30px" class="updated fade">';
					echo '<p>' . __('No post selected please select a post to be excluded.', 'TweetOldPost') . '</p>';
					echo '</div>';
				} else {
					update_option('top_opt_excluded_post',$exposts);

					echo '<div id="message" style="margin-top:30px" class="updated fade">';
					echo '<p>' . __('Posts excluded successfully.', 'TweetOldPost') . '</p>';
					echo '</div>';
				}
			}

			//tweet now
			if ( isset($_GET['tweet']) && isset($_GET['post']) ) {

				$tweet_nonce = isset($_GET['nonce']) ?  $_GET['nonce'] : '';
				if ( wp_verify_nonce( $tweet_nonce , 'fudou_tweet_nonce') ){

					$designation_post = (int)$_GET['post'];

			        	//tweet now clicked
					$tweet_msg = fudo_top_generate_query( false, $designation_post, 2 );

					echo '<div id="message" class="updated fade">';
					echo '<p>' . __($tweet_msg, 'TweetOldPost') . '</p>';
					echo '</div>';
				}else{
					echo '<div id="message" class="updated fade">';
					echo '<p>' . __("OOPS!!! there seems to be some problem while tweeting. Please try again. ", 'TweetOldPost') . '</p>';
					echo '</div>';
				}
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
				$top_opt_post_type_fudo_sql = " P.post_type='fudo' ";
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
			//	$sql = $wpdb->prepare($sql,'');
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


			$sql  = "SELECT P.ID , P.post_title , P.post_date , P.post_modified , U.user_nicename , P.guid , P.post_type";
			$sql .= " FROM $wpdb->posts AS P ";
			$sql .= " INNER JOIN  $wpdb->users AS U ON P.post_author = U.ID ";
			$sql .= " WHERE ";
			$sql .=   $post_type;
			$sql .= " P.post_status = 'publish' AND P.post_password = ''";

			//会員物件除外
			if( $meta_dat ){
				$sql = $sql . " AND P.ID NOT IN ($meta_dat)";
			}


			if(isset($_POST["setFilter"])){
				if($_POST["cat"] != 0){
					$cat_filter = $_POST["cat"];
					$cat_filter = esc_sql(esc_attr($cat_filter));
					$sql = $sql . " AND P.ID IN ( SELECT tr.object_id FROM ".$wpdb->prefix."term_relationships AS tr INNER JOIN ".$wpdb->prefix."term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.term_id=" . $cat_filter . ")";

			        } else {
					$cat_filter = 0;
				}

				if($_POST["selFilter"] == "excluded") {
					$sql = $sql . " AND P.ID IN (".$exposts.")";
					$update_text = "Update";
					$ex_filter = "excluded";
				}
			} else {
				if($omit_cat !='') {
					$sql = $sql . " AND P.ID NOT IN ( SELECT tr.object_id FROM ".$wpdb->prefix."term_relationships AS tr INNER JOIN ".$wpdb->prefix."term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.term_id IN (" . $omit_cat . "))";
				}
			}


			//絞り込み期間
			if(is_numeric($ageLimit)){
				if($ageLimit > 0)
					$sql .= " AND P." . $top_opt_date_modified . " <= curdate( ) - INTERVAL " . $ageLimit .    " day";
			}

			if ($maxAgeLimit != 0) {
					$sql .= " AND P." . $top_opt_date_modified . " >= curdate( ) - INTERVAL " . $maxAgeLimit . " day";
			}



			if(isset($_POST["s"])){
				if(trim( $_POST["s"]) != "") {
					$_s = $_POST["s"];
					$_s = esc_sql(esc_attr($_s));
					$sql = $sql . " AND P.post_title LIKE '%" . trim( $_s) . "%'";
					$search_term = trim( $_s);
				}
			}

			$sql = $sql . " ORDER BY P.post_date desc";
			$posts = $wpdb->get_results($sql);

			$from = $_GET["paged"] * $records_per_page - $records_per_page;
			$to = min($_GET['paged'] * $records_per_page, count($posts));
			$post_count =count($posts);

			$excludeList = array();

			$ex = 0;
			for ($j = 0; $j < $post_count; $j++) {
				if (in_array($posts[$j]->ID, $excluded_posts)) {
					$excludeList[$ex] = $posts[$j]->ID;
					$ex = $ex + 1;
				}
			}

			if( count($excludeList) > 0 ){
				$exposts = implode(",",$excludeList);
			}


			//form
			echo '<form id="top_TweetOldPost" name="top_TweetOldPost" action="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=ExcludePosts" method="post">';
			echo '<input type="hidden" name="delids" id="delids" value="' . $exposts . '" />';
			echo '<input type="submit" id="pageit" name="pageit" style="display:none" value="" />';

			echo '<div class="tablenav">';

				//keyword search
				echo '<p class="search-box" style="margin:0 0 0 10px">';
				echo '<input type="text" id="post-search-input" name="s" value="'.$search_term.'" />';
				echo '<input type="submit" value="' . __('Search Posts', 'TweetOldPost') . '" name="search" class="button" />';
				echo '</p>';

				echo '<div class="alignleft actions">';

					echo '<select name="selFilter" id="selFilter" style="width:100px">';
					echo '<option value="all" '.fudou_top_opt_optionselected("all",$ex_filter).'> ' . __('All', 'TweetOldPost') . ' </option>';
					echo '<option value="excluded" '.fudou_top_opt_optionselected("excluded",$ex_filter).'> ' . __('Excluded', 'TweetOldPost') . ' </option>';
					echo '</select>';

					$dropdown_options = array(
						'show_option_all' => __('Selected Categories'),
						'exclude' => $omit_cat,
						'selected' => $cat_filter,
					);
					wp_dropdown_categories($dropdown_options);

					echo '<input type="submit" class="button-secondary" name="setFilter" value="' . __('Filter', 'TweetOldPost') . '" />';

			        echo '</div>';

			//Update
			//print('<input type="submit" class="button-secondary" name="excludeall" value="' . __('Update', 'TweetOldPost') . '" />');

			if( count($posts) > 0 ) {
		        
				$page_links = paginate_links(array(
					'base' => add_query_arg('paged', '%#%'),
					'format' => '',
					'prev_text' => __('&laquo;'),
					'next_text' => __('&raquo;'),
					'total' => ceil(count($posts) / $records_per_page),
					'current' => $_GET['paged']
				));

				if ($page_links) {

					echo '<div class="tablenav-pages">';
						$page_links_text = sprintf('<span class="displaying-num">' . __('Displaying %s&#8211;%s of %s') . '</span>%s',
							number_format_i18n(( $_GET['paged'] - 1 ) * $records_per_page + 1),
							number_format_i18n(min($_GET['paged'] * $records_per_page, count($posts))),
							number_format_i18n(count($posts)),
							$page_links
						);
						echo $page_links_text;
					echo '</div>';
				}

			echo '</div>';	//tablenav div

				print('	<div id="top_exclude" class="wrap">
					<table class="widefat fixed">
						<thead>
						<tr>
							<th class="manage-column column-cb check-column"><input name="headchkbx" onchange="javascript:checkedAll();" type="checkbox" value="checkall"/></th>
							<th class="image">' . __('Image', 'TweetOldPost') . '</th>
							<th class="postid">' . __('Id', 'TweetOldPost') . '</th>
							<th class="posttitle">' . __('Post Title', 'TweetOldPost') . '</th>
							<th class="author">' . __('Author', 'TweetOldPost') . '</th>
							<th class="postdate">' . __('Post Date/Poat Modified', 'TweetOldPost') . '</th>
			                                <th class="categories">' . __('Categories', 'TweetOldPost') . '<br />' . __('Post Type', 'TweetOldPost') . '</th>
						</tr>
						</thead>
						<tbody>
				');


				$cats = '';

				for ($i = $from; $i < $to; $i++) {
					//post
					$categories = get_the_category($posts[$i]->ID);
					if (!empty($categories)) {
						$out = array();
						foreach ($categories as $c){
							$out[] = "" . esc_html(sanitize_term_field('name', $c->name, $c->term_id, 'category', 'display')) . "";
						}
						$cats = join(', ', $out);
					}
					//fudou
					if( $is_fudou ){
					$fudou_terms = get_the_terms($posts[$i]->ID,'bukken');
					if (!empty($fudou_terms)) {
						$out = array();
						foreach ($fudou_terms as $c){
							$out[] = "" . esc_html(sanitize_term_field('name', $c->name, $c->term_id, 'bukken', 'display')) . "";
						}
						$cats = join(', ', $out);
					}
					}

					if( empty($cats) ) $cats = '-';


					if (in_array($posts[$i]->ID, $excluded_posts)) {
						$checked = "Checked";
						$bgcolor="#FFCC99";
					} else {
						$checked = "";
						$bgcolor="#FFF";
					}


					//物件・投稿内画像
					$main_image = fudo_top_get_img_in_post( $posts[$i]->ID );


					//check enable image count
					$top_enable_image_su = get_option('top_enable_image_su');
					if (!(isset($top_enable_image_su) && is_numeric($top_enable_image_su))) {
						$top_enable_image_su = "4";
					}

					if( isset( $main_image[0] ) ){

						if( $top_enable_image_su == 1 ){
							$main_image_tag = '<a href="' .$main_image[0]. '" class="thickbox" title="' .$main_image[0]. '"><img src="' .$main_image[0]. '" width="100" /></a>';
						}else{

							$main_image_tag = '<a href="' .$main_image[0]. '" class="thickbox" title="' .$main_image[0]. '"><img src="' .$main_image[0]. '" width="75" /></a>';
							if( isset( $main_image[1] ) ){
								$main_image_tag .= '<a href="' .$main_image[1]. '" class="thickbox" title="' .$main_image[1]. '"><img src="' .$main_image[1]. '" width="75" /></a>';
							}
							if( isset( $main_image[2] ) ){
								$main_image_tag .= '<a href="' .$main_image[2]. '" class="thickbox" title="' .$main_image[2]. '"><img src="' .$main_image[2]. '" width="75" /></a>';
							}
							if( isset( $main_image[3] ) ){
								$main_image_tag .= '<a href="' .$main_image[3]. '" class="thickbox" title="' .$main_image[3]. '"><img src="' .$main_image[3]. '" width="75" /></a>';
							}
						}

					}else{
						$main_image_tag = '';
					}

					print('
						<tr style="background-color:'.$bgcolor.';">
							<th class="check-column">
								<input type="checkbox" name="chkbx" id="del' . $posts[$i]->ID . '" onchange="javascript:managedelid(this,\'' . $posts[$i]->ID . '\');" value="' . $posts[$i]->ID . '" ' . $checked . '/>
							</th>

							<td class="tweet_image">' . $main_image_tag . '</td>

							<td>
								' . $posts[$i]->ID . '<br />
								<a href="post.php?post=' . $posts[$i]->ID. '&action=edit"  target="_blank">' . __('Edit', 'TweetOldPost') . '</a>
								 | <a href=' . $posts[$i]->guid . ' target="_blank">' . __('View', 'TweetOldPost') . '</a>
								 | <a href="admin.php?page=ExcludePosts&tweet&post=' . $posts[$i]->ID. '&nonce='. wp_create_nonce( 'fudou_tweet_nonce' ) . '">' . __('Tweet Now', 'TweetOldPost') . '</a>

							</td>
							<td>' . $posts[$i]->post_title . '</td>
							<td>' . $posts[$i]->user_nicename . '</td>
			                                <td>' . $posts[$i]->post_date . '<br />' . $posts[$i]->post_modified . '</td>
			                                <td>' . $cats . '<br />' . $posts[$i]->post_type . '</td>
						</tr>
					');
			        }

			        print('
							</tbody>
							</table>
						</div>
				');

			        print('<div class="tablenav"><div class="alignleft actions"><input type="submit" class="button-secondary" name="excludeall" value="' . __('Update', 'TweetOldPost') . '" /></div>');

				if ($page_links) {
					print('<div class="tablenav-pages">');
					$page_links_text = sprintf('<span class="displaying-num">' . __('Displaying %s&#8211;%s of %s') . '</span>%s',
						number_format_i18n(( $_GET['paged'] - 1 ) * $records_per_page + 1),
						number_format_i18n(min($_GET['paged'] * $records_per_page, count($posts))),
						number_format_i18n(count($posts)),
						$page_links
					);
					echo $page_links_text;
					print('</div>');
				}
			        print('</div>');



				print('
					<script language="javascript">

						jQuery(function() {
							jQuery(".page-numbers").click(function(e){
								jQuery("#top_TweetOldPost").attr("action",jQuery(this).attr("href"));
								e.preventDefault();
								jQuery("#pageit").click();
							});// page number click end
						});//jquery document.ready end

						function setExcludeList(exlist){
							jQuery("#excludeList").html("\"" + exlist + "\"");
						}


						function managedelid(ctrl,id)
						{
							var delids = document.getElementById("delids").value;
							if(ctrl.checked){
								delids=addId(delids,id);
							}else{
								delids=removeId(delids,id);
							}

							if( delids == ""){
								delids = ",0";
							}
							document.getElementById("delids").value=delids;
			                                setExcludeList(delids);
						}

						function removeId(list, value) {
							list = list.split(",");
							if(list.indexOf(value) != -1)
							list.splice(list.indexOf(value), 1);
							return list.join(",");
						}


						function addId(list,value){
							list = list.split(",");
							if(list.indexOf(value) == -1)
								list.push(value);
							return list.join(",");
						}

						function checkedAll() {
							var ischecked=document.top_TweetOldPost.headchkbx.checked;
							var delids="";
							for (var i = 0; i < document.top_TweetOldPost.chkbx.length; i++) {
								document.top_TweetOldPost.chkbx[i].checked = ischecked;
								if(ischecked)
									delids=delids+document.top_TweetOldPost.chkbx[i].value+",";
							}
							if( delids == ""){
								delids = ",0";
							}
							document.getElementById("delids").value=delids;
						}
			                        setExcludeList("' . $exposts . '");
						</script>
				');
			}else{

			echo '</div>';	//tablenav div
				print('
					<div id="message" style="margin-top:30px" class="updated fade">
						<p>' . __('No Posts found. Review your search or filter criteria/term.', 'TweetOldPost') . '</p>
					</div>
				');
			}
			print('</form>');
		        print('	</div>	');


			//fudou
			if( $is_fudou ){

				//物件カテゴリ
				$append_option = '';
				$sql = "SELECT DISTINCT T.term_id , T.name ";
				$sql .=  " FROM ($wpdb->postmeta AS PM";
				$sql .=  " INNER JOIN (($wpdb->terms AS T";
				$sql .=  " INNER JOIN $wpdb->term_taxonomy AS TT ON T.term_id = TT.term_id) ";
				$sql .=  " INNER JOIN $wpdb->term_relationships AS TR ON TT.term_taxonomy_id = TR.term_taxonomy_id) ON PM.post_id = TR.object_id)";
				$sql .=  " INNER JOIN $wpdb->posts AS P ON PM.post_id = P.ID";
				$sql .=  " WHERE P.post_type ='fudo'";
				$sql .=  " AND TT.taxonomy ='bukken'";
				if( $omit_cat )
				$sql .=  " AND TT.term_id NOT IN (" . $omit_cat . ")";

			//	$sql = $wpdb->prepare($sql,$omit_cat);
				$metas = $wpdb->get_results( $sql, ARRAY_A );
				if(!empty($metas)) {
					foreach ( $metas as $meta ) {
						$append_option .= 'jQuery("#cat").append(jQuery("<option>").html("'.$meta['name'].'").val("'.$meta['term_id'].'"));' . "\n";
						if( $cat_filter == $meta['term_id'] ) $append_option .= 'jQuery("#cat").val("'.$meta['term_id'].'");' . "\n";
					}
				}

				if( $append_option ){
					$append_option  = 'jQuery("#cat").append( jQuery("<optgroup>").attr("label","--------") );' . "\n" . $append_option;
				//	$append_option .= 'jQuery("#cat").append( jQuery("</optgroup>").html("") );' . "\n" ;
				}

				print('
					<script language="javascript">
						jQuery(function() {
						' . $append_option . '
						});	//jquery append_option
					</script>
				');
			}


		} else {
		        print('
				<div id="message" class="updated fade">
					<p>' . __('You do not have enough permission to set the option. Please contact your admin.', 'TweetOldPost') . '</p>
				</div>
			');
		}
	}

}	//class Fudou_TOP_EXCLUDE
