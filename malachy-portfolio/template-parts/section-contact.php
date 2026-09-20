<?php
/**
 * Template Part: Contact Section
 *
 * Reference: polished-portfolio Contact section.
 * Heading + links on the left, underline form on card background on the right.
 *
 * Preserves form functionality: nonce, action, honeypot, AJAX status div.
 *
 * @package Malachy_Portfolio
 */

$contact_email = get_option( 'malachy_contact_email', 'malachy.egbuna@imadconsulting.co.uk' );
$linkedin      = 'https://www.linkedin.com/in/malachy-egbuna';
$github        = 'https://github.com/zubbyik';
?>
<section id="contact" class="contact section-wrap" aria-labelledby="contact-title">
	<div class="section-label reveal"><span>09</span><span><?php esc_html_e( 'Make something useful', 'malachy-portfolio' ); ?></span></div>
	<div class="contact-layout">
		<div class="contact-heading reveal">
			<h2 id="contact-title">
				<?php esc_html_e( "Let's", 'malachy-portfolio' ); ?><br />
				<em><?php esc_html_e( 'build.', 'malachy-portfolio' ); ?></em>
			</h2>
			<p><?php esc_html_e( 'Have a tricky problem, a new thing to test or a site that needs a steady pair of hands?', 'malachy-portfolio' ); ?></p>
			<div class="contact-links">
				<a href="mailto:<?php echo esc_attr( $contact_email ); ?>">
					<?php echo esc_html( $contact_email ); ?>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
				</a>
				<a href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'LinkedIn', 'malachy-portfolio' ); ?>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
				</a>
				<a href="<?php echo esc_url( $github ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'GitHub', 'malachy-portfolio' ); ?>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
				</a>
			</div>
		</div>

		<form id="contact-form" class="contact-form reveal" method="post">
			<?php wp_nonce_field( 'malachy_contact_nonce', 'malachy_nonce' ); ?>
			<input type="hidden" name="action" value="malachy_send_contact" />
			<input type="hidden" name="malachy_service" id="malachy_service" value="" />

		<!-- Honeypot: hidden from real users via CSS, bots that auto-fill every input will trip it. -->
		<div style="position:absolute;left:-9999px" aria-hidden="true">
			<input type="text" name="website" tabindex="-1" autocomplete="off" />
		</div>

		<p id="contact-service-context" class="contact-service-context" style="display:none"></p>

		<label>
			<span><?php esc_html_e( 'Your name', 'malachy-portfolio' ); ?></span>
			<input type="text" id="malachy_name" name="malachy_name" placeholder="<?php esc_attr_e( 'Jane Smith', 'malachy-portfolio' ); ?>" required autocomplete="name" />
		</label>
		<label>
			<span><?php esc_html_e( 'Email address', 'malachy-portfolio' ); ?></span>
			<input type="email" id="malachy_email" name="malachy_email" placeholder="<?php esc_attr_e( 'jane@company.com', 'malachy-portfolio' ); ?>" required autocomplete="email" />
		</label>
		<label>
			<span><?php esc_html_e( 'What are you trying to solve?', 'malachy-portfolio' ); ?></span>
			<textarea id="malachy_message" name="malachy_message" rows="4" placeholder="<?php esc_attr_e( 'A little context goes a long way...', 'malachy-portfolio' ); ?>" required></textarea>
		</label>
		<input type="hidden" name="source_campaign" id="source_campaign" value="" />
		<button type="submit" class="btn-primary" id="contact-submit">
			<?php esc_html_e( 'Send enquiry', 'malachy-portfolio' ); ?>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
		</button>
			<div id="contact-status" style="font-size:0.75rem;min-height:1.25rem;margin-top:0.5rem"></div>
		</form>
	</div>
</section>
