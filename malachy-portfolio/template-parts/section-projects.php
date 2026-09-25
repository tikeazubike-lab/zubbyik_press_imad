<?php
/**
 * Template Part: Projects Section — 3D looping card showcase (HO-051).
 *
 * Spec: docs/handover/3D-carousel-for-jobs-section.txt
 * Reference: docs/handover/Webflow-3D-Looping-Card-Animation.png
 *
 * Desktop (>=1200px): borderless two-half showcase — left copy stage
 * (spotlight title + layered parallax description), right 3-card deck that
 * recedes into the dark on a 5s autoplay loop with a clickable dot timeline.
 * Tablet (768–1199px): same composition scaled down, swipe-driven, no autoplay.
 * Mobile (<=767px): full-viewport stacked slides (image + write-up inside the
 * card), native swipe only — no 3D, no peeking cards, no autoplay.
 *
 * @package Malachy_Portfolio
 */

$projects = array();
$proj_query = new WP_Query(
	array(
		'post_type'      => 'project',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	)
);

if ( $proj_query->have_posts() ) {
	while ( $proj_query->have_posts() ) {
		$proj_query->the_post();
		$id = get_the_ID();
		$projects[] = array(
			'title'       => get_the_title(),
			'category'    => get_post_meta( $id, '_project_tag', true ) ?: __( 'Project', 'malachy-portfolio' ),
			'description' => get_the_excerpt(),
			'tldr'        => get_post_meta( $id, '_project_tldr', true ) ?: get_the_excerpt(),
			'image'       => get_post_meta( $id, '_project_image', true ),
			'thumb_id'    => (int) get_post_thumbnail_id( $id ),
		);
	}
	wp_reset_postdata();
}

if ( empty( $projects ) ) :
	// Last-resort fallback when the CPT is empty (staging/local without a seed).
	$projects = array(
		array(
			'title'       => 'Test Automation Framework',
			'category'    => 'Test Engineering',
			'description' => 'A structured, non-negotiable test taxonomy (DOMAIN-WORKFLOW-LAYER-TYPE-NNN) built to catch what mocked tests miss.',
			'tldr'        => 'A DOMAIN-WORKFLOW-LAYER-TYPE taxonomy that catches what mocks miss — proven against a real-database join regression.',
			'image'       => 'work-test-automation-framework',
			'thumb_id'    => 0,
		),
		array(
			'title'       => 'Specforge Tooling',
			'category'    => 'Test Engineering',
			'description' => 'A live pipeline visualizer for Gherkin-driven development — "Swagger for Gherkin." Turns behavioral specs into a visible pipeline instead of a black box.',
			'tldr'        => '"Swagger for Gherkin" — a live visualizer turning behavioral specs into a visible spec → IR → test pipeline.',
			'image'       => 'work-specforge-tooling',
			'thumb_id'    => 0,
		),
		array(
			'title'       => 'Custom WordPress Platform',
			'category'    => 'Web Platforms',
			'description' => 'A bespoke WordPress theme and plugin ecosystem for high-traffic portfolio and directory sites — rich motion and zero page-builder dependency.',
			'tldr'        => 'A bespoke theme + plugin ecosystem for high-traffic sites — rich motion, zero page-builder dependency.',
			'image'       => 'work-custom-wordpress-platform',
			'thumb_id'    => 0,
		),
		array(
			'title'       => 'Estate Portfolio Manager',
			'category'    => 'Web Platforms',
			'description' => 'Self-hosted alternative to Jira Cloud for tracking a single estate\'s stock portfolio. FastAPI backend, React 18 SPA, spec-driven feature workflow.',
			'tldr'        => 'Self-hosted Jira alternative for estate stock tracking — FastAPI + React 18, acceptance-tested at every gate.',
			'image'       => 'work-estate-portfolio-manager',
			'thumb_id'    => 0,
		),
		array(
			'title'       => 'Infrastructure as Code',
			'category'    => 'Automation & Infrastructure',
			'description' => 'A Docker Compose + Traefik stack on a single VPS with automatic HTTPS and one shared PostgreSQL instance serving multiple production apps.',
			'tldr'        => 'One VPS, one Compose file, one shared Postgres — Traefik + Let\'s Encrypt automating HTTPS across every service.',
			'image'       => 'work-infrastructure-as-code',
			'thumb_id'    => 0,
		),
		array(
			'title'       => 'IMAD Consulting Automation Platform',
			'category'    => 'Automation & Infrastructure',
			'description' => 'Self-hosted lead capture and notification system replacing a would-be n8n dependency. Dual-channel delivery that fails independently rather than together.',
			'tldr'        => 'Self-hosted lead capture with dual-channel Telegram + email that fails independently, not together.',
			'image'       => 'work-imad-automation-platform',
			'thumb_id'    => 0,
		),
	);
endif;

$total     = count( $projects );
$first     = $projects[0];
$comma     = __( ', ', 'malachy-portfolio' );
?>

