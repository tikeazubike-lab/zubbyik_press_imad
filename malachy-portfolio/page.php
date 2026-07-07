<?php
/**
 * Page Template
 *
 * @package Malachy_Portfolio
 */

get_header();
?>
<section class="blog-page">
	<div class="container-x">
		<div class="single-post-content" style="padding-top:6rem;padding-bottom:6rem">
			<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
				<h1 class="blog-page-title" style="margin-bottom:2rem"><?php the_title(); ?></h1>
				<?php the_content(); ?>
			<?php endwhile; endif; ?>
		</div>
	</div>
</section>
<?php
get_footer();
