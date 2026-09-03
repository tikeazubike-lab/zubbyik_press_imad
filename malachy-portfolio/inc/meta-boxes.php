<?php
/**
 * Native Meta Boxes (no ACF)
 *
 * Custom fields via register_meta + custom meta box UI.
 *
 * @package Malachy_Portfolio
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'add_meta_boxes', 'malachy_register_meta_boxes' );
add_action( 'save_post', 'malachy_save_meta_boxes' );

/**
 * Register all meta boxes.
 */
function malachy_register_meta_boxes() {
	// --- Projects ---
	add_meta_box(
		'malachy_project_meta',
		__( 'Project Details', 'malachy-portfolio' ),
		'malachy_project_meta_cb',
		'project',
		'normal',
		'high'
	);

	// --- Experience ---
	add_meta_box(
		'malachy_exp_meta',
		__( 'Experience Details', 'malachy-portfolio' ),
		'malachy_exp_meta_cb',
		'experience',
		'normal',
		'high'
	);

	// --- Skills ---
	add_meta_box(
		'malachy_skill_meta',
		__( 'Skill Details', 'malachy-portfolio' ),
		'malachy_skill_meta_cb',
		'skill',
		'normal',
		'high'
	);

	// --- Testimonials ---
	add_meta_box(
		'malachy_testimonial_meta',
		__( 'Testimonial Details', 'malachy-portfolio' ),
		'malachy_testimonial_meta_cb',
		'testimonial',
		'normal',
		'high'
	);

	// --- Lead Magnet ---
	add_meta_box(
		'malachy_lead_magnet_meta',
		__( 'Lead Magnet Settings', 'malachy-portfolio' ),
		'malachy_lead_magnet_meta_cb',
		'page',
		'normal',
		'high'
	);

	// --- Theme Settings (registered on dashboard) ---
	add_meta_box(
		'malachy_theme_settings',
		__( 'Portfolio Theme Settings', 'malachy-portfolio' ),
		'malachy_theme_settings_cb',
		'toplevel_page_malachy-settings',
		'normal',
		'high'
	);
}

/**
 * Register meta keys.
 */
add_action( 'init', 'malachy_register_meta_keys' );

