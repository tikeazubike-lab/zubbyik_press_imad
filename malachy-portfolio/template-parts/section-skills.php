<?php
/**
 * Template Part: Skills Section (Stack)
 *
 * Reference: polished-portfolio Stack section.
 * 4-column grid of tool groups with dots.
 *
 * @package Malachy_Portfolio
 */

$stack_groups = array(
	array(
		'label' => __( 'Languages', 'malachy-portfolio' ),
		'items' => array( 'JavaScript', 'TypeScript', 'Python', 'SQL' ),
	),
	array(
		'label' => __( 'Infrastructure', 'malachy-portfolio' ),
		'items' => array( 'GitHub Actions', 'Docker', 'Linux', 'AWS' ),
	),
	array(
		'label' => __( 'Testing', 'malachy-portfolio' ),
		'items' => array( 'Playwright', 'Cypress', 'Postman', 'Jira' ),
	),
	array(
		'label' => __( 'Web / CMS', 'malachy-portfolio' ),
		'items' => array( 'WordPress', 'React', 'Node.js', 'REST APIs' ),
	),
);

// Allow skills from CPT to augment the Web/CMS group if available.
$skills_query = new WP_Query(
	array(
		'post_type'      => 'skill',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	)
);

if ( $skills_query->have_posts() ) {
	$extra = array();
	while ( $skills_query->have_posts() ) {
		$skills_query->the_post();
		$extra[] = get_the_title();
	}
	wp_reset_postdata();
	if ( ! empty( $extra ) ) {
		$stack_groups[3]['items'] = array_slice( array_unique( array_merge( $stack_groups[3]['items'], $extra ) ), 0, 4 );
	}
}
?>
<section id="skills" class="stack section-wrap" aria-labelledby="stack-title">
	<div class="section-label reveal"><span>04</span><span><?php esc_html_e( 'The tools behind it', 'malachy-portfolio' ); ?></span></div>
	<div class="stack-heading reveal">
		<h2 id="stack-title">
			<?php esc_html_e( 'The stack behind', 'malachy-portfolio' ); ?><br />
			<em><?php esc_html_e( 'the work.', 'malachy-portfolio' ); ?></em>
		</h2>
		<p><?php esc_html_e( 'Enough tools to solve the problem. Not so many that the tools become the problem.', 'malachy-portfolio' ); ?></p>
	</div>
	<div class="stack-grid">
		<?php foreach ( $stack_groups as $group ) : ?>
			<div class="stack-group reveal">
				<p class="stack-label"><?php echo esc_html( $group['label'] ); ?></p>
				<?php foreach ( $group['items'] as $item ) : ?>
					<div class="stack-item"><span class="stack-dot" aria-hidden="true"></span><?php echo esc_html( $item ); ?></div>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
