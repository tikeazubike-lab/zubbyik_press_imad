<?php
/**
 * Single Post Template
 *
 * @package Malachy_Portfolio
 */

get_header();
?>
<section class="blog-page">
	<div class="container-x">
		<div class="single-post-content" style="padding-top:6rem;padding-bottom:6rem">
			<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
				<div class="blog-post-meta">
					<span><?php
					$cats = get_the_category();
					echo esc_html( ! empty( $cats ) ? $cats[0]->name : __( 'Article', 'malachy-portfolio' ) );
					?></span>
					<span class="blog-post-meta-date"><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></span>
				</div>
				<h1 class="blog-page-title" style="margin-bottom:2rem"><?php the_title(); ?></h1>
				<?php the_content(); ?>
			<?php endwhile; endif; ?>
		</div>

		<a href="<?php echo esc_url( home_url( '/blog' ) ); ?>" class="blog-back-link" style="margin-top:2rem;display:inline-flex">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
			<?php esc_html_e( 'Back to blog', 'malachy-portfolio' ); ?>
		</a>
	</div>
</section>
<?php
get_footer();
