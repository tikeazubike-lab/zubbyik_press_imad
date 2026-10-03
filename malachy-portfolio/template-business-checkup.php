<?php
/**
 * Template Name: Business Checkup
 *
 * Standalone deterministic Business Checkup (HO-061 specification, HO-062 response,
 * HO-063 implementation brief).
 *
 * Deliberately inert on the server: no form posts here, no database writes, no AI call.
 * The eight questions and the scoring live in assets/js/business-checkup.js, the result is
 * rendered client-side, and the handoff goes to the existing contact flow with the campaign
 * parameters carried through. Nothing about the current lead funnel is duplicated.
 *
 * @package Malachy_Portfolio
 */

get_header();

// Reuse the theme's existing centralised WhatsApp setting — never a hard-coded number.
$malachy_checkup_whatsapp = preg_replace( '/[^0-9]/', '', (string) get_option( 'malachy_whatsapp', '2348164162816' ) );
?>
<main
	id="checkup"
	class="checkup-page"
	data-checkup="true"
	data-whatsapp="<?php echo esc_attr( $malachy_checkup_whatsapp ); ?>"
	data-contact-base="<?php echo esc_url( home_url( '/' ) ); ?>"
>
	<div class="container-x">
		<header class="checkup-head">
			<p class="section-label"><?php esc_html_e( 'Business Checkup', 'malachy-portfolio' ); ?></p>
			<h1 class="checkup-title" id="checkup-title"><?php the_title(); ?></h1>
			<p class="checkup-lede">
				<?php esc_html_e( 'A quick, honest look at how customers reach you — and what to fix first.', 'malachy-portfolio' ); ?>
			</p>
		</header>

		<div class="checkup-progress-wrap" id="checkup-progress-wrap" hidden>
			<p class="checkup-progress-label" id="checkup-progress-label"></p>
			<div
				class="checkup-progress"
				id="checkup-progress"
				role="progressbar"
				aria-valuemin="1"
				aria-valuemax="8"
				aria-valuenow="1"
				aria-label="<?php esc_attr_e( 'Checkup progress', 'malachy-portfolio' ); ?>"
			>
				<span class="checkup-progress-bar" id="checkup-progress-bar"></span>
			</div>
		</div>

		<p class="checkup-live" id="checkup-live" aria-live="polite" aria-atomic="true"></p>

		<div class="checkup-stage" id="checkup-stage">
			<p class="checkup-fallback" id="checkup-fallback">
				<?php esc_html_e( 'This checkup needs JavaScript. You can turn it on, or', 'malachy-portfolio' ); ?>
				<a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>"><?php esc_html_e( 'get in touch directly', 'malachy-portfolio' ); ?></a>.
			</p>
		</div>
	</div>
</main>
<?php
get_footer();
