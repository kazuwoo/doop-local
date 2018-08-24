<?php
	// リンクから"http://hostname/"を削除する
	function delete_host_from_attachment_url( $url ) {
		$regex = '/^http(s)?:\/\/[^\/\s]+(.*)$/';
		if ( preg_match( $regex, $url, $m ) ) {
			$url = $m[2];
		}
		return $url;
	}
	add_filter( 'wp_get_attachment_url', 'delete_host_from_attachment_url' );
	add_filter( 'attachment_link', 'delete_host_from_attachment_url' );
	
	// 前の記事、次の記事のリンク挿入
	function add_prev_next_link() {
		if (is_single()) {
			if (get_next_post()) {
				next_post_link('<div class="next_link"></span>%link</div>', '次の記事<span class="glyphicon glyphicon-triangle-right">', false);
			}
			if (get_previous_post()) {
				previous_post_link('<div class="prev_link">%link</div>', '<span class="glyphicon glyphicon-triangle-left"></span>前の記事', true);
			}
		}
	}
	add_action( 'habakiri_after_entry_content', 'add_prev_next_link' );
	
	// 画像の前後にdivタグを挿入
	function format_image_tag($the_content) {
		if (is_singular()) {
			return preg_replace('/(<img .*?>)/', '<div class="img_post_area">\1</div>', $the_content);
			
		} else {
			return $the_content;
		}
	}
	add_filter('the_content', 'format_image_tag');
	
	// 投稿一覧のサムネイル取得
	function the_post_thumbnail_for_doop() {
		$classes = array(
			'entry--has_media__link',
		);
		if ( !has_post_thumbnail() ) {
			$classes[] = 'entry--has_media__link--text';
		}
		$classes = apply_filters( 'habakiri_post_thumbnail_link_classes', $classes );
		?>
		<a href="<?php the_permalink(); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php
				// アイキャッチ画像あり
				$size = apply_filters( 'habakiri_post_thumbnail_size', 'thumbnail' );
				the_post_thumbnail( $size, array(
					'class' => '',
				) );
				?>
			<?php else : ?>
				<?php
				// 投稿中の画像を取得
				$post = get_post();
				preg_match('/wp-image-(\d+)/s' , $post->post_content, $thumb);
				?>
				<?php if ($thumb): ?>
					<!-- 投稿中の画像の一番最初を取得 -->
					<?php echo wp_get_attachment_image($thumb[1], 'thumbnail'); ?>
				<?php else : ?>
					<!-- アイキャッチも投稿中の画像もなし -->
					<span class="entry--has_media__text">
						<?php echo apply_filters( 'habakiri_no_thumbnail_text', get_the_time( 'd' ) ); ?>
					</span>
				<?php endif; ?>
			<?php endif; ?>
		</a>
		<?php
	}

	function doop_copyright( $copyright ) {

		return 'Copyright 2011 doop. Powerd by <a href="http://wordpress.org/" target="_blank">WordPress</a>';
	}
	add_filter( 'habakiri_copyright', 'doop_copyright' );	
	

	// 人気記事の出力
	function echo_popular_posts() {
		ob_start(); // Buffer output
		?>
		
		<div id="popular-posts" class="widget sidebar-widget widget_popular">
		<ul>
			<?php
				// 以下のWP_Queryで表示回数上位５件の記事を取得
				$query = new WP_Query(array(
					'meta_key' => 'views',
					'orderby' => 'meta_value_num',
					'order' => 'DESC',
					'posts_per_page' => 5
				));
				
				// 以下のwhileループで上記で取得した人気上位５件の記事をhtml含めて出力
				while ($query->have_posts()) : $query->the_post();
			?>
			<li>
				<!-- the_permalink() で記事のURL、the_title() で記事のタイトルを取得 -->
				<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a></br>
				
				<!-- WP-PostViewsの導入により以下の the_views() で表示回数を取得可能 -->
				<span class="label label-primary"><?php echo the_views(); ?></span>
			</li>
			<?php
				endwhile;
			?>
		</ul>
		
		<?php
		$output = ob_get_clean(); // clear buffer
		return $output;
	}
	 
	// most_viewd_posts()のショートコードを作成
	add_shortcode('popular_posts', 'echo_popular_posts');
	 
	// ショートコードをテキストウィジェットで使用できるようにするためのフィルタ
	add_filter('widget_text', 'do_shortcode');
	
	// カテゴリ一覧（記事数を含む）
	function echo_list_cats_and_count() {
		$cats = get_categories();
		ob_start(); // Buffer output
		?>
			<div class="widget sidebar-widget widget_categories">
				<ul>
		<?php foreach($cats as $category) { ?>
					<li>
						<a href="<?= get_category_link( $category->term_id ) ?>"><?= $category->name ?><span class="cat_count"><?= $category->count ?></span></a>
					</li>
		<?php } ?>
				</ul>
			</div>
		<?php
		
		$output = ob_get_clean(); // clear buffer
		return $output;
	}
	// カテゴリ一覧を表示するショートコードを作成
	add_shortcode('list_cats_and_count', 'echo_list_cats_and_count');
	
	// 月別アーカイブ一覧
	function echo_list_archives() {
		// 年別アーカイブ取得
		$archives_year = wp_get_archives('type=yearly&show_post_count=0&format="option"&echo=0');

		// 年毎に分割
		$archives_year_array = split("\n", $archives_year);
		foreach ($archives_year_array as $archive_year_tag) {
			// 要素ごとに分解
			preg_match("/(<a.*?>)(\d{4})(<.*?>)/", $archive_year_tag, $elements);
			$archives_year_map[$elements[2]] = $archive_year_tag;
		}
		
		// 月別アーカイブ取得
		$archives_month = wp_get_archives('type=monthly&show_post_count=1&format="option"&echo=0');
		
		// 月毎に分割
		$archives_month_array = explode("\n", $archives_month);
		
		ob_start(); // Buffer output
		?>
		
		<div class="widget sidebar-widget widget_archive">
			<ul>
		
		<?php
		$before_year = "";
		foreach ($archives_month_array as $archive_month_tag) {
			
			if ($archive_month_tag == "") {
				// 最後に空白のゴミが入るのでスキップ
				break;
			}
			
			// 要素毎に分解
			preg_match_all("/<.*?>/", $archive_month_tag, $tags);
			preg_match("/(\d{4})年/", $archive_month_tag, $year);
			preg_match("/(\d+)月/",   $archive_month_tag, $month);
			preg_match("/\((\d+)\)/", $archive_month_tag, $count);
			
			// 年が変わったら年を出力
			if ($year[1] != $before_year) {
				printf("<h6>%s</h6>\n", $archives_year_map[$year[1]]);
				$before_year = $year[1];
			}
			printf("<li>%s%02d月<span class='month_count'>%d</span></a></li>\n", $tags[0][0], $month[1], $count[1]);
		}
		?>
		
			</ul>
		</div>
		
		<?php
		$output = ob_get_clean(); // clear buffer
		return $output;

	}
	
	// 月別アーカイブ一覧を表示するショートコードを作成
	add_shortcode('list_archives', 'echo_list_archives');
	
?>