<section id="work" class="projects section-wrap" aria-labelledby="projects-title">
	<div class="projects-top">
		<div class="section-label reveal"><span>05</span><span><?php esc_html_e( 'Selected work', 'malachy-portfolio' ); ?></span></div>
		<p class="project-count" id="work-count" data-jc-counter aria-live="polite" aria-atomic="true"><?php echo esc_html( sprintf( '%02d / %02d', 1, $total ) ); ?></p>
	</div>
	<div class="projects-heading reveal">
		<h2 id="projects-title"><?php esc_html_e( 'Recent', 'malachy-portfolio' ); ?> <em><?php esc_html_e( 'projects.', 'malachy-portfolio' ); ?></em></h2>
		<p><?php esc_html_e( 'A few useful things I\'ve helped bring into the world.', 'malachy-portfolio' ); ?></p>
	</div>

	<div class="jc" data-jc data-jc-total="<?php echo (int) $total; ?>">

		<?php // Left copy stage — desktop/tablet only (mobile write-up lives in each card). ?>
		<div class="jc-copy" data-jc-copy>
			<h3 class="jc-title">
				<span class="jc-cat" data-jc-cat><?php echo esc_html( $first['category'] ); ?></span><span class="jc-dash">–</span><span class="jc-job" data-jc-job><?php echo esc_html( $first['title'] ); ?></span>
			</h3>
			<div class="jc-desc">
				<p class="jc-desc-back" data-jc-desc-back aria-hidden="true"><?php echo esc_html( $first['category'] ); ?></p>
				<p class="jc-desc-mid" data-jc-desc-mid><?php echo esc_html( $first['description'] ); ?></p>
				<p class="jc-desc-front" data-jc-desc-front><?php echo esc_html( $first['tldr'] ); ?></p>
			</div>
		</div>

		<div class="jc-stage" data-jc-stage>
			<div class="jc-track" data-jc-track>
				<?php foreach ( $projects as $index => $project ) : ?>
				<?php
				$slide_label = sprintf(
					/* translators: 1: slide number, 2: total slides, 3: job title */
					__( '%1$d of %2$d: %3$s', 'malachy-portfolio' ),
					$index + 1,
					$total,
					$project['title']
				);

				if ( ! empty( $project['image'] ) ) {
					$src_800  = MALACHY_THEME_URI . '/assets/images/' . $project['image'] . '-800.webp';
					$src_1400 = MALACHY_THEME_URI . '/assets/images/' . $project['image'] . '-1400.webp';
					$img_html = sprintf(
						'<img src="%1$s" srcset="%1$s 800w, %2$s 1400w" sizes="(max-width: 767px) 100vw, 420px" alt="%3$s" class="jc-card-img"%4$s decoding="async">',
						esc_url( $src_800 ),
						esc_url( $src_1400 ),
						esc_attr( $project['title'] ),
						0 === $index ? ' loading="eager" fetchpriority="high"' : ' loading="lazy"'
					);
				} elseif ( $project['thumb_id'] ) {
					$img_html = wp_get_attachment_image(
						$project['thumb_id'],
						'full',
						false,
						array(
							'class'   => 'jc-card-img',
							'loading' => 0 === $index ? 'eager' : 'lazy',
							'alt'     => $project['title'],
						)
					);
				} else {
					$img_html = '';
				}
				?>
				<article
					class="jc-card"
					data-jc-card
					data-index="<?php echo (int) $index; ?>"
					data-category="<?php echo esc_attr( $project['category'] ); ?>"
					data-job="<?php echo esc_attr( $project['title'] ); ?>"
					data-desc="<?php echo esc_attr( $project['description'] ); ?>"
					data-tldr="<?php echo esc_attr( $project['tldr'] ); ?>"
					role="group"
					aria-roledescription="slide"
					aria-label="<?php echo esc_attr( $slide_label ); ?>"
				>
					<div class="jc-card-media">
						<?php echo $img_html; // phpcs:ignore WordPress.Security.EscapeOutput -- assembled above from escaped parts. ?>
					</div>
					<div class="jc-card-body">
						<h4 class="jc-card-job"><?php echo esc_html( $project['title'] ); ?></h4>
						<p class="jc-card-tldr"><?php echo esc_html( $project['tldr'] ); ?></p>
						<div class="jc-card-write">
							<h4 class="jc-write-title"><span class="jc-write-cat"><?php echo esc_html( $project['category'] ); ?></span><span class="jc-dash">–</span><span><?php echo esc_html( $project['title'] ); ?></span></h4>
							<p class="jc-write-desc"><?php echo esc_html( $project['description'] ); ?></p>
						</div>
					</div>
				</article>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="jc-dots" data-jc-dots role="tablist" aria-label="<?php esc_attr_e( 'Go to project', 'malachy-portfolio' ); ?>">
			<?php for ( $i = 0; $i < $total; $i++ ) : ?>
				<button type="button" role="tab" class="jc-dot" data-jc-dot="<?php echo (int) $i; ?>" aria-current="<?php echo 0 === $i ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: project number */ __( 'Project %d', 'malachy-portfolio' ), $i + 1 ) ); ?>"></button>
			<?php endfor; ?>
		</div>
	</div>
</section>
