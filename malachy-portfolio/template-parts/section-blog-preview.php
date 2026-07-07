<?php
/**
 * Template Part: Blog Preview Section
 *
 * Source: React BlogPreview.tsx — 3 latest blog posts on front page.
 *
 * @package Malachy_Portfolio
 */
?>
<section id="blog" class="blog-section" aria-label="<?php esc_attr_e( 'Blog', 'malachy-portfolio' ); ?>">
	<div class="container-x">
		<div class="blog-header">
			<div class="max-w-2xl">
				<p class="section-eyebrow">
					<span class="hero-eyebrow-line"></span>
					<?php esc_html_e( 'Writing', 'malachy-portfolio' ); ?>
				</p>
				<h2 class="blog-heading">
					<?php esc_html_e( 'Notes from the shop.', 'malachy-portfolio' ); ?>
				</h2>
			</div>
			<a href="<?php echo esc_url( home_url( '/blog' ) ); ?>" class="blog-all-link">
				<?php esc_html_e( 'View all posts', 'malachy-portfolio' ); ?>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
			</a>
		</div>

		<div class="blog-grid" id="blog-grid">
			<?php
			$recent = new WP_Query(
				array(
					'posts_per_page'      => 3,
					'ignore_sticky_posts' => 1,
				)
			);

			if ( $recent->have_posts() ) :
				while ( $recent->have_posts() ) :
					$recent->the_post();
					$cats = get_the_category();
					$cat  = ! empty( $cats ) ? $cats[0]->name : __( 'Article', 'malachy-portfolio' );
					?>
					<article class="blog-card">
						<div class="blog-card-meta">
							<span><?php echo esc_html( $cat ); ?></span>
							<span class="blog-card-date"><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></span>
						</div>
						<h3 class="blog-card-title"><?php the_title(); ?></h3>
						<p class="blog-card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
						<a href="<?php the_permalink(); ?>" class="blog-card-read">
							<?php esc_html_e( 'Read', 'malachy-portfolio' ); ?>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
						</a>
					</article>
				<?php endwhile;
				wp_reset_postdata();
			else :
				// Fallback hardcoded posts matching React source
				$fallback_posts = array(
					array(
						'cat'   => 'QA & Testing',
						'date'  => 'Mar 15, 2026',
						'title' => 'Why Spec-First Workflows Remain the Default',
						'pitch' => 'On the durable value of writing the test, then the code — even when the code is written by a language model.',
						'link'  => '#',
					),
					array(
						'cat'   => 'Infrastructure',
						'date'  => 'Feb 28, 2026',
						'title' => 'Docker Monitoring Stack in Six Minutes',
						'pitch' => 'Grafana, Prometheus, cAdvisor, Node Exporter — a minimal practical guide to getting observability without the overhead.',
						'link'  => '#',
					),
					array(
						'cat'   => 'AI & Tooling',
						'date'  => 'Feb 10, 2026',
						'title' => 'Agentic Coding is a Conversation, Not a Prompt',
						'pitch' => 'LLMs are not autocomplete — they\'re collaborators. How context engineering changes the quality of what you ship.',
						'link'  => '#',
					),
				);
				foreach ( $fallback_posts as $fb ) :
					?>
					<article class="blog-card">
						<div class="blog-card-meta">
							<span><?php echo esc_html( $fb['cat'] ); ?></span>
							<span class="blog-card-date"><?php echo esc_html( $fb['date'] ); ?></span>
						</div>
						<h3 class="blog-card-title"><?php echo esc_html( $fb['title'] ); ?></h3>
						<p class="blog-card-excerpt"><?php echo esc_html( $fb['pitch'] ); ?></p>
						<a href="<?php echo esc_url( $fb['link'] ); ?>" class="blog-card-read">
							<?php esc_html_e( 'Read', 'malachy-portfolio' ); ?>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
						</a>
					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</section>
