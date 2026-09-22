<?php
/**
 * Template Part: Hero Section
 *
 * Reference: polished-portfolio hero — large title, serif accent,
 * role line, two CTAs, curved portrait mask, scroll hint footer.
 *
 * Hero image src/srcset/sizes/alt and text content are preserved.
 *
 * @package Malachy_Portfolio
 */

$portrait_url  = get_theme_mod( 'malachy_portrait', MALACHY_THEME_URI . '/assets/images/malachy-portrait.webp' );
$hero_subtitle = get_theme_mod( 'malachy_hero_subtitle', __( 'Portfolio · 2026', 'malachy-portfolio' ) );
$phone         = get_option( 'malachy_phone', '+2348164162816' );
$whatsapp      = get_option( 'malachy_whatsapp', '2348164162816' );
$twitter       = get_option( 'malachy_twitter', 'https://x.com/azubike_ike' );
$booking_url   = defined( 'MALACHY_BOOKING_URL' ) ? MALACHY_BOOKING_URL : 'https://cal.com/malachy-egbuna';
?>
<section id="home" class="hero section-wrap" aria-labelledby="hero-title">
	<div class="hero-copy-wrap">
		<p class="eyebrow hero-kicker">
			<span class="eyebrow-mark"></span>
			<?php echo esc_html( $hero_subtitle ); ?>
		</p>
		<h1 id="hero-title" class="hero-title">
			<?php esc_html_e( "Hi, I'm", 'malachy-portfolio' ); ?><br />
			<em><?php esc_html_e( 'Malachy.', 'malachy-portfolio' ); ?></em>
		</h1>
		<p class="hero-role">
			<?php esc_html_e( 'QA Engineer', 'malachy-portfolio' ); ?>
			<span>·</span>
			<?php esc_html_e( 'System Admin', 'malachy-portfolio' ); ?>
			<span>·</span>
			<?php esc_html_e( 'IT Support Specialist', 'malachy-portfolio' ); ?>
		</p>
		<p class="hero-copy">
			<?php esc_html_e( "I engineer confidence into software and infrastructure — from automated test suites to resilient servers, with a fondness for LLM-driven, spec-first workflows.", 'malachy-portfolio' ); ?>
		</p>
		<div class="hero-actions">
			<a href="<?php echo esc_url( home_url( '/#work' ) ); ?>" class="btn-primary" data-section-link="work">
				<?php esc_html_e( 'View My Projects', 'malachy-portfolio' ); ?>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
			</a>
			<button type="button" class="btn-ghost" id="open-discovery" aria-expanded="false" aria-controls="discovery-panel">
				<?php esc_html_e( 'Book a Discovery Call', 'malachy-portfolio' ); ?>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
			</button>
		</div>
	</div>

	<div class="hero-visual">
		<img src="<?php echo esc_url( $portrait_url ); ?>"
			srcset="<?php echo esc_url( MALACHY_THEME_URI . '/assets/images/malachy-portrait-400w.webp' ); ?> 400w,
			        <?php echo esc_url( MALACHY_THEME_URI . '/assets/images/malachy-portrait-600w.webp' ); ?> 600w,
			        <?php echo esc_url( MALACHY_THEME_URI . '/assets/images/malachy-portrait-1024w.webp' ); ?> 1024w"
			sizes="(max-width: 800px) 100vw, (max-width: 1200px) 70vw, 980px"
			class="hero-portrait" alt="Malachy Egbuna — portrait photo" draggable="false" width="600" height="800" fetchpriority="high" />
		<p class="portrait-note">
			<?php esc_html_e( 'Based in Manchester', 'malachy-portfolio' ); ?><br />
			<span><?php esc_html_e( 'Working everywhere', 'malachy-portfolio' ); ?></span>
		</p>
	</div>

	<div class="hero-footer">
		<span><?php esc_html_e( 'Scroll to explore', 'malachy-portfolio' ); ?></span>
		<span class="hero-rule"></span>
		<span>01 / 08</span>
	</div>
</section>

