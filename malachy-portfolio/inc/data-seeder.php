<?php
/**
 * CPT Data Seeder — one-time WP-CLI command
 *
 * Seeds Projects, Experience, and Skills CPTs with the
 * existing portfolio content so front-page renders from dynamic data.
 *
 * Usage: wp malachy seed
 *
 * @package Malachy_Portfolio
 * @since  1.0.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the WP-CLI command.
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'malachy', 'Malachy_Seeder' );
}

/**
 * Helper: log a message via WP_CLI if available, otherwise silently ignore.
 */
function malachy_seeder_log( $message ) {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::line( $message );
	}
}

/**
 * Helper: log success via WP_CLI if available.
 */
function malachy_seeder_success( $message ) {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::success( $message );
	}
}

/**
 * Malachy Seeder Command
 */
class Malachy_Seeder {

	/**
	 * Seed all CPTs with default portfolio content.
	 *
	 * ## EXAMPLES
	 *
	 *     wp malachy seed
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function seed( $args, $assoc_args ) {
		$this->seed_projects();
		$this->seed_experience();
		$this->seed_skills();

		malachy_seeder_success( 'All CPTs seeded successfully.' );
	}

	/**
	 * Seed 3 project entries.
	 */
	private function seed_projects() {
		$projects = array(
			array(
				'title'       => 'Test Automation Framework',
				'excerpt'     => 'End-to-end test automation framework built with Playwright, TypeScript, and Docker. Features parallel test execution across 5 environments, CI integration, and AI-augmented flaky test detection.',
				'tag'         => 'QA',
				'tech'        => array( 'Playwright', 'TypeScript', 'Docker', 'GitHub Actions', 'Python', 'Allure' ),
				'url'         => '#',
				'github'      => '#',
				'menu_order'  => 1,
			),
			array(
				'title'       => 'Infrastructure as Code',
				'excerpt'     => 'Server provisioning and configuration management toolkit. Automates deployment of secure Linux servers, Docker hosts, monitoring stacks, and CI/CD runners using Python and Bash.',
				'tag'         => 'DevOps',
				'tech'        => array( 'Linux', 'Docker', 'Python', 'Nginx', 'Ansible', 'Prometheus' ),
				'url'         => '#',
				'github'      => '#',
				'menu_order'  => 2,
			),
			array(
				'title'       => 'Custom WordPress Platform',
				'excerpt'     => 'Full-featured portfolio and blog platform built on WordPress with GSAP animations, dark mode, contact forms, and a custom CPT-driven content architecture. No page builders, no ACF — pure native WordPress.',
				'tag'         => 'Web Dev',
				'tech'        => array( 'WordPress', 'PHP', 'GSAP', 'JavaScript', 'CSS', 'Docker' ),
				'url'         => '#',
				'github'      => '#',
				'menu_order'  => 3,
			),
		);

		$this->create_posts( 'project', $projects, function ( $id, $item ) {
			update_post_meta( $id, '_project_tag', $item['tag'] );
			update_post_meta( $id, '_project_tech', $item['tech'] );
			update_post_meta( $id, '_project_url', $item['url'] );
			update_post_meta( $id, '_project_github', $item['github'] );
		} );

		malachy_seeder_log( '  → 3 projects created.' );
	}

