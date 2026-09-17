<?php
if ( ! defined( 'MALACHY_BOOKING_URL' ) ) {
	define( 'MALACHY_BOOKING_URL', 'https://cal.com/malachy-egbuna' );
}

/**
 * Malachy Portfolio Theme Functions
 *
 * @package Malachy_Portfolio
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MALACHY_THEME_VERSION', '1.3.8' );
define( 'MALACHY_THEME_DIR', get_template_directory() );
define( 'MALACHY_THEME_URI', get_template_directory_uri() );

// ---------------------------------------------------------------------------
// Theme Supports
// ---------------------------------------------------------------------------
add_action( 'after_setup_theme', 'malachy_setup' );

function malachy_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
	add_theme_support( 'custom-logo' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/wordpress-editor.css' );

	register_nav_menus(
		array(
			'primary' => esc_html__( 'Primary Menu', 'malachy-portfolio' ),
		)
	);
}

// ---------------------------------------------------------------------------
// Disable Gutenberg Block Editor
// ---------------------------------------------------------------------------
add_filter( 'use_block_editor_for_post', '__return_false' );
add_filter( 'use_block_editor_for_post_type', '__return_false' );

// ---------------------------------------------------------------------------
// Use home.php template for the Blog page (slug: blog)
// ---------------------------------------------------------------------------
add_filter( 'template_include', function ( $template ) {
	if ( is_page( 'blog' ) ) {
		$blog = locate_template( 'home.php' );
		if ( $blog ) {
			return $blog;
		}
	}
	return $template;
} );

// ---------------------------------------------------------------------------
// Enqueue Assets
// ---------------------------------------------------------------------------
add_action( 'wp_enqueue_scripts', 'malachy_enqueue_assets' );

function malachy_enqueue_assets() {
	// Main Stylesheet
	wp_enqueue_style(
		'malachy-main',
		MALACHY_THEME_URI . '/assets/css/main.css',
		array(),
		MALACHY_THEME_VERSION
	);

	$is_mobile = wp_is_mobile();

	if ( ! $is_mobile ) {
		// GSAP Vendor (bundled copy) — deferred
		wp_enqueue_script(
			'gsap',
			MALACHY_THEME_URI . '/assets/js/vendor/gsap.min.js',
			array(),
			'3.12.5',
			array( 'strategy' => 'defer' )
		);

		wp_enqueue_script(
			'gsap-scroll-trigger',
			MALACHY_THEME_URI . '/assets/js/vendor/ScrollTrigger.min.js',
			array( 'gsap' ),
			'3.12.5',
			array( 'strategy' => 'defer' )
		);

		wp_enqueue_script(
			'gsap-text-plugin',
			MALACHY_THEME_URI . '/assets/js/vendor/TextPlugin.min.js',
			array( 'gsap' ),
			'3.12.5',
			array( 'strategy' => 'defer' )
		);

		// Animation Manager
		wp_enqueue_script(
			'malachy-anim-manager',
			MALACHY_THEME_URI . '/assets/js/animations/AnimationManager.js',
			array( 'gsap', 'gsap-scroll-trigger', 'gsap-text-plugin' ),
			MALACHY_THEME_VERSION,
			true
		);

		// Per-section animation modules — front page only
		if ( is_front_page() ) {
			$animations = array( 'hero', 'about', 'offers', 'skills', 'projects', 'experience', 'testimonials', 'contact', 'global' );
			foreach ( $animations as $a ) {
				wp_enqueue_script(
					"malachy-anim-{$a}",
					MALACHY_THEME_URI . "/assets/js/animations/{$a}.js",
					array( 'gsap', 'gsap-scroll-trigger', 'malachy-anim-manager' ),
					MALACHY_THEME_VERSION,
					true
				);
			}
		}
	}

	// Navigation — loaded everywhere, adapts to GSAP availability
	$nav_deps = $is_mobile ? array() : array( 'gsap', 'gsap-scroll-trigger', 'malachy-anim-manager' );
	wp_enqueue_script(
		'malachy-anim-navigation',
		MALACHY_THEME_URI . '/assets/js/animations/navigation.js',
		$nav_deps,
		MALACHY_THEME_VERSION,
		true
	);

	// Contact form JS — always
	wp_enqueue_script(
		'malachy-contact',
		MALACHY_THEME_URI . '/assets/js/contact.js',
		array(),
		MALACHY_THEME_VERSION,
		true
	);

	wp_localize_script(
		'malachy-contact',
		'malachyAjax',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'malachy_contact_nonce' ),
		)
	);

	// Theme toggle — loaded on all pages
	wp_enqueue_script(
		'malachy-theme-toggle',
		MALACHY_THEME_URI . '/assets/js/theme-toggle.js',
		array(),
		MALACHY_THEME_VERSION,
		true
	);
}

// ---------------------------------------------------------------------------
// Admin Settings Page
// ---------------------------------------------------------------------------
add_action( 'admin_menu', 'malachy_add_settings_page' );

function malachy_add_settings_page() {
	add_options_page(
		'Malachy Portfolio Settings',
		'Malachy Portfolio',
		'manage_options',
		'malachy-portfolio',
		'malachy_render_settings_page'
	);
}

function malachy_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_GET['seed_data'] ) && check_admin_referer( 'malachy_seed_data' ) ) {
		if ( class_exists( 'Malachy_Seeder' ) ) {
			$seeder = new Malachy_Seeder();
			$seeder->seed( array(), array() );
			echo '<div class="notice notice-success is-dismissible"><p>Default data seeded successfully!</p></div>';
		}
	}

	// HO-018 content reconciliation — safe to run repeatedly.
	// Deletes stale experience entries, re-seeds canonical experience +
	// testimonials, fixes the WhatsApp option, flushes the chatbot cache.
	if ( isset( $_GET['reconcile_content'] ) && check_admin_referer( 'malachy_reconcile_content' ) ) {
		$existing_exp = get_posts(
			array(
				'post_type'      => 'experience',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);
		foreach ( $existing_exp as $exp_id ) {
			wp_delete_post( $exp_id, true );
		}

		update_option( 'malachy_whatsapp', '2348164162816', false );
		// Site identity kept in sync with the rebrand (staging parity).
		update_option( 'blogname', 'Reliable - QA Engineer, SysAdmin & IT Support', false );

		if ( class_exists( 'Malachy_Seeder' ) ) {
			$seeder   = new Malachy_Seeder();
			$seeder->seed( array(), array() );
		}

		delete_transient( 'malachy_chat_structured_context' );

		$exp_count  = wp_count_posts( 'experience' )->publish ?? 0;
		$test_count = wp_count_posts( 'testimonial' )->publish ?? 0;
		echo '<div class="notice notice-success is-dismissible"><p>Reconciliation complete — ' . (int) $exp_count . ' experience entries, ' . (int) $test_count . ' testimonials. WhatsApp number updated.</p></div>';
	}
	?>
	<div class="wrap">
		<h1>Malachy Portfolio Settings</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'malachy_theme_settings' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="malachy_portrait">Portrait Image URL</label></th>
					<td><input type="url" id="malachy_portrait" name="malachy_portrait" value="<?php echo esc_attr( get_option( 'malachy_portrait' ) ); ?>" class="regular-text" placeholder="https://.../malachy-portrait.webp" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="malachy_hero_subtitle">Hero Subtitle</label></th>
					<td><input type="text" id="malachy_hero_subtitle" name="malachy_hero_subtitle" value="<?php echo esc_attr( get_option( 'malachy_hero_subtitle' ) ); ?>" class="regular-text" placeholder="Portfolio · 2026" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="malachy_resume_url">Resume / CV File URL</label></th>
					<td><input type="url" id="malachy_resume_url" name="malachy_resume_url" value="<?php echo esc_attr( get_option( 'malachy_resume_url' ) ); ?>" class="regular-text" placeholder="https://.../malachy-cv.pdf" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="malachy_contact_email">Contact Form Email</label></th>
					<td><input type="email" id="malachy_contact_email" name="malachy_contact_email" value="<?php echo esc_attr( get_option( 'malachy_contact_email', 'malachy.egbuna@imadconsulting.co.uk' ) ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="malachy_phone">Phone Number</label></th>
								<td><input type="text" id="malachy_phone" name="malachy_phone" value="<?php echo esc_attr( get_option( 'malachy_phone', '+2348164162816' ) ); ?>" class="regular-text" placeholder="+2348164162816" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="malachy_whatsapp">WhatsApp Number</label></th>
								<td><input type="text" id="malachy_whatsapp" name="malachy_whatsapp" value="<?php echo esc_attr( get_option( 'malachy_whatsapp', '2348164162816' ) ); ?>" class="regular-text" placeholder="2348164162816 (no + or spaces)" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="malachy_twitter">Twitter / X URL</label></th>
								<td><input type="url" id="malachy_twitter" name="malachy_twitter" value="<?php echo esc_attr( get_option( 'malachy_twitter', 'https://x.com/azubike_ike' ) ); ?>" class="regular-text" placeholder="https://x.com/azubike_ike" /></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<hr />
		<h2>Seed Default Data</h2>
		<p>Click below to create default projects, experience entries, and skills. Safe to run multiple times — won't overwrite existing entries.</p>
		<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=malachy-portfolio&seed_data=1' ), 'malachy_seed_data' ) ); ?>" class="button button-primary">Seed Default Data</a>

		<hr />
		<h2>Reconcile Content (HO-018)</h2>
		<p>Replaces stale experience entries with the canonical 7-entry timeline, creates the 9 client testimonials used by the testimonial section and chatbot, fixes the WhatsApp number, and flushes the chatbot cache. Safe to run multiple times.</p>
		<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=malachy-portfolio&reconcile_content=1' ), 'malachy_reconcile_content' ) ); ?>" class="button button-primary">Reconcile Content</a>
	</div>
	<?php
}

// Register contact email setting
add_action( 'admin_init', 'malachy_register_contact_email' );

function malachy_register_contact_email() {
	register_setting(
		'malachy_theme_settings',
		'malachy_contact_email',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_email',
			'default'           => 'malachy.egbuna@imadconsulting.co.uk',
		)
	);
}

// ---------------------------------------------------------------------------
// Include Modules
// ---------------------------------------------------------------------------
require_once MALACHY_THEME_DIR . '/inc/post-types.php';
require_once MALACHY_THEME_DIR . '/inc/meta-boxes.php';
require_once MALACHY_THEME_DIR . '/inc/data-seeder.php';
require_once MALACHY_THEME_DIR . '/inc/contact-handler.php';
require_once MALACHY_THEME_DIR . '/inc/ai-chat-bot.php';
