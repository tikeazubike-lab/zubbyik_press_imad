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
		array( 'name' => 'QA Automation',       'desc' => 'Testing software automatically, so bugs get caught before customers see them.',                 'icon' => 'test' ),
		array( 'name' => 'System Administration', 'desc' => 'Keeping servers running, secure, and monitored — quietly, in the background.',              'icon' => 'server' ),
		array( 'name' => 'Containerization',      'desc' => 'Packaging software so it runs the same way everywhere, and deploying it without drama.',    'icon' => 'docker' ),
		array( 'name' => 'Scripting',             'desc' => 'Writing small programs that handle repetitive work automatically.',                          'icon' => 'code' ),
		array( 'name' => 'Version Control',       'desc' => 'Tracking every code change, reviewing it properly, and rolling it out safely.',             'icon' => 'git' ),
		array( 'name' => 'Linux Servers',         'desc' => 'Setting up and locking down servers so they stay fast, stable, and hard to break into.',   'icon' => 'linux' ),
		array( 'name' => 'WordPress',             'desc' => 'Custom themes and features built to fit exactly what a site needs — not just what a plugin allows.', 'icon' => 'wp' ),
		array( 'name' => 'LLM Integration',       'desc' => 'Connecting AI tools into real software features, not just chat windows.',                   'icon' => 'ai' ),
		array( 'name' => 'Agentic Coding',        'desc' => 'Directing AI coding tools with clear instructions and context, so they build the right thing the first time.', 'icon' => 'ai' ),
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
					<div class="skill-card-icon" aria-hidden="true">
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
		'test'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 3h6v4l4 8a4 4 0 0 1-4 5H9a4 4 0 0 1-4-5l4-8V3Z"/><path d="M9 3h6M12 14v3"/></svg>',
		'server' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="7" rx="2"/><rect x="2" y="14" width="20" height="7" rx="2"/><path d="M6 6.5h.01M6 17.5h.01"/></svg>',
		'code'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
		'docker' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="10" width="5" height="5"/><rect x="8" y="10" width="5" height="5"/><rect x="14" y="10" width="5" height="5"/><rect x="8" y="5" width="5" height="5"/><path d="M19 14c2.5 0 4.5-1 4.5-1s-.5 6-6.5 6H4c-1.5 0-3-1-3-3 0-1.2.8-2.2 2-2.5"/></svg>',
		'ai'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v4"/><path d="M12 18v4"/><path d="M4.93 4.93l2.83 2.83"/><path d="M16.24 16.24l2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/><path d="M4.93 19.07l2.83-2.83"/><path d="M16.24 7.76l2.83-2.83"/><circle cx="12" cy="12" r="4"/></svg>',
		'wp'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M6 11c1 4 3 8 6 8s5-4 6-8c.5-2 0-4-2-5-1.5-.8-3-.5-4 .5s-1.5 2.5-1 4c.3 1 .5 2-.2 3-.5.7-1.5.8-2.2.3-.7-.5-.8-1.5-.3-2.2.5-.7 1.3-1 2-1"/><path d="M12 4v3"/></svg>',
		'git'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="6" cy="18" r="3"/><circle cx="6" cy="6" r="3"/><circle cx="18" cy="12" r="3"/><path d="M6 9v6"/><path d="M9 6h6a3 3 0 0 1 3 3v0a3 3 0 0 1-3 3h-1"/></svg>',
		'linux'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3c2.5 0 4 2 4 4.5S14.5 12 12 12 8 10 8 7.5 9.5 3 12 3Z"/><path d="M5 20c1.5-4 3.5-7 7-7s5.5 3 7 7"/><path d="M10 15c-1 1.5-1.5 3-1.5 5"/><path d="M14 15c1 1.5 1.5 3 1.5 5"/></svg>',
	);

	echo isset( $icons[ $icon ] ) ? $icons[ $icon ] : $icons['code']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
