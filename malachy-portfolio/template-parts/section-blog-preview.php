<?php
/**
 * Template Part: Blog Preview Section (Journal)
 *
 * Reference: polished-portfolio Journal grid.
 * 1 featured article with accent background + 2 notes.
 *
 * @package Malachy_Portfolio
 */
?>
<section id="blog" class="journal section-wrap" aria-labelledby="journal-title">
	<div class="section-label reveal"><span>07</span><span><?php esc_html_e( 'Notes from the shop', 'malachy-portfolio' ); ?></span></div>
	<div class="journal-heading reveal">
		<h2 id="journal-title">
			<?php esc_html_e( 'News from', 'malachy-portfolio' ); ?><br />
			<em><?php esc_html_e( 'the shop.', 'malachy-portfolio' ); ?></em>
		</h2>
		<a href="<?php echo esc_url( home_url( '/blog' ) ); ?>">
			<?php esc_html_e( 'All notes', 'malachy-portfolio' ); ?>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
		</a>
	</div>

	<div class="journal-grid">
		<?php
		$recent = new WP_Query(
			array(
				'posts_per_page'      => 3,
				'ignore_sticky_posts' => 1,
			)
		);

		$posts = array();
		if ( $recent->have_posts() ) {
			while ( $recent->have_posts() ) {
				$recent->the_post();
				$cats  = get_the_category();
				$cat   = ! empty( $cats ) ? $cats[0]->name : __( 'Article', 'malachy-portfolio' );
				$posts[] = array(
					'meta'  => $cat . ' · ' . get_the_date( 'm.y' ),
					'title' => get_the_title(),
					'link'  => get_the_permalink(),
				);
			}
			wp_reset_postdata();
		}

		if ( empty( $posts ) ) {
			$posts = array(
				array(
					'meta'  => __( 'QA practice · 05.24', 'malachy-portfolio' ),
					'title' => __( 'Small checks, fewer surprises.', 'malachy-portfolio' ),
					'link'  => '#',
				),
				array(
					'meta'  => __( 'Infrastructure · 02.24', 'malachy-portfolio' ),
					'title' => __( 'Docker monitoring stack in six minutes.', 'malachy-portfolio' ),
					'link'  => '#',
				),
				array(
					'meta'  => __( 'AI & Tooling · 01.24', 'malachy-portfolio' ),
					'title' => __( 'Agentic coding is a conversation, not a prompt.', 'malachy-portfolio' ),
					'link'  => '#',
				),
			);
		}

		$featured = array_shift( $posts );
		?>

		<article class="journal-feature reveal">
			<div>
				<p class="journal-meta"><?php echo esc_html( $featured['meta'] ); ?></p>
				<h3><?php echo esc_html( $featured['title'] ); ?></h3>
			</div>
			<a href="<?php echo esc_url( $featured['link'] ); ?>">
				<?php esc_html_e( 'Read the note', 'malachy-portfolio' ); ?>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
			</a>
		</article>

		<?php foreach ( $posts as $post ) : ?>
			<article class="journal-note reveal">
				<p class="journal-meta"><?php echo esc_html( $post['meta'] ); ?></p>
				<h3><?php echo esc_html( $post['title'] ); ?></h3>
				<a href="<?php echo esc_url( $post['link'] ); ?>">
					<?php esc_html_e( 'Read', 'malachy-portfolio' ); ?>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
				</a>
			</article>
		<?php endforeach; ?>
	</div>
</section>
