<?php
/**
 * Template Name: Thank You
 *
 * Generic thank-you page for lead magnets.
 * Accepts ?download=URL for optional resource delivery.
 *
 * @package Malachy_Portfolio
 */

get_header();

$download_url = isset( $_GET['download'] ) ? esc_url_raw( urldecode( wp_unslash( $_GET['download'] ) ) ) : '';

?>
<main class="thank-you-page">
	<div class="container-x">
		<div class="thank-you-inner">
			<h1 class="thank-you-heading"><?php esc_html_e( 'Thank you — you are almost done.', 'malachy-portfolio' ); ?></h1>
			<p class="thank-you-message">
				<?php esc_html_e( 'Check your inbox for a confirmation email. Once you confirm, the guide will be on its way.', 'malachy-portfolio' ); ?>
			</p>

			<?php if ( $download_url ) : ?>
				<div class="thank-you-download">
					<a href="<?php echo esc_url( $download_url ); ?>" class="thank-you-download-button" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Download your guide', 'malachy-portfolio' ); ?>
					</a>
				</div>
			<?php endif; ?>

			<div class="thank-you-next">
				<p><?php esc_html_e( 'Want help implementing this?', 'malachy-portfolio' ); ?></p>
				<a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" class="thank-you-cta">
					<?php esc_html_e( 'Get in touch', 'malachy-portfolio' ); ?>
				</a>
			</div>
		</div>
	</div>
</main>
<?php
get_footer();
