<?php
/**
 * Template Part: Experience Section
 *
 * Reference: polished-portfolio Experience timeline.
 * Vertical line + nodes.
 *
 * @package Malachy_Portfolio
 */

$exp = array();
$exp_query = new WP_Query(
	array(
		'post_type'      => 'experience',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	)
);

if ( $exp_query->have_posts() ) {
	while ( $exp_query->have_posts() ) {
		$exp_query->the_post();
		$id = get_the_ID();
		$exp[] = array(
			'role'        => get_the_title(),
			'company'     => get_post_meta( $id, '_exp_org', true ),
			'year'        => get_post_meta( $id, '_exp_year', true ),
			'text'        => get_the_excerpt(),
			'details'     => '',
		);
	}
	wp_reset_postdata();
}

if ( empty( $exp ) ) :
	$exp = array(
		array(
			'role'        => 'QA Engineer',
			'company'     => 'Independent / contract',
			'year'        => '2024 — now',
			'text'        => 'Building quality into web products through thoughtful test strategy, automation and documentation.',
			'details'     => 'Playwright · API testing · CI/CD',
		),
		array(
			'role'        => 'Technical Support Engineer',
			'company'     => 'Digital products',
			'year'        => '2022 — 24',
			'text'        => 'Untangling infrastructure and customer issues, then turning patterns into better systems and clearer help.',
			'details'     => 'WordPress · DNS · Linux · Customer success',
		),
		array(
			'role'        => 'IT Support Specialist',
			'company'     => 'Growing teams',
			'year'        => '2020 — 22',
			'text'        => 'Keeping people productive across devices, accounts and the quiet technical details that make work possible.',
			'details'     => 'Endpoint support · Identity · Process',
		),
	);
endif;
?>
<section id="experience" class="experience section-wrap" aria-labelledby="experience-title">
	<div class="section-label reveal"><span>06</span><span><?php esc_html_e( 'A little history', 'malachy-portfolio' ); ?></span></div>
	<div class="experience-heading reveal">
		<h2 id="experience-title">
			<?php esc_html_e( "Where I've", 'malachy-portfolio' ); ?><br />
			<em><?php esc_html_e( 'been.', 'malachy-portfolio' ); ?></em>
		</h2>
		<p><?php esc_html_e( "The roles change. The instinct to make things better doesn't.", 'malachy-portfolio' ); ?></p>
	</div>
	<div class="experience-list">
		<div class="experience-line" aria-hidden="true"></div>
		<?php foreach ( $exp as $item ) : ?>
			<article class="experience-item reveal">
				<div class="experience-year"><?php echo esc_html( $item['year'] ); ?></div>
				<div class="experience-node" aria-hidden="true"></div>
				<div class="experience-copy">
					<p class="experience-role"><?php echo esc_html( $item['role'] ); ?> <span>— <?php echo esc_html( $item['company'] ); ?></span></p>
					<p><?php echo esc_html( $item['text'] ); ?></p>
					<?php if ( ! empty( $item['details'] ) ) : ?>
						<small><?php echo esc_html( $item['details'] ); ?></small>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
