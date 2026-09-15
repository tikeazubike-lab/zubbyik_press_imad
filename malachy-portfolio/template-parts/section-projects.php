<?php
/**
 * Template Part: Projects Section
 *
 * Reference: polished-portfolio Projects section.
 * Large project cards with image + details.
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
			'tags'        => get_post_meta( $id, '_project_tech', true ) ?: array(),
			'url'         => get_post_meta( $id, '_project_url', true ),
			'image'       => get_the_post_thumbnail_url( $id, 'large' ) ?: MALACHY_THEME_URI . '/assets/images/project-placeholder.svg',
		);
	}
	wp_reset_postdata();
}

if ( empty( $projects ) ) :
	$projects = array(
		array(
			'title'       => 'Test Automation Framework',
			'category'    => 'Quality assurance',
			'description' => 'A comprehensive Playwright-based E2E framework for multi-environment regression testing, featuring parallel execution, visual diffing, and CI integration.',
			'tags'        => array( 'Playwright', 'React', 'Data viz' ),
			'url'         => '#',
			'image'       => MALACHY_THEME_URI . '/assets/images/project-qa.png',
		),
		array(
			'title'       => 'Infrastructure as Code',
			'category'    => 'Web / CMS',
			'description' => 'Automation for server provisioning using Docker Compose, monitoring stacks, and automated backup rotations across 4 environments.',
			'tags'        => array( 'WordPress', 'PHP', 'Performance' ),
			'url'         => '#',
			'image'       => MALACHY_THEME_URI . '/assets/images/project-sysadmin.png',
		),
		array(
			'title'       => 'Custom WordPress Platform',
			'category'    => 'Product systems',
			'description' => 'A bespoke WordPress theme and plugin ecosystem for high-traffic portfolio and directory sites, with GSAP animations and zero page builder reliance.',
			'tags'        => array( 'Product design', 'UX strategy', 'Systems' ),
			'url'         => 'https://imadconsult.zubbystudio.site',
			'image'       => MALACHY_THEME_URI . '/assets/images/project-wordpress.png',
		),
	);
endif;

$total = count( $projects );
?>
<section id="work" class="projects section-wrap" aria-labelledby="projects-title">
	<div class="projects-top">
		<div class="section-label reveal"><span>05</span><span><?php esc_html_e( 'Selected work', 'malachy-portfolio' ); ?></span></div>
		<p class="project-count"><?php echo esc_html( sprintf( '%02d / %02d', $total, $total ) ); ?></p>
	</div>
	<div class="projects-heading reveal">
		<h2 id="projects-title"><?php esc_html_e( 'Recent', 'malachy-portfolio' ); ?> <em><?php esc_html_e( 'projects.', 'malachy-portfolio' ); ?></em></h2>
		<p><?php esc_html_e( 'A few useful things I\'ve helped bring into the world.', 'malachy-portfolio' ); ?></p>
	</div>
	<div class="project-list">
		<?php foreach ( $projects as $index => $project ) : ?>
			<?php
			$project_url = ( ! empty( $project['url'] ) && '#' !== $project['url'] ) ? $project['url'] : home_url( '/#contact' );
			$is_external = strpos( $project_url, home_url() ) === false;
			?>
			<article class="project reveal">
				<div class="project-image-wrap">
					<?php
					$img_base = preg_replace( '/\.[^.]+$/', '', $project['image'] );
					$webp_600 = $img_base . '-600w.webp';
					$webp_900 = $img_base . '-900w.webp';
					$webp_full = $img_base . '.webp';
					$has_webp = file_exists( str_replace( MALACHY_THEME_URI, MALACHY_THEME_DIR, $webp_full ) );
					?>
					<img
						src="<?php echo esc_url( $has_webp ? $webp_full : $project['image'] ); ?>"
						<?php if ( $has_webp ) : ?>
						srcset="<?php echo esc_url( $webp_600 ); ?> 600w, <?php echo esc_url( $webp_900 ); ?> 900w, <?php echo esc_url( $webp_full ); ?> 1200w"
						sizes="(max-width: 800px) 100vw, (max-width: 1200px) 55vw, 720px"
						<?php endif; ?>
						alt="<?php echo esc_attr( $project['title'] ); ?> <?php esc_attr_e( 'case study preview', 'malachy-portfolio' ); ?>"
						loading="lazy"
						decoding="async"
						width="1200"
						height="800" />
					<span class="project-index"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
				</div>
				<div class="project-details">
					<p class="project-category"><?php echo esc_html( $project['category'] ); ?></p>
					<h3><?php echo esc_html( $project['title'] ); ?></h3>
					<p class="project-description"><?php echo esc_html( $project['description'] ); ?></p>
					<div class="project-bottom">
						<?php if ( ! empty( $project['tags'] ) ) : ?>
							<div class="tag-list">
								<?php foreach ( $project['tags'] as $tag ) : ?>
									<span><?php echo esc_html( $tag ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<a href="<?php echo esc_url( $project_url ); ?>" class="btn-ghost" <?php echo $is_external ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
							<?php esc_html_e( 'View case', 'malachy-portfolio' ); ?>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
						</a>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
