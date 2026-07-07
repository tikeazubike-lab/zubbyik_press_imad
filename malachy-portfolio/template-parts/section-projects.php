<?php
/**
 * Template Part: Projects Section
 *
 * Source: React Projects.tsx — stacked sticky cards.
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
			'tag'         => get_post_meta( $id, '_project_tag', true ) ?: 'Project',
			'description' => get_the_excerpt(),
			'tech'        => get_post_meta( $id, '_project_tech', true ) ?: array(),
			'url'         => get_post_meta( $id, '_project_url', true ),
			'github'      => get_post_meta( $id, '_project_github', true ),
			'image'       => get_the_post_thumbnail_url( $id, 'large' ) ?: MALACHY_THEME_URI . '/assets/images/project-placeholder.svg',
		);
	}
	wp_reset_postdata();
}

if ( empty( $projects ) ) :
	$projects = array(
		array(
			'title'       => 'Test Automation Framework',
			'tag'         => 'QA Engineering',
			'description' => 'A comprehensive Playwright-based E2E framework for multi-environment regression testing, featuring parallel execution, visual diffing, and CI integration.',
			'tech'        => array( 'Playwright', 'TypeScript', 'Docker', 'GitHub Actions' ),
			'url'         => '#',
			'github'      => '#',
			'image'       => MALACHY_THEME_URI . '/assets/images/project-qa.png',
		),
		array(
			'title'       => 'Infrastructure as Code',
			'tag'         => 'System Administration',
			'description' => 'Automation for server provisioning using Docker Compose, monitoring stacks, and automated backup rotations across 4 environments.',
			'tech'        => array( 'Docker', 'Linux', 'Bash', 'Python' ),
			'url'         => '#',
			'github'      => '#',
			'image'       => MALACHY_THEME_URI . '/assets/images/project-sysadmin.png',
		),
		array(
			'title'       => 'Custom WordPress Platform',
			'tag'         => 'Web Development',
			'description' => 'A bespoke WordPress theme and plugin ecosystem for high-traffic portfolio and directory sites, with GSAP animations and zero page builder reliance.',
			'tech'        => array( 'WordPress', 'PHP', 'GSAP', 'Docker' ),
			'url'         => '#',
			'github'      => '#',
			'image'       => MALACHY_THEME_URI . '/assets/images/project-wordpress.png',
		),
	);
endif;
?>
<section id="projects" class="projects-section" aria-label="<?php esc_attr_e( 'Projects', 'malachy-portfolio' ); ?>">
	<div class="container-x">
		<div class="max-w-2xl">
			<p class="section-eyebrow">
				<span class="hero-eyebrow-line"></span>
				<?php esc_html_e( 'Work', 'malachy-portfolio' ); ?>
			</p>
			<h2 class="projects-heading">
				<?php esc_html_e( 'Recent', 'malachy-portfolio' ); ?>
				<span class="projects-heading-accent"><?php esc_html_e( 'projects.', 'malachy-portfolio' ); ?></span>
			</h2>
		</div>
	</div>

	<div class="projects-stack container-x" id="projects-stack">
		<?php foreach ( $projects as $index => $p ) : ?>
			<article class="proj-card" data-project-index="<?php echo esc_attr( $index ); ?>">
				<div class="proj-card-grid">
					<div class="proj-card-image">
						<img src="<?php echo esc_url( $p['image'] ); ?>" alt="<?php echo esc_attr( $p['title'] ); ?>" loading="lazy" />
					</div>
					<div class="proj-card-body">
						<div class="proj-card-tag"><?php echo esc_html( $p['tag'] ); ?></div>
						<h3 class="proj-card-title"><?php echo esc_html( $p['title'] ); ?></h3>
						<p class="proj-card-desc"><?php echo esc_html( $p['description'] ); ?></p>
						<?php if ( ! empty( $p['tech'] ) ) : ?>
							<ul class="proj-card-tech" aria-label="Technologies used">
								<?php foreach ( $p['tech'] as $tech ) : ?>
									<li><?php echo esc_html( $tech ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<div class="proj-card-actions">
							<?php if ( ! empty( $p['url'] ) && '#' !== $p['url'] ) : ?>
								<a href="<?php echo esc_url( $p['url'] ); ?>" class="proj-card-btn-primary" target="_blank" rel="noopener noreferrer">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
									<?php esc_html_e( 'Live Demo', 'malachy-portfolio' ); ?>
								</a>
							<?php endif; ?>
							<?php if ( ! empty( $p['github'] ) && '#' !== $p['github'] ) : ?>
								<a href="<?php echo esc_url( $p['github'] ); ?>" class="proj-card-btn-outline" target="_blank" rel="noopener noreferrer">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
									<?php esc_html_e( 'Source', 'malachy-portfolio' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