	/**
	 * Seed 4 experience entries.
	 */
	private function seed_experience() {
		$entries = array(
			array(
				'title'      => 'Senior QA Engineer & Systems Consultant',
				'excerpt'    => 'Architecting and deploying test automation frameworks across client projects. Building LLM-integrated QA pipelines and championing spec-first development practices.',
				'org'        => 'IMaD Consulting · London',
				'year'       => '2024 — Present',
				'menu_order' => 1,
			),
			array(
				'title'      => 'QA Automation Engineer',
				'excerpt'    => 'Designed and maintained large-scale test suites for enterprise SaaS products. Reduced regression cycle time by 70% through parallelization and smart test selection algorithms.',
				'org'        => 'Enterprise SaaS · Remote',
				'year'       => '2021 — 2024',
				'menu_order' => 2,
			),
			array(
				'title'      => 'Systems Administrator',
				'excerpt'    => 'Managed 200+ Linux servers across 3 data centres. Implemented automated patching, monitoring, and disaster recovery procedures that achieved 99.99% uptime over 24 months.',
				'org'        => 'Managed Services Firm · London',
				'year'       => '2019 — 2021',
				'menu_order' => 3,
			),
			array(
				'title'      => 'IT Support Engineer',
				'excerpt'    => 'Provided tier-2 and tier-3 support for 800+ users across 15 branches. Led the migration from on-premise Exchange to Microsoft 365, and deployed a company-wide MDM solution.',
				'org'        => 'Regional Bank · Manchester',
				'year'       => '2017 — 2019',
				'menu_order' => 4,
			),
		);

		$this->create_posts( 'experience', $entries, function ( $id, $item ) {
			update_post_meta( $id, '_exp_org', $item['org'] );
			update_post_meta( $id, '_exp_year', $item['year'] );
		} );

		malachy_seeder_log( '  → 4 experience entries created.' );
	}

	/**
	 * Seed 8 skill entries.
	 */
	private function seed_skills() {
		$skills = array(
			array( 'name' => 'QA Automation',       'desc' => 'Playwright, Cypress, Pytest — end-to-end coverage.',              'icon' => 'test' ),
			array( 'name' => 'System Administration', 'desc' => 'Linux, Nginx, Bash, monitoring & hardening.',                     'icon' => 'server' ),
			array( 'name' => 'Containerization',      'desc' => 'Docker, Compose, image optimization, CI pipelines.',              'icon' => 'docker' ),
			array( 'name' => 'Scripting',             'desc' => 'Python and shell for automation & tooling.',                      'icon' => 'code' ),
			array( 'name' => 'Version Control',       'desc' => 'Git workflows, code review, branching strategies, release mgmt.', 'icon' => 'git' ),
			array( 'name' => 'Linux Servers',         'desc' => 'Debian/Ubuntu, security hardening, performance tuning.',          'icon' => 'terminal' ),
			array( 'name' => 'WordPress',             'desc' => 'Custom themes, CPTs, meta boxes, REST API, performance tuning.',  'icon' => 'wordpress' ),
			array( 'name' => 'LLM & Agentic Coding',  'desc' => 'Prompt engineering, context management, multi-agent workflows.',  'icon' => 'brain' ),
		);

		$count = 0;
		foreach ( $skills as $i => $skill ) {
			$existing = get_posts( array(
				'post_type'      => 'skill',
				'title'          => $skill['name'],
				'posts_per_page' => 1,
				'fields'         => 'ids',
			) );

			if ( ! empty( $existing ) ) {
				continue;
			}

			$id = wp_insert_post( array(
				'post_type'    => 'skill',
				'post_title'   => $skill['name'],
				'post_excerpt' => $skill['desc'],
				'post_status'  => 'publish',
				'menu_order'   => $i + 1,
			) );

			if ( $id && ! is_wp_error( $id ) ) {
				update_post_meta( $id, '_skill_icon', $skill['icon'] );
				++$count;
			}
		}

		malachy_seeder_log( "  → {$count} skills created." );
	}

	/**
	 * Generic post creator.
	 *
	 * @param string   $post_type        CPT slug.
	 * @param array    $items            Array of item data arrays.
	 * @param callable $meta_callback    Callback to set meta after insert. Receives ( $post_id, $item ).
	 */
	private function create_posts( $post_type, $items, $meta_callback ) {
		foreach ( $items as $item ) {
			// Check if post with this title already exists.
			$existing = get_posts( array(
				'post_type'      => $post_type,
				'title'          => $item['title'],
				'posts_per_page' => 1,
				'fields'         => 'ids',
			) );

			if ( ! empty( $existing ) ) {
				continue;
			}

			$id = wp_insert_post( array(
				'post_type'    => $post_type,
				'post_title'   => $item['title'],
				'post_excerpt' => $item['excerpt'],
				'post_status'  => 'publish',
				'menu_order'   => $item['menu_order'],
			) );

			if ( $id && ! is_wp_error( $id ) ) {
				$meta_callback( $id, $item );
			}
		}
	}
}