function malachy_register_meta_keys() {
	$meta_keys = array(
		// Project fields
		'_project_url'    => 'string',
		'_project_github' => 'string',
		'_project_tag'    => 'string',
		'_project_tech'   => 'array',
		// Experience fields
		'_exp_org'        => 'string',
		'_exp_year'       => 'string',
		// Skill fields
		'_skill_icon'     => 'string',
		'_skill_level'    => 'integer',
		// Testimonial fields
		'_testimonial_role' => 'string',
		'_testimonial_org'  => 'string',
		// Lead magnet fields
		'_lead_magnet_headline'     => 'string',
		'_lead_magnet_bullets'      => 'string',
		'_lead_magnet_list_uuid'    => 'string',
		'_lead_magnet_download_url' => 'string',
		'_lead_magnet_tripwire_slug' => 'string',
	);

	foreach ( $meta_keys as $key => $type ) {
		register_meta(
			'post',
			$key,
			array(
				'type'           => $type,
				'single'         => true,
				'show_in_rest'   => false,
				'auth_callback'  => '__return_true',
			)
		);
	}

	// Theme mods (options, not post meta)
	$theme_mods = array(
		'malachy_portrait'     => 'string',
		'malachy_hero_subtitle' => 'string',
		'malachy_resume_url'   => 'string',
	);
	foreach ( $theme_mods as $key => $type ) {
		register_setting(
			'malachy_theme_settings',
			$key,
			array(
				'type'              => $type,
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
	}
}

// --- Project Meta Box ---

function malachy_project_meta_cb( $post ) {
	wp_nonce_field( 'malachy_meta', 'malachy_meta_nonce' );
	?>
	<table class="form-table">
		<tr>
			<th><label for="_project_tag"><?php esc_html_e( 'Tag / Category Label', 'malachy-portfolio' ); ?></label></th>
			<td><input type="text" id="_project_tag" name="_project_tag" value="<?php echo esc_attr( get_post_meta( $post->ID, '_project_tag', true ) ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="_project_url"><?php esc_html_e( 'Project URL', 'malachy-portfolio' ); ?></label></th>
			<td><input type="url" id="_project_url" name="_project_url" value="<?php echo esc_attr( get_post_meta( $post->ID, '_project_url', true ) ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="_project_github"><?php esc_html_e( 'GitHub URL', 'malachy-portfolio' ); ?></label></th>
			<td><input type="url" id="_project_github" name="_project_github" value="<?php echo esc_attr( get_post_meta( $post->ID, '_project_github', true ) ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label><?php esc_html_e( 'Technology Stack', 'malachy-portfolio' ); ?></label></th>
			<td>
				<?php
				$tech = get_post_meta( $post->ID, '_project_tech', true );
				$tech = is_array( $tech ) ? $tech : array();
				?>
				<div id="tech-stack-repeater">
					<?php foreach ( $tech as $i => $t ) : ?>
						<div class="tech-row" style="margin-bottom:6px">
							<input type="text" name="_project_tech[]" value="<?php echo esc_attr( $t ); ?>" style="width:300px" />
							<button type="button" class="button remove-tech-row" style="margin-left:6px"><?php esc_html_e( 'Remove', 'malachy-portfolio' ); ?></button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button" id="add-tech-row" style="margin-top:4px"><?php esc_html_e( '+ Add Technology', 'malachy-portfolio' ); ?></button>
				<p class="description"><?php esc_html_e( 'e.g. Playwright, Docker, Python', 'malachy-portfolio' ); ?></p>
			</td>
		</tr>
	</table>
	<script>
	jQuery(document).ready(function($) {
		$('#add-tech-row').on('click', function() {
			$('#tech-stack-repeater').append(
				'<div class="tech-row" style="margin-bottom:6px">' +
				'<input type="text" name="_project_tech[]" value="" style="width:300px" /> ' +
				'<button type="button" class="button remove-tech-row" style="margin-left:6px">Remove</button>' +
				'</div>'
			);
		});
		$('#tech-stack-repeater').on('click', '.remove-tech-row', function() {
			$(this).closest('.tech-row').remove();
		});
	});
	</script>
	<?php
}

// --- Experience Meta Box ---

function malachy_exp_meta_cb( $post ) {
	wp_nonce_field( 'malachy_meta', 'malachy_meta_nonce' );
	?>
	<table class="form-table">
		<tr>
			<th><label for="_exp_org"><?php esc_html_e( 'Organization / Company', 'malachy-portfolio' ); ?></label></th>
			<td><input type="text" id="_exp_org" name="_exp_org" value="<?php echo esc_attr( get_post_meta( $post->ID, '_exp_org', true ) ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="_exp_year"><?php esc_html_e( 'Date / Year Range', 'malachy-portfolio' ); ?></label></th>
			<td><input type="text" id="_exp_year" name="_exp_year" value="<?php echo esc_attr( get_post_meta( $post->ID, '_exp_year', true ) ); ?>" class="regular-text" placeholder="e.g. 2024 — Present" /></td>
		</tr>
	</table>
	<?php
}

// --- Skill Meta Box ---

function malachy_skill_meta_cb( $post ) {
	wp_nonce_field( 'malachy_meta', 'malachy_meta_nonce' );
	$icon = get_post_meta( $post->ID, '_skill_icon', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="_skill_icon"><?php esc_html_e( 'Icon', 'malachy-portfolio' ); ?></label></th>
			<td>
				<select id="_skill_icon" name="_skill_icon">
					<?php
					$icons = array( 'test', 'server', 'code', 'docker', 'ai', 'wp', 'git', 'linux' );
					foreach ( $icons as $ic ) {
						echo '<option value="' . esc_attr( $ic ) . '" ' . selected( $icon, $ic, false ) . '>' . esc_html( ucfirst( $ic ) ) . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="_skill_level"><?php esc_html_e( 'Skill Level (1-5)', 'malachy-portfolio' ); ?></label></th>
			<td><input type="number" id="_skill_level" name="_skill_level" min="1" max="5" value="<?php echo esc_attr( get_post_meta( $post->ID, '_skill_level', true ) ?: '3' ); ?>" /></td>
		</tr>
	</table>
	<?php
}

// --- Testimonial Meta Box ---

function malachy_testimonial_meta_cb( $post ) {
	wp_nonce_field( 'malachy_meta', 'malachy_meta_nonce' );
	?>
	<table class="form-table">
		<tr>
			<th><label for="_testimonial_role"><?php esc_html_e( 'Role / Title', 'malachy-portfolio' ); ?></label></th>
			<td><input type="text" id="_testimonial_role" name="_testimonial_role" value="<?php echo esc_attr( get_post_meta( $post->ID, '_testimonial_role', true ) ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="_testimonial_org"><?php esc_html_e( 'Organization', 'malachy-portfolio' ); ?></label></th>
			<td><input type="text" id="_testimonial_org" name="_testimonial_org" value="<?php echo esc_attr( get_post_meta( $post->ID, '_testimonial_org', true ) ); ?>" class="regular-text" /></td>
		</tr>
	</table>
	<?php
}

// --- Lead Magnet Meta Box ---

function malachy_lead_magnet_meta_cb( $post ) {
	wp_nonce_field( 'malachy_meta', 'malachy_meta_nonce' );
	$headline     = get_post_meta( $post->ID, '_lead_magnet_headline', true );
	$bullets      = get_post_meta( $post->ID, '_lead_magnet_bullets', true );
	$list_uuid    = get_post_meta( $post->ID, '_lead_magnet_list_uuid', true );
	$download_url = get_post_meta( $post->ID, '_lead_magnet_download_url', true );
	$tripwire     = get_post_meta( $post->ID, '_lead_magnet_tripwire_slug', true ) ?: 'fix-spam';

	if ( is_array( $bullets ) ) {
		$bullets = implode( "\n", $bullets );
	}
	?>
	<p class="description">
		<?php esc_html_e( 'These fields are used when the page template is set to "Lead Magnet".', 'malachy-portfolio' ); ?>
	</p>
	<table class="form-table">
		<tr>
			<th><label for="_lead_magnet_headline"><?php esc_html_e( 'Headline', 'malachy-portfolio' ); ?></label></th>
			<td>
				<input type="text" id="_lead_magnet_headline" name="_lead_magnet_headline" value="<?php echo esc_attr( $headline ); ?>" class="large-text" placeholder="Get the free email security checklist" />
			</td>
		</tr>
		<tr>
			<th><label for="_lead_magnet_bullets"><?php esc_html_e( 'Bullets', 'malachy-portfolio' ); ?></label></th>
			<td>
				<textarea id="_lead_magnet_bullets" name="_lead_magnet_bullets" class="large-text" rows="5" placeholder="One bullet per line"><?php echo esc_textarea( $bullets ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Enter one bullet per line.', 'malachy-portfolio' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="_lead_magnet_list_uuid"><?php esc_html_e( 'Listmonk List UUID', 'malachy-portfolio' ); ?></label></th>
			<td>
				<input type="text" id="_lead_magnet_list_uuid" name="_lead_magnet_list_uuid" value="<?php echo esc_attr( $list_uuid ); ?>" class="regular-text" />
				<p class="description"><?php esc_html_e( 'Public List UUID from Listmonk.', 'malachy-portfolio' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="_lead_magnet_download_url"><?php esc_html_e( 'Download Redirect URL', 'malachy-portfolio' ); ?></label></th>
			<td>
				<input type="url" id="_lead_magnet_download_url" name="_lead_magnet_download_url" value="<?php echo esc_attr( $download_url ); ?>" class="large-text" placeholder="https://example.com/lead-magnet.pdf" />
				<p class="description"><?php esc_html_e( 'File or external URL to send the subscriber to after confirming opt-in. Passed to the thank-you page as ?download=.', 'malachy-portfolio' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="_lead_magnet_tripwire_slug"><?php esc_html_e( 'Tripwire Service Slug', 'malachy-portfolio' ); ?></label></th>
			<td>
				<input type="text" id="_lead_magnet_tripwire_slug" name="_lead_magnet_tripwire_slug" value="<?php echo esc_attr( $tripwire ); ?>" class="regular-text" />
				<p class="description"><?php esc_html_e( 'Service slug for the "Get started" link below the form (e.g. fix-spam, m365-setup).', 'malachy-portfolio' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

// --- Theme Settings (admin page) ---

function malachy_theme_settings_cb() {
	?>
	<table class="form-table">
		<tr>
			<th><label for="malachy_portrait"><?php esc_html_e( 'Portrait Image URL', 'malachy-portfolio' ); ?></label></th>
			<td><input type="url" id="malachy_portrait" name="malachy_portrait" value="<?php echo esc_attr( get_option( 'malachy_portrait', '' ) ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="malachy_hero_subtitle"><?php esc_html_e( 'Hero Subtitle', 'malachy-portfolio' ); ?></label></th>
			<td><input type="text" id="malachy_hero_subtitle" name="malachy_hero_subtitle" value="<?php echo esc_attr( get_option( 'malachy_hero_subtitle', 'Portfolio · 2026' ) ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="malachy_resume_url"><?php esc_html_e( 'Resume/CV Download URL', 'malachy-portfolio' ); ?></label></th>
			<td><input type="url" id="malachy_resume_url" name="malachy_resume_url" value="<?php echo esc_attr( get_option( 'malachy_resume_url', '' ) ); ?>" class="regular-text" /></td>
		</tr>
	</table>
	<?php
}

// --- Save handler ---

function malachy_save_meta_boxes( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['malachy_meta_nonce'] ) || ! wp_verify_nonce( $_POST['malachy_meta_nonce'], 'malachy_meta' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// String meta keys
	$string_keys = array(
		'_project_url', '_project_github', '_project_tag',
		'_exp_org', '_exp_year', '_skill_icon', '_testimonial_role', '_testimonial_org',
		'_lead_magnet_headline', '_lead_magnet_list_uuid', '_lead_magnet_download_url', '_lead_magnet_tripwire_slug',
	);
	foreach ( $string_keys as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
		}
	}

	// Integer keys
	if ( isset( $_POST['_skill_level'] ) ) {
		update_post_meta( $post_id, '_skill_level', absint( $_POST['_skill_level'] ) );
	}

	// Array keys (tech stack repeater)
	if ( isset( $_POST['_project_tech'] ) && is_array( $_POST['_project_tech'] ) ) {
		$tech = array_map( 'sanitize_text_field', $_POST['_project_tech'] );
		$tech = array_filter( $tech );
		update_post_meta( $post_id, '_project_tech', array_values( $tech ) );
	} else {
		delete_post_meta( $post_id, '_project_tech' );
	}

	// Lead magnet bullets (textarea stored as newline-separated string)
	if ( isset( $_POST['_lead_magnet_bullets'] ) ) {
		update_post_meta( $post_id, '_lead_magnet_bullets', sanitize_textarea_field( $_POST['_lead_magnet_bullets'] ) );
	}
}

// --- Theme Settings Admin Page ---

add_action( 'admin_menu', 'malachy_add_admin_menu' );

function malachy_add_admin_menu() {
	add_menu_page(
		__( 'Malachy Portfolio Settings', 'malachy-portfolio' ),
		__( 'Portfolio', 'malachy-portfolio' ),
		'manage_options',
		'malachy-settings',
		'malachy_settings_page',
		'dashicons-admin-customizer',
		30
	);
}

function malachy_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Malachy Portfolio Settings', 'malachy-portfolio' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'malachy_theme_settings' );
			do_meta_boxes( 'toplevel_page_malachy-settings', 'normal', null );
			submit_button();
			?>
		</form>
	</div>
	<?php
}
