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
			'title'       => 'Founder & Systems/QA Consultant',
			'org'         => 'IMaD Consulting',
			'year'        => '2020 – Present',
			'description' => 'Run a small freelance consultancy covering business email setup, DNS security, and web support, alongside AI-assisted automation work. Currently building and maintaining a self-hosted portfolio-tracking application from the ground up — backend, database, deployment, and testing — using a spec-first workflow where AI coding tools do the implementation and every change gets reviewed before it ships. Handle the server it runs on directly: containerized deployment, routing, and backups.',
		),
		array(
			'title'       => 'Test Analyst / User Acceptance Tester',
			'org'         => 'Imad Consulting (clients: zubbystudio, Okra Technology)',
			'year'        => 'Mar 2019 – Oct 2022',
			'description' => 'Documented functional requirements and wrote regression tests for an ERP platform. Worked directly with business owners to turn their needs into test cases, and ran the user-acceptance process that caught serious defects before they reached production.',
		),
		array(
			'title'       => 'Test Analyst / Front-End Development, Freelance',
			'org'         => 'Imad Consulting (client: Fitzdanuk.org)',
			'year'        => 'Jul 2017 – Mar 2019',
			'description' => 'Owned testing end-to-end for a tax reporting system — functional, regression, and integration testing, plus exploratory testing to catch the bugs a checklist wouldn\'t. Wrote the SQL needed to validate data directly against the database, and ran load tests before major releases went live.',
		),
		array(
			'title'       => 'Test Analyst (Planixs)',
			'org'         => 'Planixs (clients: Barclays, RBS, Vodafone, Zenith Bank)',
			'year'        => 'Jul 2017 – Aug 2018',
			'description' => 'Turned tickets into test cases and ran them against a liquidity-reporting content management system, using Selenium and Cucumber alongside manual testing. Wrote the API scripts needed to check the data coming back from the backend actually matched what the front end showed.',
		),
		array(
			'title'       => 'Test Analyst (Parcel Force / NatWest / Quick Light)',
			'org'         => 'Parcel Force / NatWest / Quick Light',
			'year'        => 'Jun 2014 – Aug 2018',
			'description' => 'Several UK test-analyst roles in a row, each following the same core loop: turn requirements into a test plan, run smoke/regression/UAT cycles, and report clear results back to the business so decisions weren\'t made on guesswork.',
		),
		array(
			'title'       => 'Desktop Support Engineer',
			'org'         => 'British Telecoms',
			'year'        => 'Jun 2011 – Dec 2013',
			'description' => 'Supported a Windows server migration from 2003 to 2008 and helped bring order to a support environment that had been struggling to keep up with day-to-day demand.',
		),
		array(
			'title'       => 'Network Administrator',
			'org'         => 'Admiral Insurance',
			'year'        => 'May 2010 – Oct 2012',
			'description' => 'Rolled out and configured pre-built desktop systems for staff, and helped set up regular knowledge-sharing between support staff to speed up problem-solving.',
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
