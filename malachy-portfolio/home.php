<?php
/**
 * Blog Archive Template (home.php)
 *
 * Source: React routes/blog.tsx — full blog listing page.
 * Works even without the Posts page configured in Reading Settings.
 * Always queries posts explicitly to avoid page-as-post confusion.
 *
 * @package Malachy_Portfolio
 */

get_header();

// Always query posts directly — this works whether home.php is loaded
// as the default posts page, or via template_include for a "Blog" page.
$paged = get_query_var( 'paged', 1 ) ?: 1;
$blog_query = new WP_Query( array(
	'post_type'      => 'post',
	'posts_per_page' => get_option( 'posts_per_page', 10 ),
	'paged'          => $paged,
) );
?>
<section class="blog-page">
	<div class="container-x">
		<div class="blog-page-header">
			<p class="section-eyebrow">
				<span class="hero-eyebrow-line"></span>
				<?php esc_html_e( 'Writing', 'malachy-portfolio' ); ?>
			</p>
			<h1 class="blog-page-title">
				<?php esc_html_e( 'Notes from the shop.', 'malachy-portfolio' ); ?>
			</h1>
		</div>

		<div class="blog-post-list">
			<?php if ( $blog_query->have_posts() ) : ?>
				<?php while ( $blog_query->have_posts() ) : $blog_query->the_post(); ?>
					<article class="blog-post-item">
						<div class="blog-post-meta">
							<span><?php
							$cats = get_the_category();
							echo esc_html( ! empty( $cats ) ? $cats[0]->name : __( 'Article', 'malachy-portfolio' ) );
							?></span>
							<span class="blog-post-meta-date"><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></span>
						</div>
						<h2 class="blog-post-title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>
						<p class="blog-post-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
					</article>
				<?php endwhile; ?>

				<div class="blog-pagination">
					<?php
					$big = 999999999;
					echo paginate_links( array(
						'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
						'format'  => '?paged=%#%',
						'current' => max( 1, $paged ),
						'total'   => $blog_query->max_num_pages,
					) );
					?>
				</div>

			<?php else : ?>
				<p><?php esc_html_e( 'No posts yet. Check back soon.', 'malachy-portfolio' ); ?></p>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</div>

		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="blog-back-link">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
			<?php esc_html_e( 'Back to home', 'malachy-portfolio' ); ?>
		</a>
	</div>
</section>
<?php
get_footer();
