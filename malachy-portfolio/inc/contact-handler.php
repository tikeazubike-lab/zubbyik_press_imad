<?php
/**
 * Contact Form Handler
 *
 * REST API route + admin-ajax handler with nonce verification,
 * honeypot, and rate limiting.
 *
 * @package Malachy_Portfolio
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'malachy_register_contact_route' );
add_action( 'wp_ajax_malachy_send_contact', 'malachy_ajax_contact' );
add_action( 'wp_ajax_nopriv_malachy_send_contact', 'malachy_ajax_contact' );

/**
 * Register REST API route for contact form.
 */
function malachy_register_contact_route() {
	register_rest_route(
		'malachy/v1',
		'/contact',
		array(
			'methods'             => 'POST',
			'callback'            => 'malachy_rest_contact',
			'permission_callback' => function () {
				return wp_verify_nonce( $_POST['malachy_nonce'] ?? '', 'malachy_contact_nonce' );
			},
		)
	);
}

/**
 * REST API contact handler.
 */
function malachy_rest_contact( WP_REST_Request $request ) {
	$params = $request->get_params();
	return malachy_process_contact( $params );
}

/**
 * AJAX contact handler.
 */
function malachy_ajax_contact() {
	$result = malachy_process_contact( $_POST );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	} else {
		wp_send_json_success( array( 'message' => $result ) );
	}
}

/**
 * Process contact form submission.
 *
 * @param array $data Form data.
 * @return string|\WP_Error Success message or error.
 */
function malachy_process_contact( $data ) {
	// Nonce check
	if ( ! isset( $data['malachy_nonce'] ) || ! wp_verify_nonce( $data['malachy_nonce'], 'malachy_contact_nonce' ) ) {
		return new WP_Error( 'invalid_nonce', __( 'Security check failed. Please refresh and try again.', 'malachy-portfolio' ) );
	}

	// Honeypot
	if ( ! empty( $data['malachy_hp'] ) ) {
		return __( 'Thank you! Your message has been sent.', 'malachy-portfolio' ); // Fake success
	}

	// Rate limiting — max 3 messages per IP per hour
	$ip       = malachy_get_client_ip();
	$transient = 'malachy_contact_' . md5( $ip );
	$count    = (int) get_transient( $transient );
	if ( $count >= 3 ) {
		return new WP_Error( 'rate_limit', __( 'You\'ve sent too many messages. Please try again later.', 'malachy-portfolio' ) );
	}
	set_transient( $transient, $count + 1, HOUR_IN_SECONDS );

	// Sanitize
	$name    = isset( $data['malachy_name'] ) ? sanitize_text_field( $data['malachy_name'] ) : '';
	$email   = isset( $data['malachy_email'] ) ? sanitize_email( $data['malachy_email'] ) : '';
	$message = isset( $data['malachy_message'] ) ? sanitize_textarea_field( $data['malachy_message'] ) : '';

	if ( empty( $name ) || empty( $email ) || empty( $message ) ) {
		return new WP_Error( 'missing_fields', __( 'Please fill in all fields.', 'malachy-portfolio' ) );
	}

	if ( ! is_email( $email ) ) {
		return new WP_Error( 'invalid_email', __( 'Please enter a valid email address.', 'malachy-portfolio' ) );
	}

	// Build email
	$to      = get_option( 'admin_email', 'malachy.egbuna@imadconsulting.co.uk' );
	$subject = sprintf(
		/* translators: %s: sender name */
		__( '[Malachy Portfolio] Contact from %s', 'malachy-portfolio' ),
		$name
	);
	$body = sprintf(
		"Name: %s\nEmail: %s\n\nMessage:\n%s",
		$name,
		$email,
		$message
	);
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $email,
	);

	$sent = wp_mail( $to, $subject, $body, $headers );

	if ( $sent ) {
		return __( 'Thanks! I\'ll get back to you soon.', 'malachy-portfolio' );
	}

	return new WP_Error( 'mail_failed', __( 'Could not send message. Please try again later.', 'malachy-portfolio' ) );
}

/**
 * Get client IP address.
 */
function malachy_get_client_ip() {
	$ips = array(
		'HTTP_CF_CONNECTING_IP', // Cloudflare
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_REAL_IP',
		'REMOTE_ADDR',
	);
	foreach ( $ips as $key ) {
		if ( ! empty( $_SERVER[ $key ] ) ) {
			$ip = filter_var( $_SERVER[ $key ], FILTER_VALIDATE_IP );
			if ( $ip ) {
				return $ip;
			}
		}
	}
	return '127.0.0.1';
}