<!-- Discovery Call Panel -->
<div class="discovery-overlay" id="discovery-overlay" aria-hidden="true"></div>
<aside class="discovery-panel" id="discovery-panel" role="dialog" aria-label="<?php esc_attr_e( 'Book a Discovery Call', 'malachy-portfolio' ); ?>" aria-hidden="true">
	<div class="discovery-panel-inner">
		<div class="discovery-header">
			<h2 class="discovery-title"><?php esc_html_e( 'Book a Discovery Call', 'malachy-portfolio' ); ?></h2>
			<button type="button" class="discovery-close" id="close-discovery" aria-label="<?php esc_attr_e( 'Close', 'malachy-portfolio' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
			</button>
		</div>

		<div class="discovery-contacts">
			<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>" class="discovery-contact-link">
				<span class="discovery-contact-icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
				</span>
				<span class="discovery-contact-label"><?php esc_html_e( 'Phone', 'malachy-portfolio' ); ?></span>
				<span class="discovery-contact-value"><?php echo esc_html( $phone ); ?></span>
			</a>
			<a href="https://wa.me/<?php echo esc_attr( $whatsapp ); ?>" class="discovery-contact-link" target="_blank" rel="noopener noreferrer">
				<span class="discovery-contact-icon">
					<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
				</span>
				<span class="discovery-contact-label"><?php esc_html_e( 'WhatsApp', 'malachy-portfolio' ); ?></span>
				<span class="discovery-contact-value"><?php esc_html_e( 'Send a message', 'malachy-portfolio' ); ?></span>
			</a>
			<a href="<?php echo esc_url( $twitter ); ?>" class="discovery-contact-link" target="_blank" rel="noopener noreferrer">
				<span class="discovery-contact-icon">
					<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
				</span>
				<span class="discovery-contact-label"><?php esc_html_e( 'Twitter / X', 'malachy-portfolio' ); ?></span>
				<span class="discovery-contact-value"><?php echo esc_html( '@' . preg_replace( '#.*/#', '', rtrim( $twitter, '/' ) ) ); ?></span>
			</a>
		</div>

		<div class="discovery-divider"><span><?php esc_html_e( 'or send a message', 'malachy-portfolio' ); ?></span></div>

		<form class="discovery-form" id="discovery-form">
			<?php wp_nonce_field( 'malachy_contact_nonce', 'malachy_nonce' ); ?>
			<input type="hidden" name="action" value="malachy_send_contact" />
			<div style="position:absolute;left:-9999px" aria-hidden="true">
				<input type="text" name="malachy_hp" tabindex="-1" autocomplete="off" />
			</div>
			<div class="discovery-field">
				<label for="discovery-name"><?php esc_html_e( 'Name', 'malachy-portfolio' ); ?></label>
				<input type="text" id="discovery-name" name="malachy_name" placeholder="<?php esc_attr_e( 'Your name', 'malachy-portfolio' ); ?>" required />
			</div>
			<div class="discovery-field">
				<label for="discovery-email"><?php esc_html_e( 'Email', 'malachy-portfolio' ); ?></label>
				<input type="email" id="discovery-email" name="malachy_email" placeholder="<?php esc_attr_e( 'your@email.com', 'malachy-portfolio' ); ?>" required />
			</div>
			<div class="discovery-field">
				<label for="discovery-message"><?php esc_html_e( 'Message', 'malachy-portfolio' ); ?></label>
				<textarea id="discovery-message" name="malachy_message" rows="4" placeholder="<?php esc_attr_e( 'Tell me about your project...', 'malachy-portfolio' ); ?>" required></textarea>
			</div>
			<button type="submit" class="discovery-submit" id="discovery-submit">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
				<?php esc_html_e( 'Send Message', 'malachy-portfolio' ); ?>
			</button>
			<div id="discovery-status" class="discovery-status"></div>
		</form>

		<a href="<?php echo esc_url( $booking_url ); ?>" class="discovery-book-link" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Or book directly on Cal.com', 'malachy-portfolio' ); ?>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M7 17 17 7M7 7h10v10"/></svg>
		</a>
	</div>
</aside>
