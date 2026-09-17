<?php
/**
 * Template Part: Testimonials Section (HO-019)
 *
 * Pinned parallax + typewriter quotes. Data is rendered server-side from
 * the `testimonial` CPT into data attributes — the JS never hardcodes
 * quotes (avoids a 4th contradicting data source).
 *
 * Falls back to a static stacked list when:
 *  - JS is unavailable (list is visible by default, JS hides it),
 *  - on mobile (GSAP is not enqueued), or
 *  - `prefers-reduced-motion` is set (JS bails early, same as other sections).
 *
 * @package Malachy_Portfolio
 */

// Curated subset per HO-019 §4: narrative order — general reliability →
// specific offer match (Mkenny, strongest, near the end) → close.
$curated_slugs = array(
	'tig-michael',
	'lee-j',
	'mkenny-properties',
	'mimi-r',
);

$quote_payload = array();

$testimonials_query = new WP_Query(
	array(
		'post_type'      => 'testimonial',
		'post_name__in'  => $curated_slugs,
		'posts_per_page' => 4,
		'orderby'        => 'post__in',
		'order'          => 'ASC',
	)
);

// Fallback: if slugs don't match (post names differ), take the first N by menu order.
if ( ! $testimonials_query->have_posts() ) {
	$testimonials_query = new WP_Query(
		array(
			'post_type'      => 'testimonial',
			'posts_per_page' => 4,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);
}

while ( $testimonials_query->have_posts() ) {
	$testimonials_query->the_post();
	$id      = get_the_ID();
	$content = get_the_content( $id );
	if ( ! $content ) {
		$content = get_the_excerpt( $id );
	}
	$quote_payload[] = array(
		'quote'   => $content,
		'author'  => get_the_title( $id ),
		'project' => get_post_meta( $id, '_testimonial_org', true ),
	);
}
wp_reset_postdata();
?>
<section id="testimonials" class="testimonials-pin" aria-labelledby="testimonials-eyebrow"<?php echo ! empty( $quote_payload ) ? ' data-quotes="' . esc_attr( wp_json_encode( $quote_payload ) ) . '"' : ''; ?>>
	<div class="testimonials-bg" aria-hidden="true"></div>
	<div class="testimonials-overlay" aria-hidden="true"></div>

	<div class="testimonials-content section-wrap">
		<p class="section-label"><span>07</span><span><?php esc_html_e( 'What clients say', 'malachy-portfolio' ); ?></span></p>
		<div class="testimonials-stage">
			<h3 class="testimonial-text" aria-live="polite"></h3>
			<span class="typewriter-cursor" aria-hidden="true">|</span>
			<p class="testimonial-attribution"></p>
		</div>
	</div>

	<?php if ( ! empty( $quote_payload ) ) : ?>
		<div class="testimonials-static section-wrap">
			<div class="section-label"><span>07</span><span><?php esc_html_e( 'What clients say', 'malachy-portfolio' ); ?></span></div>
			<?php foreach ( $quote_payload as $q ) : ?>
				<blockquote class="testimonial-static-item">
					<p class="testimonial-static-quote"><?php echo esc_html( $q['quote'] ); ?></p>
					<cite><?php echo esc_html( $q['author'] ); ?><?php echo $q['project'] ? ' — ' . esc_html( $q['project'] ) : ''; ?></cite>
				</blockquote>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
