<?php
/**
 * Template Part: Skills Section
 *
 * Source: React Skills.tsx — 8 skill cards with SVG icons.
 *
 * @package Malachy_Portfolio
 */

// Query skills from CPT if available, otherwise fall back to hardcoded data
$skills_data = array();
$skills_query = new WP_Query(
	array(
		'post_type'      => 'skill',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	)
);

if ( $skills_query->have_posts() ) {
	while ( $skills_query->have_posts() ) {
		$skills_query->the_post();
		$icon = get_post_meta( get_the_ID(), '_skill_icon', true );
		$skills_data[] = array(
			'name' => get_the_title(),
			'desc' => get_the_excerpt(),
			'icon' => $icon ?: 'code',
		);
	}
	wp_reset_postdata();
}

// Fallback hardcoded data matching the React source exactly
if ( empty( $skills_data ) ) :
	$skills_data = array(
		array( 'name' => 'QA Automation',       'desc' => 'Playwright, Cypress, Pytest — end-to-end coverage.',        'icon' => 'test' ),
		array( 'name' => 'System Administration', 'desc' => 'Linux, Nginx, Bash, monitoring & hardening.',               'icon' => 'server' ),
		array( 'name' => 'Containerization',      'desc' => 'Docker, Compose, image optimization, CI pipelines.',        'icon' => 'docker' ),
		array( 'name' => 'Scripting',             'desc' => 'Python and shell for automation & tooling.',                 'icon' => 'code' ),
		array( 'name' => 'Version Control',       'desc' => 'Git workflows, code review, release engineering.',           'icon' => 'git' ),
		array( 'name' => 'Linux Servers',         'desc' => 'Debian/Ubuntu ops, systemd, security patching.',             'icon' => 'linux' ),
		array( 'name' => 'WordPress',             'desc' => 'Custom themes, plugin work, performance tuning.',            'icon' => 'wp' ),
		array( 'name' => 'LLM & Agentic Coding',  'desc' => 'Prompt & context engineering, spec-driven workflows.',      'icon' => 'ai' ),
	);
endif;
?>
<section id="skills" class="skills-section" aria-label="<?php esc_attr_e( 'Skills and toolkit', 'malachy-portfolio' ); ?>">
	<div class="container-x">
		<div class="max-w-2xl">
			<p class="section-eyebrow skill-head">
				<span class="hero-eyebrow-line"></span>
				<?php esc_html_e( 'Toolkit', 'malachy-portfolio' ); ?>
			</p>
			<h2 class="skills-heading skill-head">
				<?php esc_html_e( 'The stack behind the work.', 'malachy-portfolio' ); ?>
			</h2>
		</div>

		<div class="skills-grid" id="skills-grid">
			<?php foreach ( $skills_data as $s ) : ?>
				<article class="skill-card">
					<div class="skill-card-icon">
						<?php malachy_skill_icon( $s['icon'] ); ?>
					</div>
					<h3 class="skill-card-title"><?php echo esc_html( $s['name'] ); ?></h3>
					<p class="skill-card-desc"><?php echo esc_html( $s['desc'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php
/**
 * Render an inline SVG icon for a skill.
 */
function malachy_skill_icon( $icon ) {
	$icons = array(
		'test'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 3h6v4l4 8a4 4 0 0 1-4 5H9a4 4 0 0 1-4-5l4-8V3Z"/><path d="M9 3h6"/></svg>',
		'server' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="6" rx="1"/><rect x="3" y="14" width="18" height="6" rx="1"/><path d="M7 7h.01M7 17h.01"/></svg>',
		'code'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/></svg>',
		'docker' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="10" width="4" height="4"/><rect x="8" y="10" width="4" height="4"/><rect x="13" y="10" width="4" height="4"/><rect x="8" y="5" width="4" height="4"/><path d="M18 14c2 0 4-1 4-1s-.5 6-6 6H4c-1.5 0-2.5-1-2.5-2.5"/></svg>',
		'ai'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M5 19l2-2M17 7l2-2"/></svg>',
		'wp'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="9"/><path d="M4 10l4 10 3-8 3 8 4-10"/></svg>',
		'git'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/><circle cx="12" cy="6" r="2"/><path d="M12 8v6M12 14a4 4 0 0 0 4 4M12 14a4 4 0 0 1-4 4"/></svg>',
		'linux'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 3c2 0 3 2 3 4s-1 4-3 4-3-2-3-4 1-4 3-4Z"/><path d="M6 20c1-4 3-6 6-6s5 2 6 6"/></svg>',
	);

	echo isset( $icons[ $icon ] ) ? $icons[ $icon ] : $icons['code']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
