<?php
/**
 * CPT Data Seeder — one-time WP-CLI command
 *
 * Seeds Projects, Experience, Skills, and Blog Posts with the
 * existing portfolio content so front-page renders from dynamic data.
 *
 * Usage: wp malachy seed
 *
 * @package Malachy_Portfolio
 * @since  1.1.0
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
		$this->seed_blog_posts();

		malachy_seeder_success( 'All data seeded successfully.' );
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
				'image'       => 'project-qa.png',
				'menu_order'  => 1,
			),
			array(
				'title'       => 'Infrastructure as Code',
				'excerpt'     => 'Server provisioning and configuration management toolkit. Automates deployment of secure Linux servers, Docker hosts, monitoring stacks, and CI/CD runners using Python and Bash.',
				'tag'         => 'DevOps',
				'tech'        => array( 'Linux', 'Docker', 'Python', 'Nginx', 'Ansible', 'Prometheus' ),
				'url'         => '#',
				'github'      => '#',
				'image'       => 'project-sysadmin.png',
				'menu_order'  => 2,
			),
			array(
				'title'       => 'Custom WordPress Platform',
				'excerpt'     => 'Full-featured portfolio and blog platform built on WordPress with GSAP animations, dark mode, contact forms, and a custom CPT-driven content architecture. No page builders, no ACF — pure native WordPress.',
				'tag'         => 'Web Dev',
				'tech'        => array( 'WordPress', 'PHP', 'GSAP', 'JavaScript', 'CSS', 'Docker' ),
				'url'         => '#',
				'github'      => '#',
				'image'       => 'project-wordpress.png',
				'menu_order'  => 3,
			),
		);

		$this->create_posts( 'project', $projects, function ( $id, $item ) {
			update_post_meta( $id, '_project_tag', $item['tag'] );
			update_post_meta( $id, '_project_tech', $item['tech'] );
			update_post_meta( $id, '_project_url', $item['url'] );
			update_post_meta( $id, '_project_github', $item['github'] );
			$this->set_featured_image( $id, $item['image'] );
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
	 * Seed 2 blog posts.
	 */
	private function seed_blog_posts() {
		$posts = array(
			array(
				'title'   => 'Diary of an Upwork Newbie: Day 1 to Day 30',
				'content' => "So I joined Upwork. Thirty days later, I have opinions.\n\n<!--more-->\n\n## Week 1: The Humbling\n\nI sent 47 proposals. Got 3 views. Zero replies. My profile photo smiled back at me with what I now recognize as pity.\n\n## Week 2: Strategy Pivot\n\nStopped blasting generic proposals. Started reading the job description like it mattered. Mentioned specific details from their post. Response rate went from 0% to \"someone actually wrote back.\"\n\n## Week 3: First Gig\n\nA WordPress speed optimization project. Small budget, but it was a real client paying real money. I over-delivered, they left a 5-star review, and suddenly the algorithm noticed me.\n\n## Week 4: Momentum\n\nThree active contracts. A repeat client. The dopamine of that \"New Message\" notification when it's a client, not a scammer.\n\n**Lesson:** Upwork rewards patience and specificity, not volume. Quality proposals beat quantity every time.",
				'excerpt'  => 'The first 30 days of freelancing on Upwork — from zero replies to three active contracts. What worked, what didn\'t, and what I wish I knew on Day 1.',
				'date'     => '2026-06-15 10:00:00',
				'status'   => 'publish',
			),
			array(
				'title'   => 'When a Senior Developer Joins Upwork: The Five Stages of Freelance Grief',
				'content' => "You've got 10 years of experience, a GitHub full of green squares, and a resume that makes recruiters swoon. Then you join Upwork and discover you're nobody.\n\n<!--more-->\n\n## Stage 1: Denial\n\n\"My rates are too high? But I'm worth it!\" You see entry-level developers charging $15/hr and scoff. You set your rate at $100/hr. You get zero invites.\n\n## Stage 2: Anger\n\n\"This platform is broken! These clients don't know quality!\" You write a strongly-worded LinkedIn post about the race to the bottom.\n\n## Stage 3: Bargaining\n\nFine. $80/hr. $60/hr. $45/hr. With a premium package. And a discount for the first project. And a satisfaction guarantee.\n\n## Stage 4: Depression\n\nYou check your connects balance. You've burned through 100+ connects. One interview. They went with someone else.\n\n## Stage 5: Acceptance\n\nYou lower your rate to $35/hr, land a small project, crush it, get a 5-star review, and slowly — painfully slowly — build a reputation.\n\nSix months later you're back at $100/hr with a waiting list.\n\n**The moral:** Upwork doesn't care about your resume. It cares about your last completed project. The first one is the hardest. It gets easier.",
				'excerpt'  => 'The emotional journey of an experienced developer discovering that Upwork doesn\'t care about your resume — only your last completed project.',
				'date'     => '2026-06-20 14:30:00',
				'status'   => 'publish',
			),
		);

		$count = 0;
		foreach ( $posts as $p ) {
			$existing = get_posts( array(
				'post_type'      => 'post',
				'title'          => $p['title'],
				'posts_per_page' => 1,
				'fields'         => 'ids',
			) );

			if ( ! empty( $existing ) ) {
				continue;
			}

			$id = wp_insert_post( array(
				'post_type'    => 'post',
				'post_title'   => $p['title'],
				'post_content' => $p['content'],
				'post_excerpt' => $p['excerpt'],
				'post_date'    => $p['date'],
				'post_status'  => $p['status'],
			) );

			if ( $id && ! is_wp_error( $id ) ) {
				++$count;
			}
		}

		malachy_seeder_log( "  → {$count} blog posts created." );
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

	/**
	 * Import a theme image as the post's featured image.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $file    Filename in assets/images/ (e.g. 'project-qa.png').
	 */
	private function set_featured_image( $post_id, $file ) {
		$file_path = MALACHY_THEME_DIR . '/assets/images/' . $file;

		if ( ! file_exists( $file_path ) ) {
			malachy_seeder_log( "    (image not found: {$file})" );
			return;
		}

		// Check if already set
		if ( has_post_thumbnail( $post_id ) ) {
			return;
		}

		$wp_filetype = wp_check_filetype( $file, null );
		$attachment  = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => sanitize_file_name( pathinfo( $file, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $file_path, $post_id );

		if ( is_wp_error( $attach_id ) ) {
			return;
		}

		// Generate attachment metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		set_post_thumbnail( $post_id, $attach_id );
	}
}
