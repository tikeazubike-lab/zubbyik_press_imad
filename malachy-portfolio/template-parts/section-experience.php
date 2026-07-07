<?php
/**
 * Template Part: Experience Section
 *
 * Source: React Experience.tsx — timeline with vertical growing line.
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
			'title'       => get_the_title(),
			'org'         => get_post_meta( $id, '_exp_org', true ),
			'year'        => get_post_meta( $id, '_exp_year', true ),
			'description' => get_the_excerpt(),
		);
	}
	wp_reset_postdata();
}

if ( empty( $exp ) ) :
	$exp = array(
		array(
			'title'       => 'Senior QA Engineer',
			'org'         => 'IMaD Consulting · London',
			'year'        => '2024 — Present',
			'description' => 'Architecting and deploying test automation frameworks across client projects. Building LLM-integrated QA pipelines and championing spec-first development practices.',
		),
		array(
			'title'       => 'Systems Administrator',
			'org'         => 'Freelance / Contract',
			'year'        => '2021 — 2024',
			'description' => 'Managed Linux infrastructure for SMBs — server provisioning, Docker migrations, backup automation, and hardening. Migrated 30+ sites to containerized stacks.',
		),
		array(
			'title'       => 'QA & Automation Lead',
			'org'         => 'Digital Agency · London',
			'year'        => '2019 — 2021',
			'description' => 'Led QA for a portfolio of 15+ client websites and web apps. Introduced Playwright E2E testing, reducing regression time by 80%.',
		),
		array(
			'title'       => 'IT Support Engineer',
			'org'         => 'MSP · Remote',
			'year'        => '2017 — 2019',
			'description' => 'Provided Tier 2-3 support across 50+ client organisations. Automated ticket resolution workflows and built internal documentation systems.',
		),
	);
endif;
?>
<section id="experience" class="experience-section" aria-label="<?php esc_attr_e( 'Experience timeline', 'malachy-portfolio' ); ?>">
	<div class="container-x">
		<div class="experience-inner">
			<p class="section-eyebrow">
				<span class="hero-eyebrow-line"></span>
				<?php esc_html_e( 'Timeline', 'malachy-portfolio' ); ?>
			</p>
			<h2 class="experience-heading">
				<?php esc_html_e( 'Where I\'ve been.', 'malachy-portfolio' ); ?>
			</h2>

			<div class="experience-timeline" id="exp-timeline" style="margin-top:2rem">
				<div aria-hidden="true" class="exp-line-bg"></div>
				<div aria-hidden="true" class="exp-line-fill" id="exp-line-fill"></div>

				<?php foreach ( $exp as $i => $e ) : ?>
					<div class="exp-item" data-exp-index="<?php echo esc_attr( $i ); ?>">
						<div aria-hidden="true" class="exp-item-dot"></div>
						<div class="exp-item-year"><?php echo esc_html( $e['year'] ); ?></div>
						<h3 class="exp-item-role"><?php echo esc_html( $e['title'] ); ?></h3>
						<div class="exp-item-org"><?php echo esc_html( $e['org'] ); ?></div>
						<p class="exp-item-desc"><?php echo esc_html( $e['description'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
