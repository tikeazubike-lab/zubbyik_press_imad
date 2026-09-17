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
		$this->seed_offer_pages();
		$this->seed_thank_you_page();
		$this->seed_testimonials();

		malachy_seeder_success( 'All data seeded successfully.' );
	}

	/**
	 * HO-018 content reconciliation.
	 *
	 * Deletes stale experience CPT entries, re-seeds the canonical
	 * experience timeline + testimonials, fixes the WhatsApp option,
	 * syncs the site title, and flushes the chatbot context cache.
	 * Safe to run repeatedly (identical to the admin "Reconcile Content"
	 * button) — intended for environments without the admin UI.
	 *
	 * ## EXAMPLES
	 *
	 *     wp malachy reconcile
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function reconcile( $args, $assoc_args ) {
		$existing = get_posts(
			array(
				'post_type'      => 'experience',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);
		foreach ( $existing as $post_id ) {
			wp_delete_post( $post_id, true );
		}
		malachy_seeder_log( 'Deleted ' . count( $existing ) . ' stale experience posts.' );

		update_option( 'malachy_whatsapp', '2348164162816', false );
		update_option( 'blogname', 'Reliable - QA Engineer, SysAdmin & IT Support', false );
		malachy_seeder_log( 'WhatsApp option + site title updated.' );

		$this->seed();

		delete_transient( 'malachy_chat_structured_context' );
		malachy_seeder_log( 'Chatbot context cache flushed.' );

		malachy_seeder_success( 'HO-018 reconciliation complete.' );
	}

	/**
	 * Seed offer detail pages.
	 */
	private function seed_offer_pages() {
		$offers = array(
			array(
				'title'     => 'Fix Business Emails Going to Spam',
				'slug'      => 'fix-business-emails-going-to-spam',
				'content'   => "If legitimate emails from your business are landing in spam, the problem is almost always in your domain authentication records: SPF, DKIM, and DMARC.",
				'highlight' => true,
			),
			array(
				'title'     => 'Audit and Report on Your Business Email Security and Deliverability',
				'slug'      => 'audit-and-report-on-your-business-email-security-and-deliverability',
				'content'   => "You get a written audit of your email authentication, DNS records, blacklist status, and spam risk, plus a prioritized list of what to fix.",
				'highlight' => true,
			),
			array(
				'title'     => 'Lock Down Your Domain and DNS Against Spoofing, Hijacking and Email Fraud',
				'slug'      => 'lock-down-your-domain-and-dns-against-spoofing-hijacking-and-email-fraud',
				'content'   => "Harden your domain records and registrar settings against impersonation and takeover.",
				'highlight' => false,
			),
			array(
				'title'     => 'Provide Ongoing Email & DNS Health Monitoring for Your Business',
				'slug'      => 'provide-ongoing-email-and-dns-health-monitoring-for-your-business',
				'content'   => "Monthly monitoring that catches email and DNS problems before they affect your customers.",
				'highlight' => false,
			),
			array(
				'title'     => 'Set Up Microsoft 365 Business Email With Your Custom Domain',
				'slug'      => 'set-up-microsoft-365-business-email-with-your-custom-domain',
				'content'   => "Get professional business email running on your domain with correct DNS and authentication.",
				'highlight' => true,
			),
			array(
				'title'     => 'Migrate Business Email to Microsoft 365, Google Workspace',
				'slug'      => 'migrate-business-email-to-microsoft-365-google-workspace',
				'content'   => "Move mailboxes, calendars, and contacts without downtime or lost messages.",
				'highlight' => true,
			),
			array(
				'title'     => 'Migrate Your Website and Business Email to a New Host',
				'slug'      => 'migrate-your-website-and-business-email-to-a-new-host',
				'content'   => "Relocate your site and email together with minimal disruption and proper DNS handover.",
				'highlight' => true,
			),
			array(
				'title'     => 'Migrate and Rebuild a WordPress Site Onto a Modern Stack for AI Chatbot Integration',
				'slug'      => 'migrate-and-rebuild-a-wordpress-site-onto-a-modern-stack-for-ai-chatbot-integration',
				'content'   => "A full rebuild on a modern, maintainable stack with AI chatbot integration built in.",
				'highlight' => true,
			),
			array(
				'title'     => 'Assess Your WordPress Site for AI Chatbot Integration',
				'slug'      => 'assess-your-wordpress-site-for-ai-chatbot-integration',
				'content'   => "A structured review of whether your site is ready for an AI chatbot and what it would take.",
				'highlight' => false,
			),
		);

		$count = 0;
		foreach ( $offers as $offer ) {
			$existing = get_posts( array(
				'post_type'      => 'page',
				'name'           => $offer['slug'],
				'posts_per_page' => 1,
				'fields'         => 'ids',
			) );

			if ( ! empty( $existing ) ) {
				continue;
			}

			$badge = $offer['highlight']
				? '<p><span class="badge-highlight">Most Requested</span></p>' . "\n\n"
				: '';

			$id = wp_insert_post( array(
				'post_type'    => 'page',
				'post_title'   => $offer['title'],
				'post_name'    => $offer['slug'],
				'post_content' => $badge . $offer['content'] . "\n\n<a href=\"" . home_url( '/?service=' . $offer['slug'] . '#contact' ) . "\">Get started</a>",
				'post_status'  => 'publish',
			) );

			if ( $id && ! is_wp_error( $id ) ) {
				++$count;
			}
		}

		malachy_seeder_log( "  → {$count} offer pages created." );
	}

	/**
	 * Seed a generic thank-you page.
	 */
	private function seed_thank_you_page() {
		$existing = get_posts( array(
			'post_type'      => 'page',
			'name'           => 'thank-you',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		) );

		if ( ! empty( $existing ) ) {
			malachy_seeder_log( '  → thank-you page already exists.' );
			return;
		}

		$id = wp_insert_post( array(
			'post_type'    => 'page',
			'post_title'   => 'Thank You',
			'post_name'    => 'thank-you',
			'post_content' => '',
			'post_status'  => 'publish',
			'page_template' => 'template-thank-you.php',
		) );

		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_wp_page_template', 'template-thank-you.php' );
			malachy_seeder_log( '  → thank-you page created.' );
		}
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
				'github'      => 'https://github.com/zubbyik',
				'image'       => 'project-qa.png',
				'menu_order'  => 1,
			),
			array(
				'title'       => 'Infrastructure as Code',
				'excerpt'     => 'Server provisioning and configuration management toolkit. Automates deployment of secure Linux servers, Docker hosts, monitoring stacks, and CI/CD runners using Python and Bash.',
				'tag'         => 'DevOps',
				'tech'        => array( 'Linux', 'Docker', 'Python', 'Nginx', 'Ansible', 'Prometheus' ),
				'url'         => '#',
				'github'      => 'https://github.com/zubbyik',
				'image'       => 'project-sysadmin.png',
				'menu_order'  => 2,
			),
			array(
				'title'       => 'Custom WordPress Platform',
				'excerpt'     => 'Full-featured portfolio and blog platform built on WordPress with GSAP animations, dark mode, contact forms, and a custom CPT-driven content architecture. No page builders, no ACF — pure native WordPress.',
				'tag'         => 'Web Dev',
				'tech'        => array( 'WordPress', 'PHP', 'GSAP', 'JavaScript', 'CSS', 'Docker' ),
				'url'         => 'https://imadconsult.zubbystudio.site',
				'github'      => 'https://github.com/zubbyik',
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
	 * Seed 7 canonical experience entries (HO-018).
	 */
	private function seed_experience() {
		$entries = array(
			array(
				'title'      => 'Founder & Systems/QA Consultant',
				'excerpt'    => 'Run a small consultancy covering business email setup, DNS security, and web support, alongside AI-assisted automation work. Currently building and maintaining a self-hosted portfolio-tracking application end to end — backend, deployment, and testing — using a spec-first workflow where AI coding tools handle implementation and every change gets reviewed before it ships.',
				'org'        => 'IMaD Consulting',
				'year'       => '2020 – Present',
				'tags'       => 'Email & DNS · Docker · Spec-first AI workflows',
				'menu_order' => 1,
			),
			array(
				'title'      => 'Test Analyst / UAT',
				'excerpt'    => 'Documented functional requirements and wrote regression tests for an ERP platform. Worked directly with business owners to turn their needs into test cases, and ran the user-acceptance process that caught serious defects before they reached production.',
				'org'        => 'Imad Consulting (clients: zubbystudio, Okra Technology)',
				'year'       => 'March 2017 – October 2020',
				'tags'       => 'UAT · Regression testing · Requirements',
				'menu_order' => 2,
			),
			array(
				'title'      => 'Test Analyst / Front-End Development',
				'excerpt'    => 'Owned testing end-to-end for a tax reporting system — functional, regression, and integration testing, plus exploratory testing. Wrote SQL to validate data directly against the database and ran load tests before major releases.',
				'org'        => 'Imad Consulting (client: Fitzdanuk.org)',
				'year'       => 'July 2015 – March 2017',
				'tags'       => 'SQL · Load testing · Exploratory QA',
				'menu_order' => 3,
			),
			array(
				'title'      => 'Test Analyst (Planixs)',
				'excerpt'    => 'Turned tickets into test cases for a liquidity-reporting system, using Selenium and Cucumber alongside manual testing. Wrote API scripts to verify backend data matched what the front end displayed.',
				'org'        => 'Planixs (clients: Barclays, RBS, Vodafone, Zenith Bank)',
				'year'       => 'July 2015 – August 2016',
				'tags'       => 'Selenium · Cucumber · API testing',
				'menu_order' => 4,
			),
			array(
				'title'      => 'Test Analyst (Parcel Force / NatWest / Quick Light)',
				'excerpt'    => 'A run of UK test-analyst roles: turning requirements into test plans, running smoke/regression/UAT cycles, and reporting clear results back to the business.',
				'org'        => 'Parcel Force / NatWest / Quick Light',
				'year'       => 'June 2012 – August 2016',
				'tags'       => 'Test planning · UAT · Regression',
				'menu_order' => 5,
			),
			array(
				'title'      => 'Desktop Support Engineer',
				'excerpt'    => 'Supported a Windows server migration from 2003 to 2008 and helped bring order to a support environment under heavy day-to-day demand.',
				'org'        => 'British Telecoms',
				'year'       => 'June 2008 – December 2012',
				'tags'       => 'Windows Server · Migration · Support',
				'menu_order' => 6,
			),
			array(
				'title'      => 'Network Administrator',
				'excerpt'    => 'Rolled out and configured desktop systems for staff, and helped set up regular knowledge-sharing between support staff to speed up problem-solving.',
				'org'        => 'Admiral Insurance',
				'year'       => 'May 2001 – October 2004',
				'tags'       => 'Desktop admin · Knowledge sharing',
				'menu_order' => 7,
			),
		);

		$this->create_posts( 'experience', $entries, function ( $id, $item ) {
			update_post_meta( $id, '_exp_org', $item['org'] );
			update_post_meta( $id, '_exp_year', $item['year'] );
			update_post_meta( $id, '_exp_tags', $item['tags'] );
		} );

		malachy_seeder_log( '  → 7 experience entries created.' );
	}

	/**
	 * Seed 9 client testimonials (HO-018).
	 *
	 * Sources: PeopleWorkPerHour (4) + Upwork relayed from client confirmation (5).
	 * The chatbot is the only consumer of testimonial CPT posts.
	 */
	private function seed_testimonials() {
		$testimonials = array(
			// — PeopleWorkPerHour —
			array(
				'title'      => 'Nicholas R.',
				'excerpt'    => 'Personal and on-hand.',
				'role'       => 'London, GB · Rating: 5/5 · Aug 2018',
				'org'        => 'Google AdWords event snippet implementation, Shopify store',
				'menu_order' => 1,
			),
			array(
				'title'      => 'Mimi R.',
				'excerpt'    => 'Efficient and wonderful help! :)',
				'role'       => 'Zagreb, HR · Rating: 5/5 · Nov 2017',
				'org'        => 'Shopify store setup (payment gateways, tax configuration)',
				'menu_order' => 2,
			),
			array(
				'title'      => 'Lee J.',
				'excerpt'    => 'We did run into difficulties but turned out to be a server side issue. Certainly can\'t question Malachy\'s work ethic, and professionalism.',
				'role'       => 'London, GB · Rating: 4/5 · Nov 2017',
				'org'        => 'Web.Config configuration, 301 redirects',
				'menu_order' => 3,
			),
			array(
				'title'      => 'Greg W.',
				'excerpt'    => 'Great!',
				'role'       => 'Birmingham, GB · Rating: 5/5 · Oct 2017',
				'org'        => 'WordPress edits',
				'menu_order' => 4,
			),
			// — Upwork (relayed, client-confirmed) —
			array(
				'title'      => 'Mkenny Properties',
				'excerpt'    => 'Malachy helped us migrate our property management website from IONOS (1&1) to InMotion Hosting. He also transferred our business email during the move and resolved an initial deliverability issue that came up — everything\'s been running smoothly since.',
				'role'       => 'Upwork client',
				'org'        => 'Website migration & email hosting setup',
				'menu_order' => 5,
			),
			array(
				'title'      => 'Tig Michael',
				'excerpt'    => 'Malachy tested our website thoroughly and caught bugs we hadn\'t noticed ourselves — issues that could have turned into costly problems down the line. Glad we had a second set of eyes on it before launch.',
				'role'       => 'Upwork client',
				'org'        => 'Website testing & bug fixes',
				'menu_order' => 6,
			),
			array(
				'title'      => 'Dimitry V.',
				'excerpt'    => 'Malachy helped fix a stubborn 301 redirect error on my site. It took some back-and-forth with my hosting provider\'s support team, but he stayed frank and transparent throughout the process and got it resolved.',
				'role'       => 'Upwork client',
				'org'        => '301 redirect error troubleshooting',
				'menu_order' => 7,
			),
			array(
				'title'      => 'Stanley H.',
				'excerpt'    => 'Malachy integrated Stripe into my Shopify store. Professional from start to finish — clear communication and delivered right on schedule.',
				'role'       => 'Upwork client',
				'org'        => 'Stripe payment gateway integration (Shopify)',
				'menu_order' => 8,
			),
			array(
				'title'      => 'Gracilis (Dishusbandmata)',
				'excerpt'    => 'Malachy built my website from the ground up: logo design, layout, and all the content. He was genuinely open to learning throughout, and still delivered on schedule. Really happy with the result.',
				'role'       => 'Upwork client',
				'org'        => 'Full website build — design, logo, content & layout',
				'menu_order' => 9,
			),
		);

		$this->create_posts( 'testimonial', $testimonials, function ( $id, $item ) {
			// create_posts stores the quote in the excerpt; mirror it to
			// post_content so the chatbot reads it via get_the_content().
			wp_update_post( array( 'ID' => $id, 'post_content' => $item['excerpt'] ) );
			update_post_meta( $id, '_testimonial_role', $item['role'] );
			update_post_meta( $id, '_testimonial_org', $item['org'] );
		} );

		malachy_seeder_log( '  → 9 testimonials created.' );
	}

	/**
	 * Seed 8 skill entries.
	 */
	private function seed_skills() {
		$skills = array(
			array( 'name' => 'QA Automation',       'desc' => 'Testing software automatically, so bugs get caught before customers see them.',                 'icon' => 'test' ),
			array( 'name' => 'System Administration', 'desc' => 'Keeping servers running, secure, and monitored — quietly, in the background.',              'icon' => 'server' ),
			array( 'name' => 'Containerization',      'desc' => 'Packaging software so it runs the same way everywhere, and deploying it without drama.',    'icon' => 'docker' ),
			array( 'name' => 'Scripting',             'desc' => 'Writing small programs that handle repetitive work automatically.',                          'icon' => 'code' ),
			array( 'name' => 'Version Control',       'desc' => 'Tracking every code change, reviewing it properly, and rolling it out safely.',             'icon' => 'git' ),
			array( 'name' => 'Linux Servers',         'desc' => 'Setting up and locking down servers so they stay fast, stable, and hard to break into.',   'icon' => 'terminal' ),
			array( 'name' => 'WordPress',             'desc' => 'Custom themes and features built to fit exactly what a site needs — not just what a plugin allows.', 'icon' => 'wordpress' ),
			array( 'name' => 'LLM Integration',       'desc' => 'Connecting AI tools into real software features, not just chat windows.',                   'icon' => 'brain' ),
			array( 'name' => 'Agentic Coding',        'desc' => 'Directing AI coding tools with clear instructions and context, so they build the right thing the first time.', 'icon' => 'brain' ),
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
		$notes = get_category_by_slug( 'notes' );
		if ( ! $notes ) {
			$notes_id = wp_insert_category( array(
				'cat_name'          => 'Notes',
				'category_nicename' => 'notes',
				'category_description' => 'Short notes and observations.',
			) );
		} else {
			$notes_id = $notes->term_id;
		}

		foreach ( $posts as $p ) {
			$existing = get_posts( array(
				'post_type'      => 'post',
				'title'          => $p['title'],
				'posts_per_page' => 1,
				'fields'         => 'ids',
			) );

			if ( ! empty( $existing ) ) {
				// Recategorize existing blog posts under Notes if needed.
				foreach ( $existing as $existing_id ) {
					$categories = wp_get_post_categories( $existing_id, array( 'fields' => 'ids' ) );
					if ( ! in_array( (int) $notes_id, $categories, true ) ) {
						wp_set_post_categories( $existing_id, array( (int) $notes_id ) );
					}
				}
				continue;
			}

			$id = wp_insert_post( array(
				'post_type'    => 'post',
				'post_title'   => $p['title'],
				'post_content' => $p['content'],
				'post_excerpt' => $p['excerpt'],
				'post_date'    => $p['date'],
				'post_status'  => $p['status'],
				'post_category' => array( (int) $notes_id ),
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
		$source_path = MALACHY_THEME_DIR . '/assets/images/' . $file;

		if ( ! file_exists( $source_path ) ) {
			malachy_seeder_log( "    (image not found: {$file})" );
			return;
		}

		// Check if already set
		if ( has_post_thumbnail( $post_id ) ) {
			return;
		}

		// Copy to uploads dir so WordPress can process it correctly.
		$upload_dir  = wp_upload_dir();
		$target_path = $upload_dir['path'] . '/' . sanitize_file_name( basename( $file ) );
		copy( $source_path, $target_path );

		$wp_filetype = wp_check_filetype( basename( $target_path ), null );
		$attachment  = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => sanitize_file_name( pathinfo( basename( $target_path ), PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $target_path, $post_id );

		if ( is_wp_error( $attach_id ) ) {
			return;
		}

		// Generate attachment metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attach_id, $target_path );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		set_post_thumbnail( $post_id, $attach_id );
	}
}
