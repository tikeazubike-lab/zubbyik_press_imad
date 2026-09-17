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
			'details'     => get_post_meta( $id, '_exp_tags', true ),
		);
	}
	wp_reset_postdata();
}

if ( empty( $exp ) ) :
	// Trimmed fallback (HO-018) — kept aligned with the canonical seeder
	// data so it can never contradict the real entries again.
	$exp = array(
		array(
			'role'        => 'Founder & Systems/QA Consultant',
			'company'     => 'IMaD Consulting',
			'year'        => '2020 — Present',
			'text'        => 'Consultancy work spanning email/DNS security, web support, and building a self-hosted application with a spec-first, AI-assisted workflow.',
			'details'     => 'Email & DNS · Spec-first AI workflows',
		),
		array(
			'role'        => 'Test Analyst / UAT',
			'company'     => 'Imad Consulting, Planixs, and others',
			'year'        => '2012 — 2020',
			'text'        => 'A decade of UK test-analyst roles: requirements into test cases, regression and UAT cycles, clear reporting back to the business.',
			'details'     => 'UAT · Regression testing · Requirements',
		),
		array(
			'role'        => 'Desktop Support & Network Administration',
			'company'     => 'British Telecoms, Admiral Insurance',
			'year'        => '2001 — 2012',
			'text'        => 'Hands-on support and infrastructure work — server migrations, desktop rollouts, and the fundamentals everything since has built on.',
			'details'     => 'Windows Server · Desktop admin · Knowledge sharing',
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
